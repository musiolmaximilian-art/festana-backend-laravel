<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Services\StripeService;
use Illuminate\Http\Request;

class StripeController extends Controller
{
    public function createOnboardingLink(Request $request, int $eventId, StripeService $stripe)
    {
        $event = Event::where('owner_id', $request->user()->id)->findOrFail($eventId);

        if (!$event->stripe_account_id) {
            $account = $stripe->createExpressAccount();
            $event->stripe_account_id = $account['id'] ?? null;
            $event->save();
        }

        $frontendUrl = rtrim(config('stripe.frontend_url', ''), '/');
        $refreshUrl = $frontendUrl.'/stripe/onboarding/refresh?eventId='.$event->id;
        $returnUrl = $frontendUrl.'/stripe/onboarding/return?eventId='.$event->id;

        $link = $stripe->createAccountLink($event->stripe_account_id, $refreshUrl, $returnUrl);

        return response()->json(['url' => $link['url'] ?? null]);
    }

    public function createDashboardLink(Request $request, int $eventId, StripeService $stripe)
    {
        $event = Event::where('owner_id', $request->user()->id)->findOrFail($eventId);

        if (!$event->stripe_account_id) {
            return response()->json(['message' => 'Stripe account not connected.'], 422);
        }

        $link = $stripe->createLoginLink($event->stripe_account_id);

        return response()->json(['url' => $link['url'] ?? null]);
    }

    public function status(Request $request, int $eventId, StripeService $stripe)
    {
        $event = Event::where('owner_id', $request->user()->id)->findOrFail($eventId);

        if (!$event->stripe_account_id) {
            return response()->json(['status' => 'pending']);
        }

        $account = $stripe->retrieveAccount($event->stripe_account_id);
        $status = $stripe->deriveStatus($account);

        $event->stripe_account_status = $status;
        $event->save();

        return response()->json(['status' => $status]);
    }
}
