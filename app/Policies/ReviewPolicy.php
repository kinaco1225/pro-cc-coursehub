<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\User;

class ReviewPolicy
{
    /**
     * コースにレビューを投稿できるか
     *
     * 受講完了（Enrollment status=completed）した student のみ、1コースにつき1件まで
     */
    public function create(User $user, Course $course): bool
    {
        if ($user->role !== 'student') {
            return false;
        }

        $hasCompleted = $user->enrollments()
            ->where('course_id', $course->id)
            ->where('status', 'completed')
            ->exists();

        $alreadyReviewed = $course->reviews()
            ->where('user_id', $user->id)
            ->exists();

        return $hasCompleted && ! $alreadyReviewed;
    }
}
