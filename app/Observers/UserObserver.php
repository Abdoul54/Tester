<?php

namespace App\Observers;

use App\Models\User;
use App\Notifications\EmailVerificationNotification;

class UserObserver
{
    public function created(User $user): void
    {
        if ($user->email != "superadmin@example.com") {
            // Send email verification notification when user is created
            $user->notify(new EmailVerificationNotification());
        }
    }
}
