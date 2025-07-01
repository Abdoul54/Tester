<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Knuckles\Scribe\Attributes\Group;

#[Group("Authentication", "APIs for managing authentication")]
class AuthController extends Controller
{
    /**
     * The storage disk for file uploads.
     *
     * @var \Illuminate\Contracts\Filesystem\Filesystem
     */
    protected $storage;

    public function __construct()
    {
        $this->storage = Storage::disk('s3');
    }

    /**
     * Register a new user.
     *
     * @param RegisterRequest $request
     * 
     * @return JsonResponse
     */
    public function register(RegisterRequest $request)
    {
        $avatarPath = null;

        try {
            // Handle avatar upload if provided (before DB transaction)
            if ($request->hasFile('avatar')) {
                $avatarFile = $request->file('avatar');

                // Generate unique filename
                $filename = time() . '_' . Str::random(10) . '.' . $avatarFile->getClientOriginalExtension();

                // Upload to S3/MinIO
                $avatarPath = $this->storage->putFileAs('avatars', $avatarFile, $filename);

                // Check if upload was successful
                if (!$avatarPath) {
                    throw new \Exception('Avatar upload failed');
                }
            }

            // Wrap user creation and token generation in a database transaction
            $result = DB::transaction(function () use ($request, $avatarPath) {
                // Create a new user instance
                $user = User::create([
                    'name' => $request->name,
                    'email' => $request->email,
                    'password' => bcrypt($request->password),
                    'phone' => $request->phone,
                    'avatar' => $avatarPath, // Store the file path
                ]);

                $user->assignRole($request->role); // Assign role to the user

                // Generate token using Sanctum (if this fails, user creation will be rolled back)
                $token = $user->createToken('auth-token')->plainTextToken;

                return [
                    'user' => $user,
                    'token' => $token
                ];
            });


            // Prepare response data
            $responseData = [
                'message' => 'Registration successful',
                'user' => [
                    'id' => $result['user']->id,
                    'name' => $result['user']->name,
                    'email' => $result['user']->email,
                    'phone' => $result['user']->phone,
                    'avatar_url' => $avatarPath ? $this->getAvatarUrl($avatarPath) : null,
                ],
                'token' => $result['token'] // Include the token in the response
            ];

            return response()->json($responseData, 201);
        } catch (\Throwable $th) {
            // Clean up uploaded file if anything fails
            if ($avatarPath) {
                $this->storage->delete($avatarPath);
            }

            // Handle any exceptions that occur during registration
            return response()->json(['message' => 'Registration failed', 'error' => $th->getMessage()], 500);
        }
    }


    /**
     * Generate access token for user, by provided email and password
     *
     * @param LoginRequest $request
     * @return JsonResponse
     */
    public function login(LoginRequest $request)
    {
        try {
            // Attempt to authenticate using the web guard for credential checking
            if (Auth::guard('web')->attempt($request->only('email', 'password'))) {
                // Get the user after successful authentication
                $user = User::where('email', $request->email)->first();

                // Generate token using Sanctum
                $token = $user->createToken('auth-token')->plainTextToken;

                return response()->json([
                    'message' => 'Login successful',
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'email_verified' => $user->hasVerifiedEmail(),
                        'phone' => $user->phone,
                        'avatar' => $this->getAvatarUrl($user->avatar),
                        'role' => $user->getRole(),
                        'created_at' => $user->created_at->toIso8601String(),
                        'updated_at' => $user->updated_at->toIso8601String(),
                    ],
                    'token' => $token
                ], 200);
            }

            return response()->json(['message' => 'Invalid credentials'], 401);
        } catch (\Throwable $th) {
            return response()->json(['message' => 'Login failed', 'error' => $th->getMessage()], 500);
        }
    }

    /**
     * Get the full URL for an avatar - optimized for MinIO
     */
    private function getAvatarUrl($avatarPath)
    {
        if (!$avatarPath) {
            return null;
        }

        try {
            // For MinIO in development, construct the public URL manually
            // Since your bucket is set to public, we can use direct URLs
            $baseUrl = config('filesystems.disks.s3.url');
            $bucket = config('filesystems.disks.s3.bucket');

            // Construct the public URL
            return $baseUrl . '/' . $bucket . '/' . $avatarPath;

            // Alternative: Use pre-signed URLs (works even if bucket is not public)
            // return Storage::disk('s3')->temporaryUrl($avatarPath, now()->addHours(24));

        } catch (\Exception $e) {
            // Fallback: return null if URL generation fails
            Log::error('Avatar URL generation failed: ' . $e->getMessage());
            return null;
        }
    }
}
