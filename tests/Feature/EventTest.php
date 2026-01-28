<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_requires_authentication(): void
    {
        $this->getJson('/api/events')->assertUnauthorized();
    }

    public function test_index_returns_only_owned_events(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $ownedEvent = Event::factory()->create(['owner_id' => $owner->id]);
        Event::factory()->create(['owner_id' => $other->id]);

        $response = $this->withAuthToken($owner)->getJson('/api/events');

        $response->assertOk()->assertJsonCount(1);
        $this->assertSame($ownedEvent->id, $response->json('0.id'));
    }

    public function test_store_creates_event_for_authenticated_user(): void
    {
        $user = User::factory()->create();

        $payload = [
            'website_name' => 'festana-wedding',
            'title' => 'Festana Wedding',
            'wedding_date' => '2030-06-01',
            'timezone' => 'Europe/Berlin',
            'public_settings' => ['is_public' => true],
        ];

        $response = $this->withAuthToken($user)->postJson('/api/events', $payload);

        $response->assertCreated()->assertJsonFragment([
            'website_name' => 'festana-wedding',
            'title' => 'Festana Wedding',
        ]);

        $this->assertDatabaseHas('events', [
            'website_name' => 'festana-wedding',
            'owner_id' => $user->id,
        ]);
    }

    public function test_store_requires_authentication(): void
    {
        $this->postJson('/api/events', [])->assertUnauthorized();
    }

    public function test_show_returns_owned_event(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->create(['owner_id' => $user->id]);

        $response = $this->withAuthToken($user)->getJson('/api/events/'.$event->id);

        $response->assertOk()->assertJson([
            'id' => $event->id,
            'website_name' => $event->website_name,
        ]);
    }

    public function test_update_requires_owner(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $event = Event::factory()->create(['owner_id' => $owner->id]);

        $this->withAuthToken($other)
            ->patchJson('/api/events/'.$event->id, [
                'website_name' => $event->website_name,
                'title' => 'Updated Title',
            ])
            ->assertNotFound();
    }

    public function test_update_changes_event_details(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->create(['owner_id' => $user->id]);

        $response = $this->withAuthToken($user)
            ->patchJson('/api/events/'.$event->id, [
                'website_name' => $event->website_name,
                'title' => 'Updated Title',
                'public_settings' => ['is_public' => true],
            ]);

        $response->assertOk()->assertJsonFragment([
            'title' => 'Updated Title',
        ]);

        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'title' => 'Updated Title',
        ]);
    }

    public function test_delete_requires_owner(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $event = Event::factory()->create(['owner_id' => $owner->id]);

        $this->withAuthToken($other)
            ->deleteJson('/api/events/'.$event->id)
            ->assertNotFound();
    }

    public function test_delete_removes_event(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->create(['owner_id' => $user->id]);

        $this->withAuthToken($user)
            ->deleteJson('/api/events/'.$event->id)
            ->assertNoContent();

        $this->assertDatabaseMissing('events', [
            'id' => $event->id,
        ]);
    }

    public function test_public_show_requires_event_to_be_public(): void
    {
        $event = Event::factory()->create([
            'website_name' => 'private-site',
            'public_settings' => ['is_public' => false],
        ]);

        $this->getJson('/api/public/events/'.$event->website_name)
            ->assertNotFound();
    }

    public function test_public_show_returns_public_event(): void
    {
        $event = Event::factory()->public()->create([
            'website_name' => 'public-site',
        ]);

        $this->getJson('/api/public/events/'.$event->website_name)
            ->assertOk()
            ->assertJsonPath('event.website_name', 'public-site');
    }

    public function test_public_show_returns_visible_gifts_for_public_event(): void
    {
        $event = Event::factory()->public()->create([
            'website_name' => 'public-gifts',
        ]);

        $secondGift = \App\Models\Gift::factory()->create([
            'event_id' => $event->id,
            'title' => 'Second Gift',
            'sort_order' => 2,
            'is_visible' => true,
        ]);
        $firstGift = \App\Models\Gift::factory()->create([
            'event_id' => $event->id,
            'title' => 'First Gift',
            'sort_order' => 1,
            'is_visible' => true,
        ]);
        \App\Models\Gift::factory()->hidden()->create([
            'event_id' => $event->id,
        ]);

        $response = $this->getJson('/api/public/events/'.$event->website_name);

        $response->assertOk()
            ->assertJsonPath('event.website_name', 'public-gifts')
            ->assertJsonCount(2, 'gifts');

        $this->assertSame($firstGift->id, $response->json('gifts.0.id'));
        $this->assertSame($secondGift->id, $response->json('gifts.1.id'));
    }

    private function withAuthToken(User $user)
    {
        $token = $user->createToken('auth')->plainTextToken;

        return $this->withHeader('Authorization', 'Bearer '.$token);
    }
}
