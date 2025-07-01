<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class Post extends Model
{
    protected $fillable = [
        'title',
        'content',
        'user_id',
        'slug',
        'status',
        'published_at',
        'thumbnail',
        'tags',
    ];

    protected $casts = [
        'tags' => 'array',
        'published_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getStatusAttribute($value)
    {
        return ucfirst($value);
    }

    public function getThumbnailUrl($thumbnailPath)
    {
        if (!$thumbnailPath) {
            return null;
        }

        try {
            // For MinIO in development, construct the public URL manually
            // Since your bucket is set to public, we can use direct URLs
            $baseUrl = config('filesystems.disks.s3.url');
            $bucket = config('filesystems.disks.s3.bucket');

            // Construct the public URL
            return $baseUrl . '/' . $bucket . '/' . $thumbnailPath;

            // Alternative: Use pre-signed URLs (works even if bucket is not public)
            // return Storage::disk('s3')->temporaryUrl($avatarPath, now()->addHours(24));

        } catch (\Exception $e) {
            // Fallback: return null if URL generation fails
            Log::error('Thumbnail URL generation failed: ' . $e->getMessage());
            return null;
        }
    }
}
