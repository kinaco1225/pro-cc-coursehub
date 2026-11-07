<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Review;
use App\Models\User;

/**
 * コースレビューに関するビジネスロジックを集約するサービスクラス
 *
 * 投稿可否の判定は ReviewPolicy が担当し、ここではレビューの作成を行う。
 */
class ReviewService
{
    public function create(User $user, Course $course, array $data): Review
    {
        return $course->reviews()->create([
            'user_id' => $user->id,
            'rating' => $data['rating'],
            'comment' => $data['comment'] ?? null,
        ]);
    }
}
