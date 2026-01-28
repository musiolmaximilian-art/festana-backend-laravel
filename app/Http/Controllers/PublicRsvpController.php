<?php

namespace App\Http\Controllers;

use App\Models\Guest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PublicRsvpController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'attendance_status' => ['required', 'string', Rule::in(['attending', 'declined'])],
            'has_plus_one' => ['sometimes', 'boolean'],
            'dietary_restrictions' => ['sometimes', 'nullable', 'string', 'max:255'],
            'preferred_meal' => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);

        $guest = Guest::where('invitation_token_hash', Guest::hashInvitationToken($data['token']))->first();

        if (! $guest) {
            return response()->json(['message' => 'Guest not found.'], 404);
        }

        $guest->attendance_status = $data['attendance_status'];

        if (array_key_exists('has_plus_one', $data)) {
            $guest->has_plus_one = $data['has_plus_one'];
        }

        if (array_key_exists('dietary_restrictions', $data)) {
            $guest->dietary_restrictions = $data['dietary_restrictions'];
        }

        if (array_key_exists('preferred_meal', $data)) {
            $guest->preferred_meal = $data['preferred_meal'];
        }

        $guest->save();

        return response()->json([
            'first_name' => $guest->first_name,
            'last_name' => $guest->last_name,
            'attendance_status' => $guest->attendance_status,
        ]);
    }
}
