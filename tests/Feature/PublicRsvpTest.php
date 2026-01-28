<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Guest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicRsvpTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_token_updates_guest(): void
    {
        $token = 'guest-token';
        $guest = Guest::factory()->create([
            'attendance_status' => 'unknown',
            'has_plus_one' => false,
            'invitation_token_hash' => Guest::hashInvitationToken($token),
        ]);

        $response = $this->postJson('/api/public/rsvp', [
            'token' => $token,
            'attendance_status' => 'attending',
            'has_plus_one' => true,
            'dietary_restrictions' => 'Vegetarian',
            'preferred_meal' => 'Pasta',
        ]);

        $response->assertOk()->assertJsonFragment([
            'first_name' => $guest->first_name,
            'attendance_status' => 'attending',
        ]);

        $this->assertDatabaseHas('guests', [
            'id' => $guest->id,
            'attendance_status' => 'attending',
            'has_plus_one' => true,
            'dietary_restrictions' => 'Vegetarian',
            'preferred_meal' => 'Pasta',
        ]);
    }

    public function test_invalid_token_returns_not_found(): void
    {
        $response = $this->postJson('/api/public/rsvp', [
            'token' => 'invalid-token',
            'attendance_status' => 'declined',
        ]);

        $response->assertNotFound();
    }

    public function test_owner_can_regenerate_invite_token(): void
    {
        $owner = User::factory()->create();
        $guest = Guest::factory()->create([
            'event_id' => Event::factory()->create(['owner_id' => $owner->id])->id,
        ]);

        $response = $this->withAuthToken($owner)
            ->postJson('/api/guests/'.$guest->id.'/invite-token');

        $response->assertOk();
        $token = $response->json('token');
        $this->assertNotEmpty($token);
        $this->assertDatabaseHas('guests', [
            'id' => $guest->id,
            'invitation_token_hash' => Guest::hashInvitationToken($token),
        ]);

        $secondResponse = $this->withAuthToken($owner)
            ->postJson('/api/guests/'.$guest->id.'/invite-token');

        $secondResponse->assertOk();
        $this->assertNotSame($token, $secondResponse->json('token'));
    }

    private function withAuthToken(User $user)
    {
        $token = $user->createToken('auth')->plainTextToken;

        return $this->withHeader('Authorization', 'Bearer '.$token);
    }
}
