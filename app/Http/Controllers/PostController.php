<?php

namespace App\Http\Controllers;

use App\Http\Requests\Post\CreatePostRequest;
use App\Http\Resources\PostResource;
use App\Models\Post;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Knuckles\Scribe\Attributes\Group;
use Spatie\QueryBuilder\QueryBuilder;

#[Group("Posts", "APIs for managing blog posts")]
class PostController extends Controller
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
     * Display a listing of the resource.
     * use Spatie query builder with filters, sorts, and includes
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * 
     */
    public function index(Request $request)
    {
        // Use Spatie Query Builder to handle filtering, sorting, and includes
        try {
            $posts = QueryBuilder::for(\App\Models\Post::class)
                ->allowedFilters(['title', 'status', 'tags'])
                ->allowedSorts(['title', 'published_at'])
                ->allowedIncludes(['user'])
                ->paginate(10);

            return response()->json($posts);
        } catch (\Throwable $th) {
            // Log the error for debugging
            Log::error('Error fetching posts: ' . $th->getMessage());

            return response()->json([
                'message' => 'Failed to fetch posts',
                'error' => $th->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            $post = \App\Models\Post::with('user')->findOrFail($id);
            return response()->json(new PostResource($post));
        } catch (\Throwable $th) {
            Log::error('Error fetching post: ' . $th->getMessage());
            return response()->json([
                'message' => 'Post not found',
                'error' => $th->getMessage(),
            ], 404);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param CreatePostRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(CreatePostRequest $request)
    {
        $thumbnailPath = null;

        try {
            // Handle thumbnail upload if provided (before DB transaction)
            if ($request->hasFile('thumbnail')) {
                $thumbnailFile = $request->file('thumbnail');

                // Generate unique filename
                $filename = time() . '_' . Str::random(10) . '.' . $thumbnailFile->getClientOriginalExtension();

                // Upload to S3/MinIO
                $thumbnailPath = $this->storage->putFileAs('thumbnails', $thumbnailFile, $filename);

                // Check if upload was successful
                if (!$thumbnailPath) {
                    throw new \Exception('Thumbnail upload failed');
                }
            }

            // Wrap post creation in a database transaction
            $result = DB::transaction(function () use ($request, $thumbnailPath) {
                // Create a new post instance
                $post = Post::create([
                    'title' => $request->title,
                    'content' => $request->content,
                    'user_id' => Auth::id(),
                    'slug' => $request->slug,
                    'tags' => $request->tags ? json_encode($request->tags) : null, // Convert tags to JSON
                    'published_at' => $request->status === 'published' ? now() : null,
                    'status' => $request->status,
                    'thumbnail' => $thumbnailPath, // Store the file path
                ]);

                return $post;
            });

            return response()->json(new PostResource($result), 201);
        } catch (\Throwable $th) {
            // Clean up uploaded file if anything fails
            if ($thumbnailPath) {
                $this->storage->delete($thumbnailPath);
            }

            // Handle any exceptions that occur during post creation
            return response()->json(['message' => 'Post creation failed', 'error' => $th->getMessage()], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param CreatePostRequest $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(CreatePostRequest $request, $id)
    {
        $thumbnailPath = null;
        try {
            // Find the post to update
            $post = Post::findOrFail($id);

            // Handle thumbnail upload if provided
            if ($request->hasFile('thumbnail')) {
                $thumbnailFile = $request->file('thumbnail');
                // Generate unique filename
                $filename = time() . '_' . Str::random(10) . '.' . $thumbnailFile->getClientOriginalExtension();
                // Upload to S3/MinIO
                $thumbnailPath = $this->storage->putFileAs('thumbnails', $thumbnailFile, $filename);
                // Check if upload was successful
                if (!$thumbnailPath) {
                    throw new \Exception('Thumbnail upload failed');
                }

                // If a new thumbnail is uploaded, delete the old one
                if ($post->thumbnail) {
                    $this->storage->delete($post->thumbnail);

                    $post->thumbnail = $thumbnailPath;
                    $post->saveQuietly();
                }
            }
            // Update the post attributes
            $post->title = $request->title;
            $post->content = $request->content;
            $post->slug = $request->slug;
            $post->tags = $request->tags ? json_encode($request->tags) : null; // Convert tags to JSON
            $post->status = $request->status;
            $post->published_at = $request->status === 'published' ? now() : null;
            $post->save();

            return response()->json(new PostResource($post), 200);
        } catch (\Throwable $th) {
            // Clean up uploaded file if anything fails
            if ($thumbnailPath) {
                $this->storage->delete($thumbnailPath);
            }
            // Handle any exceptions that occur during post update
            return response()->json(['message' => 'Post update failed', 'error' => $th->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            $post = Post::findOrFail($id);

            // Delete the thumbnail if it exists
            if ($post->thumbnail) {
                $this->storage->delete($post->thumbnail);
            }

            $post->delete();

            return response()->json(['message' => 'Post deleted successfully'], 200);
        } catch (\Throwable $th) {
            Log::error('Error deleting post: ' . $th->getMessage());
            return response()->json(['message' => 'Post deletion failed', 'error' => $th->getMessage()], 500);
        }
    }

    /**
     * Publish or archive a post.
     * This method updates the status of a post to either 'published' or 'archived'.
     * @param int $id
     * @param Request $request
     * @bodyParam status string required The new status for the post, either 'published' or 'archived'.
     * @return \Illuminate\Http\JsonResponse
     */
    public function publishOrArchive($id, Request $request)
    {
        try {
            $post = Post::findOrFail($id);

            // Validate the request to ensure status is either 'published' or 'archived'
            $request->validate([
                'status' => 'required|in:published,archived',
            ]);
            // Update the post status
            $post->status = $request->status;
            $post->published_at = $request->status === 'published' ? now() : null; // Set published_at to now if status is 'published'
            $post->save();

            return response()->json(new PostResource($post), 200);
        } catch (\Throwable $th) {
            Log::error('Error updating post status: ' . $th->getMessage());
            return response()->json(['message' => 'Post status update failed', 'error' => $th->getMessage()], 500);
        }
    }
}
