<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use App\Services\StripeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StripeTest extends TestCase
{
    use RefreshDatabase;

    public function test_onboarding_link_requires_owner(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $event = Event::factory()->create(['owner_id' => $owner->id]);

        $this->mock(StripeService::class, function ($mock) {
            $mock->shouldNotReceive('createExpressAccount');
            $mock->shouldNotReceive('createAccountLink');
        });

        $this->withAuthToken($other)
            ->postJson('/api/events/'.$event->id.'/stripe/onboarding-link')
            ->assertNotFound();
    }

    public function test_onboarding_link_creates_account_and_link(): void
    {
        config(['stripe.frontend_url' => 'https://frontend.test']);

        $owner = User::factory()->create();
        $event = Event::factory()->create(['owner_id' => $owner->id]);

        $this->mock(StripeService::class, function ($mock) {
            $mock->shouldReceive('createExpressAccount')
                ->once()
                ->andReturn(['id' => 'acct_123']);
            $mock->shouldReceive('createAccountLink')
                ->once()
                ->andReturn(['url' => 'https://stripe.test/onboarding']);
        });

        $this->withAuthToken($owner)
            ->postJson('/api/events/'.$event->id.'/stripe/onboarding-link')
            ->assertOk()
            ->assertJson(['url' => 'https://stripe.test/onboarding']);

        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'stripe_account_id' => 'acct_123',
        ]);
    }

    public function test_dashboard_link_requires_owner(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $event = Event::factory()->create([
            'owner_id' => $owner->id,
            'stripe_account_id' => 'acct_123',
        ]);

        $this->mock(StripeService::class, function ($mock) {
            $mock->shouldNotReceive('createLoginLink');
        });

        $this->withAuthToken($other)
            ->postJson('/api/events/'.$event->id.'/stripe/dashboard-link')
            ->assertNotFound();
    }

    public function test_dashboard_link_returns_login_url(): void
    {
        $owner = User::factory()->create();
        $event = Event::factory()->create([
            'owner_id' => $owner->id,
            'stripe_account_id' => 'acct_123',
        ]);

        $this->mock(StripeService::class, function ($mock) {
            $mock->shouldReceive('createLoginLink')
                ->once()
                ->with('acct_123')
                ->andReturn(['url' => 'https://stripe.test/login']);
        });

        $this->withAuthToken($owner)
            ->postJson('/api/events/'.$event->id.'/stripe/dashboard-link')
            ->assertOk()
            ->assertJson(['url' => 'https://stripe.test/login']);
    }

    public function test_status_persists_account_status(): void
    {
        $owner = User::factory()->create();
        $event = Event::factory()->create([
            'owner_id' => $owner->id,
            'stripe_account_id' => 'acct_123',
        ]);

        $this->mock(StripeService::class, function ($mock) {
            $mock->shouldReceive('retrieveAccount')
                ->once()
                ->andReturn([
                    'id' => 'acct_123',
                    'charges_enabled' => true,
                    'payouts_enabled' => true,
                    'requirements' => [],
                ]);
            $mock->shouldReceive('deriveStatus')
                ->once()
                ->andReturn('connected');
        });

        $this->withAuthToken($owner)
            ->getJson('/api/events/'.$event->id.'/stripe/status')
            ->assertOk()
            ->assertJson(['status' => 'connected']);

        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'stripe_account_status' => 'connected',
        ]);
    }

    public function test_webhook_verifies_signature_and_updates_status(): void
    {
        config(['stripe.webhook_secret' => 'whsec_test']);

        $event = Event::factory()->create([
            'stripe_account_id' => 'acct_123',
        ]);

        $payload = [
            'type' => 'account.updated',
            'data' => [
                'object' => [
                    'id' => 'acct_123',
                    'charges_enabled' => false,
                    'payouts_enabled' => false,
                    'requirements' => [
                        'disabled_reason' => 'requirements.past_due',
                    ],
                ],
            ],
        ];

        $body = json_encode($payload);
        $timestamp = time();
        $signature = hash_hmac('sha256', $timestamp.'.'.$body, 'whsec_test');
        $header = 't='.$timestamp.',v1='.$signature;

        $this->withHeader('Stripe-Signature', $header)
            ->post('/api/stripe/webhook', $body, ['CONTENT_TYPE' => 'application/json'])
            ->assertOk()
            ->assertJson(['received' => true]);

        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'stripe_account_status' => 'restricted',
        ]);
    }

    private function withAuthToken(User $user)
    {
        $token = $user->createToken('auth')->plainTextToken;

        return $this->withHeader('Authorization', 'Bearer '.$token);
    }
}
