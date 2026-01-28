<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Gift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GiftTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_requires_authentication(): void
    {
        $event = Event::factory()->create();

        $this->getJson('/api/events/'.$event->id.'/gifts')->assertUnauthorized();
    }

    public function test_index_returns_gifts_for_owned_event(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $event = Event::factory()->create(['owner_id' => $owner->id]);
        $otherEvent = Event::factory()->create(['owner_id' => $other->id]);

        Gift::factory()->create(['event_id' => $event->id, 'sort_order' => 2]);
        $firstGift = Gift::factory()->create(['event_id' => $event->id, 'sort_order' => 1]);
        Gift::factory()->create(['event_id' => $otherEvent->id]);

        $response = $this->withAuthToken($owner)->getJson('/api/events/'.$event->id.'/gifts');

        $response->assertOk()->assertJsonCount(2);
        $this->assertSame($firstGift->id, $response->json('0.id'));
    }

    public function test_store_requires_authentication(): void
    {
        $event = Event::factory()->create();

        $this->postJson('/api/events/'.$event->id.'/gifts', [])->assertUnauthorized();
    }

    public function test_store_requires_owner(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $event = Event::factory()->create(['owner_id' => $owner->id]);

        $this->withAuthToken($other)
            ->postJson('/api/events/'.$event->id.'/gifts', ['title' => 'Registry Item'])
            ->assertNotFound();
    }

    public function test_store_creates_gift(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->create(['owner_id' => $user->id]);

        $payload = [
            'title' => 'Toaster',
            'description' => 'Stainless steel toaster',
            'price_cents' => 4500,
            'currency' => 'EUR',
            'is_visible' => true,
            'sort_order' => 3,
            'is_cash_gift' => false,
            'image_url' => 'https://example.com/toaster.png',
        ];

        $response = $this->withAuthToken($user)
            ->postJson('/api/events/'.$event->id.'/gifts', $payload);

        $response->assertCreated()->assertJsonFragment([
            'title' => 'Toaster',
            'price_cents' => 4500,
        ]);

        $this->assertDatabaseHas('gifts', [
            'event_id' => $event->id,
            'title' => 'Toaster',
        ]);
    }

    public function test_update_requires_owner(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $gift = Gift::factory()->create([
            'event_id' => Event::factory()->create(['owner_id' => $owner->id])->id,
        ]);

        $this->withAuthToken($other)
            ->patchJson('/api/gifts/'.$gift->id, ['title' => 'Updated'])
            ->assertNotFound();
    }

    public function test_update_changes_gift(): void
    {
        $user = User::factory()->create();
        $gift = Gift::factory()->create([
            'event_id' => Event::factory()->create(['owner_id' => $user->id])->id,
            'title' => 'Original',
        ]);

        $response = $this->withAuthToken($user)
            ->patchJson('/api/gifts/'.$gift->id, [
                'title' => 'Updated',
                'is_visible' => false,
                'price_cents' => null,
            ]);

        $response->assertOk()->assertJsonFragment([
            'title' => 'Updated',
            'is_visible' => false,
        ]);

        $this->assertDatabaseHas('gifts', [
            'id' => $gift->id,
            'title' => 'Updated',
        ]);
    }

    public function test_delete_requires_owner(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $gift = Gift::factory()->create([
            'event_id' => Event::factory()->create(['owner_id' => $owner->id])->id,
        ]);

        $this->withAuthToken($other)
            ->deleteJson('/api/gifts/'.$gift->id)
            ->assertNotFound();
    }

    public function test_delete_removes_gift(): void
    {
        $user = User::factory()->create();
        $gift = Gift::factory()->create([
            'event_id' => Event::factory()->create(['owner_id' => $user->id])->id,
        ]);

        $this->withAuthToken($user)
            ->deleteJson('/api/gifts/'.$gift->id)
            ->assertNoContent();

        $this->assertDatabaseMissing('gifts', [
            'id' => $gift->id,
        ]);
    }

    public function test_public_index_requires_event_to_be_public(): void
    {
        $event = Event::factory()->create([
            'website_name' => 'private-event',
            'public_settings' => ['is_public' => false],
        ]);

        $this->getJson('/api/public/events/'.$event->website_name.'/gifts')
            ->assertNotFound();
    }

    public function test_public_index_returns_only_visible_gifts(): void
    {
        $event = Event::factory()->public()->create([
            'website_name' => 'public-event',
        ]);

        $visibleGift = Gift::factory()->create([
            'event_id' => $event->id,
            'is_visible' => true,
        ]);
        Gift::factory()->hidden()->create([
            'event_id' => $event->id,
        ]);

        $response = $this->getJson('/api/public/events/'.$event->website_name.'/gifts');

        $response->assertOk()->assertJsonCount(1);
        $this->assertSame($visibleGift->id, $response->json('0.id'));
    }

    private function withAuthToken(User $user)
    {
        $token = $user->createToken('auth')->plainTextToken;

        return $this->withHeader('Authorization', 'Bearer '.$token);
    }
}
