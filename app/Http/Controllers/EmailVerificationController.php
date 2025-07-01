<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\EmailVerificationNotification;
use Illuminate\Http\Request;
use Knuckles\Scribe\Attributes\Group;

#[Group("Email Verification", "APIs for managing email verification")]
class EmailVerificationController extends Controller
{
    /**
     * Resend the email verification notification.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function resend(Request $request)
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Email already verified.',
                'example' => 'Email already verified.'
            ]);
        }

        $user->notify(new EmailVerificationNotification());

        return response()->json([
            'message' => 'Verification email sent!',
            'example' => 'Verification email sent!'
        ]);
    }


    /**
     * Verify the user's email address.
     *
     * @param Request $request
     * @param int $id
     * @param string $hash
     * @return \Illuminate\Http\JsonResponse
     */
    public function verify(Request $request, $id, $hash)
    {
        $user = User::findOrFail($id);

        // Check if the hash matches
        if (!hash_equals($hash, sha1($user->email))) {
            return response()->json([
                'message' => 'Invalid verification link.'
            ], 400);
        }

        // Check if email is already verified
        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Email already verified.'
            ]);
        }

        // Mark email as verified
        $user->markEmailAsVerified();

        return response()->json([
            'message' => 'Email verified successfully!'
        ]);
    }

    /**
     * Check the email verification status of the authenticated user.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkStatus(Request $request)
    {
        return response()->json([
            'email_verified' => $request->user()->hasVerifiedEmail(),
            'user' => [
                'id' => $request->user()->id,
                'email' => $request->user()->email,
            ]
        ]);
    }
}
