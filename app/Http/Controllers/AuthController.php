<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
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

            $password = Hash::make($request->password); // Hash the password

            // Wrap user creation and token generation in a database transaction
            $user = DB::transaction(function () use ($request, $avatarPath, $password) {
                // Create a new user instance
                $user = User::create([
                    'name' => $request->name,
                    'email' => $request->email,
                    'password' => $password,
                    'phone' => $request->phone,
                    'avatar' => $avatarPath, // Store the file path
                ]);

                $user->assignRole($request->role ?? 'user'); // Assign role to the user

                return  $user;
            });


            // Prepare response data
            $responseData = [
                'message' => 'Registration successful',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'email_verified' => $user->hasVerifiedEmail(),
                    'role' => $user->getRole(),
                    'avatar_url' => $avatarPath ? $user->getAvatarUrl($avatarPath) : null,
                    'created_at' => $user->created_at->toIso8601String(),
                    'updated_at' => $user->updated_at->toIso8601String(),
                ]
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
                        'avatar' => $user->getAvatarUrl($user->avatar),
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
}
