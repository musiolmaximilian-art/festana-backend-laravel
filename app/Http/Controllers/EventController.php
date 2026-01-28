<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Gift;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EventController extends Controller
{
    public function index(Request $request)
    {
        $events = Event::where('owner_id', $request->user()->id)->get();

        return response()->json($events);
    }

    public function store(Request $request)
    {
        $data = $this->validateEvent($request);

        $event = Event::create([
            ...$data,
            'owner_id' => $request->user()->id,
            'public_settings' => $data['public_settings'] ?? ['is_public' => false],
        ]);

        return response()->json($event, 201);
    }

    public function show(Request $request, int $id)
    {
        $event = Event::where('owner_id', $request->user()->id)->findOrFail($id);

        return response()->json($event);
    }

    public function update(Request $request, int $id)
    {
        $event = Event::where('owner_id', $request->user()->id)->findOrFail($id);
        $data = $this->validateEvent($request, $event);

        if (array_key_exists('public_settings', $data)) {
            $data['public_settings'] = [
                'is_public' => $data['public_settings']['is_public'] ?? false,
            ];
        }

        $event->update($data);

        return response()->json($event);
    }

    public function destroy(Request $request, int $id)
    {
        $event = Event::where('owner_id', $request->user()->id)->findOrFail($id);
        $event->delete();

        return response()->noContent();
    }

    public function publicShow(string $websiteName)
    {
        $event = Event::where('website_name', $websiteName)
            ->where('public_settings->is_public', true)
            ->firstOrFail();

        $gifts = $event->gifts()
            ->where('is_visible', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(function ($gift) {
                return $this->formatPublicGift($gift);
            });

        return response()->json([
            'event' => $this->formatPublicEvent($event),
            'gifts' => $gifts,
        ]);
    }

    private function formatPublicEvent(Event $event): array
    {
        return [
            'id' => $event->id,
            'website_name' => $event->website_name,
            'title' => $event->title,
            'wedding_date' => $event->wedding_date?->toDateString(),
            'timezone' => $event->timezone,
        ];
    }

    private function formatPublicGift(Gift $gift): array
    {
        return [
            'id' => $gift->id,
            'title' => $gift->title,
            'description' => $gift->description,
            'price_cents' => $gift->price_cents,
            'currency' => $gift->currency,
            'sort_order' => $gift->sort_order,
            'is_cash_gift' => $gift->is_cash_gift,
            'image_url' => $gift->image_url,
        ];
    }

    private function validateEvent(Request $request, ?Event $event = null): array
    {
        $uniqueWebsite = Rule::unique('events', 'website_name');

        if ($event) {
            $uniqueWebsite = $uniqueWebsite->ignore($event->id);
        }

        $requiredRule = $event ? 'sometimes' : 'required';

        return $request->validate([
            'website_name' => [$requiredRule, 'string', 'max:255', $uniqueWebsite],
            'title' => [$requiredRule, 'string', 'max:255'],
            'wedding_date' => ['nullable', 'date'],
            'timezone' => ['sometimes', 'string', 'max:255'],
            'public_settings' => ['sometimes', 'array'],
            'public_settings.is_public' => ['sometimes', 'boolean'],
        ]);
    }
}
