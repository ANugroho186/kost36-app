<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;

class UserPolicy
{
    /**
     * Pemilik boleh mereset password akun penghuni (misalnya yang tidak punya email),
     * tapi tidak boleh mereset akun pemilik lain lewat jalur ini.
     */
    public function resetPassword(User $user, User $target): bool
    {
        return $user->isOwner() && $target->role === UserRole::Tenant;
    }
}
