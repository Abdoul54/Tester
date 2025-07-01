<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Knuckles\Scribe\Attributes\Group;

#[Group("Users", "APIs for managing users")]
class UserController extends Controller
{
    /**
     * Get the authenticated user.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function profile()
    {
        // Ensure the user is authenticated
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Return user profile data
        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'avatar' => $user->avatar ? asset('storage/' . $user->avatar) : null, // Assuming avatar is stored in public disk
            'role' => $user->getRole(),
            'email_verified' => $user->hasVerifiedEmail(),
            'created_at' => $user->created_at->toIso8601String(),
            'updated_at' => $user->updated_at->toIso8601String(),
        ]);
    }
}
