<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Guest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuestTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_requires_authentication(): void
    {
        $event = Event::factory()->create();

        $this->getJson('/api/events/'.$event->id.'/guests')->assertUnauthorized();
    }

    public function test_index_filters_by_attendance_status(): void
    {
        $owner = User::factory()->create();
        $event = Event::factory()->create(['owner_id' => $owner->id]);

        $attendingGuest = Guest::factory()->create([
            'event_id' => $event->id,
            'attendance_status' => 'attending',
        ]);
        Guest::factory()->create([
            'event_id' => $event->id,
            'attendance_status' => 'declined',
        ]);

        $response = $this->withAuthToken($owner)
            ->getJson('/api/events/'.$event->id.'/guests?attendance_status=attending');

        $response->assertOk()->assertJsonCount(1);
        $this->assertSame($attendingGuest->id, $response->json('0.id'));
    }

    public function test_index_filters_by_search_query(): void
    {
        $owner = User::factory()->create();
        $event = Event::factory()->create(['owner_id' => $owner->id]);

        $matchedGuest = Guest::factory()->create([
            'event_id' => $event->id,
            'first_name' => 'Alex',
            'email' => 'alex@example.com',
        ]);
        Guest::factory()->create([
            'event_id' => $event->id,
            'first_name' => 'Brooke',
            'email' => 'brooke@example.com',
        ]);

        $response = $this->withAuthToken($owner)
            ->getJson('/api/events/'.$event->id.'/guests?q=alex');

        $response->assertOk()->assertJsonCount(1);
        $this->assertSame($matchedGuest->id, $response->json('0.id'));
    }

    public function test_store_requires_authentication(): void
    {
        $event = Event::factory()->create();

        $this->postJson('/api/events/'.$event->id.'/guests', [])->assertUnauthorized();
    }

    public function test_store_requires_owner(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $event = Event::factory()->create(['owner_id' => $owner->id]);

        $this->withAuthToken($other)
            ->postJson('/api/events/'.$event->id.'/guests', ['first_name' => 'Jane'])
            ->assertNotFound();
    }

    public function test_store_creates_guest(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->create(['owner_id' => $user->id]);

        $payload = [
            'first_name' => 'Jamie',
            'last_name' => 'Lee',
            'email' => 'jamie@example.com',
            'phone' => '+123456789',
            'tags' => ['family', 'vip'],
            'has_plus_one' => true,
            'dietary_restrictions' => 'Vegetarian',
            'preferred_meal' => 'Pasta',
            'attendance_status' => 'invited',
            'notes' => 'Prefers aisle seat',
        ];

        $response = $this->withAuthToken($user)
            ->postJson('/api/events/'.$event->id.'/guests', $payload);

        $response->assertCreated()->assertJsonFragment([
            'first_name' => 'Jamie',
            'attendance_status' => 'invited',
        ]);

        $this->assertDatabaseHas('guests', [
            'event_id' => $event->id,
            'email' => 'jamie@example.com',
        ]);
    }

    public function test_update_requires_owner(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $guest = Guest::factory()->create([
            'event_id' => Event::factory()->create(['owner_id' => $owner->id])->id,
        ]);

        $this->withAuthToken($other)
            ->patchJson('/api/guests/'.$guest->id, ['attendance_status' => 'attending'])
            ->assertNotFound();
    }

    public function test_update_changes_guest(): void
    {
        $user = User::factory()->create();
        $guest = Guest::factory()->create([
            'event_id' => Event::factory()->create(['owner_id' => $user->id])->id,
            'attendance_status' => 'unknown',
        ]);

        $response = $this->withAuthToken($user)
            ->patchJson('/api/guests/'.$guest->id, [
                'attendance_status' => 'attending',
                'notes' => 'Confirmed with plus one',
            ]);

        $response->assertOk()->assertJsonFragment([
            'attendance_status' => 'attending',
            'notes' => 'Confirmed with plus one',
        ]);

        $this->assertDatabaseHas('guests', [
            'id' => $guest->id,
            'attendance_status' => 'attending',
        ]);
    }

    public function test_delete_requires_owner(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $guest = Guest::factory()->create([
            'event_id' => Event::factory()->create(['owner_id' => $owner->id])->id,
        ]);

        $this->withAuthToken($other)
            ->deleteJson('/api/guests/'.$guest->id)
            ->assertNotFound();
    }

    public function test_delete_removes_guest(): void
    {
        $user = User::factory()->create();
        $guest = Guest::factory()->create([
            'event_id' => Event::factory()->create(['owner_id' => $user->id])->id,
        ]);

        $this->withAuthToken($user)
            ->deleteJson('/api/guests/'.$guest->id)
            ->assertNoContent();

        $this->assertDatabaseMissing('guests', [
            'id' => $guest->id,
        ]);
    }

    private function withAuthToken(User $user)
    {
        $token = $user->createToken('auth')->plainTextToken;

        return $this->withHeader('Authorization', 'Bearer '.$token);
    }
}
