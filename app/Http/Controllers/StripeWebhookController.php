<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Services\StripeService;
use Illuminate\Http\Request;

class StripeWebhookController extends Controller
{
    public function __invoke(Request $request, StripeService $stripe)
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');
        $secret = config('stripe.webhook_secret');

        if (!$secret || !$stripe->verifyWebhookSignature($payload, $signature, $secret)) {
            return response()->json(['message' => 'Invalid signature.'], 400);
        }

        $event = $request->json()->all();

        if (($event['type'] ?? null) === 'account.updated') {
            $account = $event['data']['object'] ?? [];
            $accountId = $account['id'] ?? null;

            if ($accountId) {
                $status = $stripe->deriveStatus($account);
                Event::where('stripe_account_id', $accountId)
                    ->update(['stripe_account_status' => $status]);
            }
        }

        return response()->json(['received' => true]);
    }
}
