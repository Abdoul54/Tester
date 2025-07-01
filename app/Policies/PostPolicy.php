<?php

namespace App\Policies;

use App\Models\Post;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PostPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user)
    {
        // Anyone can view posts list (you might want to restrict this)
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Post $post)
    {
        // Anyone can view published posts, only owner/admin can view drafts
        if ($post->status === 'published') {
            return true;
        }

        // Owner can view their own drafts
        if ($post->user_id === $user->id) {
            return true;
        }

        // Admin can view all posts (if using Spatie roles)
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user)
    {
        // All authenticated users can create posts
        // Or restrict to specific roles: return $user->hasAnyRole(['admin', 'author']);
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Post $post)
    {
        // Owner can update their own posts
        if ($post->user_id === $user->id) {
            return true;
        }

        // Admin can update any post
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Post $post)
    {
        // Owner can delete their own posts
        if ($post->user_id === $user->id) {
            return true;
        }

        // Admin can delete any post
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can publish or archive the model.
     */
    public function publishOrArchive(User $user, Post $post)
    {
        // Owner can publish/archive their own posts
        if ($post->user_id === $user->id) {
            return true;
        }

        // Admin can publish/archive any post
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Post $post)
    {
        return $this->delete($user, $post);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Post $post)
    {
        // Only admin can permanently delete
        return $user->hasRole('admin');
    }
}
