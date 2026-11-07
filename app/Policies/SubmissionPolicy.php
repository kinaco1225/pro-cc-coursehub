<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Quiz;
use App\Models\User;

class SubmissionPolicy
{
    public function submit(User $user, Quiz $quiz, Course $course): bool
    {
        if ($user->role !== 'student') {
            return false;
        }

        return Enrollment::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->where('status', 'active')
            ->exists();
    }

    /**
     * 小テストを受験（Submission を作成）できるか
     *
     * 合格済み（score >= passing_score）の Submission がある場合は再受験不可
     */
    public function create(User $user, Quiz $quiz): bool
    {
        return ! $quiz->submissions()
            ->where('user_id', $user->id)
            ->where('score', '>=', $quiz->passing_score)
            ->exists();
    }
}
