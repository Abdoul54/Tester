<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasRoles, HasFactory, Notifiable, HasApiTokens;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function getRole(): string
    {
        return $this->getRoleNames()->first() ?? 'user';
    }

    public function hasVerifiedEmail()
    {
        return !is_null($this->email_verified_at);
    }

    public function markEmailAsVerified()
    {
        return $this->forceFill([
            'email_verified_at' => $this->freshTimestamp(),
        ])->save();
    }

    public function getAvatarUrl($avatarPath)
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

    public function posts()
    {
        return $this->hasMany(Post::class);
    }
}
