<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Guest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GuestController extends Controller
{
    public function index(Request $request, int $eventId)
    {
        $event = Event::where('owner_id', $request->user()->id)->findOrFail($eventId);

        $query = $event->guests()->orderBy('id');

        if ($attendanceStatus = $request->query('attendance_status')) {
            $query->where('attendance_status', $attendanceStatus);
        }

        if ($search = $request->query('q')) {
            $query->where(function ($subQuery) use ($search) {
                $like = '%'.$search.'%';

                $subQuery
                    ->where('first_name', 'like', $like)
                    ->orWhere('last_name', 'like', $like)
                    ->orWhere('email', 'like', $like);
            });
        }

        return response()->json($query->get());
    }

    public function store(Request $request, int $eventId)
    {
        $event = Event::where('owner_id', $request->user()->id)->findOrFail($eventId);
        $data = $this->validateGuest($request);

        $guest = $event->guests()->create($data);

        return response()->json($guest, 201);
    }

    public function update(Request $request, int $id)
    {
        $guest = Guest::whereHas('event', function ($query) use ($request) {
            $query->where('owner_id', $request->user()->id);
        })->findOrFail($id);

        $data = $this->validateGuest($request, $guest);

        $guest->update($data);

        return response()->json($guest);
    }

    public function destroy(Request $request, int $id)
    {
        $guest = Guest::whereHas('event', function ($query) use ($request) {
            $query->where('owner_id', $request->user()->id);
        })->findOrFail($id);

        $guest->delete();

        return response()->noContent();
    }

    private function validateGuest(Request $request, ?Guest $guest = null): array
    {
        $requiredRule = $guest ? 'sometimes' : 'required';

        return $request->validate([
            'first_name' => [$requiredRule, 'string', 'max:255'],
            'last_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:50'],
            'tags' => ['sometimes', 'array'],
            'tags.*' => ['sometimes', 'string', 'max:255'],
            'has_plus_one' => ['sometimes', 'boolean'],
            'dietary_restrictions' => ['sometimes', 'nullable', 'string', 'max:255'],
            'preferred_meal' => ['sometimes', 'nullable', 'string', 'max:255'],
            'attendance_status' => ['sometimes', 'string', Rule::in(['invited', 'attending', 'declined', 'unknown'])],
            'notes' => ['sometimes', 'nullable', 'string'],
            'invitation_token_hash' => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);
    }
}
