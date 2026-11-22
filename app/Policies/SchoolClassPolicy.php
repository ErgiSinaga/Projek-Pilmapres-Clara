<?php

namespace App\Policies;

use App\Models\SchoolClass;
use App\Models\User;

class SchoolClassPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isPeneliti() || $user->isGuru();
    }

    public function view(User $user, SchoolClass $class): bool
    {
        if ($user->isPeneliti()) {
            return true;
        }

        return $user->isGuru() && (int) $user->school_id === (int) $class->school_id;
    }

    public function create(User $user): bool
    {
        return $user->isPeneliti();
    }

    public function update(User $user, SchoolClass $class): bool
    {
        return $user->isPeneliti();
    }

    public function delete(User $user, SchoolClass $class): bool
    {
        return $user->isPeneliti();
    }
}
