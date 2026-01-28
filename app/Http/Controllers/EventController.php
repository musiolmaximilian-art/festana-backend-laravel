<?php

namespace App\Http\Controllers;

use App\Models\Event;
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

        return response()->json($event);
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
