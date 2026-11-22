<?php

namespace App\Policies;

use App\Models\Student;
use App\Models\User;

class StudentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isPeneliti() || $user->isGuru();
    }

    public function view(User $user, Student $student): bool
    {
        if ($user->isPeneliti()) {
            return true;
        }

        return $user->isGuru() && (int) $user->school_id === (int) $student->school_id;
    }

    public function create(User $user): bool
    {
        return $user->isPeneliti() || $user->isGuru();
    }

    public function update(User $user, Student $student): bool
    {
        if ($user->isPeneliti()) {
            return true;
        }

        return $user->isGuru() && (int) $user->school_id === (int) $student->school_id;
    }

    public function delete(User $user, Student $student): bool
    {
        if ($user->isPeneliti()) {
            return true;
        }

        return $user->isGuru() && (int) $user->school_id === (int) $student->school_id;
    }

    public function resetPin(User $user, Student $student): bool
    {
        return $this->update($user, $student);
    }

    public function unlock(User $user, Student $student): bool
    {
        return $this->update($user, $student);
    }

    public function markConsent(User $user, Student $student): bool
    {
        return $this->update($user, $student);
    }

    public function export(User $user): bool
    {
        return $user->isPeneliti() || $user->isGuru();
    }
}
