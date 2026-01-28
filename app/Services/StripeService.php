<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class StripeService
{
    public function createExpressAccount(): array
    {
        return $this->request('post', '/v1/accounts', [
            'type' => 'express',
            'business_type' => 'individual',
        ]);
    }

    public function createAccountLink(string $accountId, string $refreshUrl, string $returnUrl): array
    {
        return $this->request('post', '/v1/account_links', [
            'account' => $accountId,
            'type' => 'account_onboarding',
            'refresh_url' => $refreshUrl,
            'return_url' => $returnUrl,
        ]);
    }

    public function createLoginLink(string $accountId): array
    {
        return $this->request('post', "/v1/accounts/{$accountId}/login_links");
    }

    public function retrieveAccount(string $accountId): array
    {
        return $this->request('get', "/v1/accounts/{$accountId}");
    }

    public function deriveStatus(array $account): string
    {
        if (($account['charges_enabled'] ?? false) && ($account['payouts_enabled'] ?? false)) {
            return 'connected';
        }

        $requirements = $account['requirements'] ?? [];
        $disabledReason = $requirements['disabled_reason'] ?? ($account['disabled_reason'] ?? null);

        if ($disabledReason) {
            return 'restricted';
        }

        return 'pending';
    }

    public function verifyWebhookSignature(string $payload, ?string $signatureHeader, string $secret): bool
    {
        if (!$signatureHeader) {
            return false;
        }

        $parts = collect(explode(',', $signatureHeader))
            ->mapWithKeys(function (string $part) {
                [$key, $value] = array_pad(explode('=', $part, 2), 2, null);

                return [$key => $value];
            });

        $timestamp = $parts->get('t');
        $signature = $parts->get('v1');

        if (!$timestamp || !$signature) {
            return false;
        }

        $signedPayload = $timestamp.'.'.$payload;
        $expected = hash_hmac('sha256', $signedPayload, $secret);

        return hash_equals($expected, $signature);
    }

    private function request(string $method, string $path, array $data = []): array
    {
        $response = $this->client()->{$method}('https://api.stripe.com'.$path, $data);

        return $response->json();
    }

    private function client(): PendingRequest
    {
        return Http::asForm()
            ->withToken(config('stripe.secret_key'));
    }
}
