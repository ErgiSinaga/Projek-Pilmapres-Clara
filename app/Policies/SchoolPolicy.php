<?php

namespace App\Policies;

use App\Models\School;
use App\Models\User;

class SchoolPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isPeneliti();
    }

    public function view(User $user, School $school): bool
    {
        if ($user->isPeneliti()) {
            return true;
        }

        return $user->isGuru() && (int) $user->school_id === (int) $school->id;
    }

    public function create(User $user): bool
    {
        return $user->isPeneliti();
    }

    public function update(User $user, School $school): bool
    {
        return $user->isPeneliti();
    }

    public function delete(User $user, School $school): bool
    {
        return $user->isPeneliti();
    }
}
