<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Gift;
use Illuminate\Http\Request;

class GiftController extends Controller
{
    public function index(Request $request, int $eventId)
    {
        $event = Event::where('owner_id', $request->user()->id)->findOrFail($eventId);

        $gifts = $event->gifts()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return response()->json($gifts);
    }

    public function store(Request $request, int $eventId)
    {
        $event = Event::where('owner_id', $request->user()->id)->findOrFail($eventId);
        $data = $this->validateGift($request);

        $gift = $event->gifts()->create($data);

        return response()->json($gift, 201);
    }

    public function update(Request $request, int $id)
    {
        $gift = Gift::whereHas('event', function ($query) use ($request) {
            $query->where('owner_id', $request->user()->id);
        })->findOrFail($id);

        $data = $this->validateGift($request, $gift);

        $gift->update($data);

        return response()->json($gift);
    }

    public function destroy(Request $request, int $id)
    {
        $gift = Gift::whereHas('event', function ($query) use ($request) {
            $query->where('owner_id', $request->user()->id);
        })->findOrFail($id);

        $gift->delete();

        return response()->noContent();
    }

    public function publicIndex(string $websiteName)
    {
        $event = Event::where('website_name', $websiteName)
            ->where('public_settings->is_public', true)
            ->firstOrFail();

        $gifts = $event->gifts()
            ->where('is_visible', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return response()->json($gifts);
    }

    private function validateGift(Request $request, ?Gift $gift = null): array
    {
        $requiredRule = $gift ? 'sometimes' : 'required';

        return $request->validate([
            'title' => [$requiredRule, 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'price_cents' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'currency' => ['sometimes', 'string', 'max:10'],
            'is_visible' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer'],
            'is_cash_gift' => ['sometimes', 'boolean'],
            'image_url' => ['sometimes', 'nullable', 'string', 'max:2048'],
        ]);
    }
}
