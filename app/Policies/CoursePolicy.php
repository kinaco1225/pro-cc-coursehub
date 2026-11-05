<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\User;

class CoursePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Course $course): bool
    {
        if ($user->role === 'admin' || $course->user_id === $user->id) {
            return true;
        }

        return $course->status === 'published';
    }

    public function create(User $user): bool
    {
        return $user->role === 'coach';
    }

    public function update(User $user, Course $course): bool
    {
        return $user->id === $course->user_id;
    }

    public function delete(User $user, Course $course): bool
    {
        return $user->id === $course->user_id;
    }
}
