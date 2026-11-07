<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReviewRequest;
use App\Models\Course;
use App\Models\Review;
use App\Services\ReviewService;

class ReviewController extends Controller
{
    public function __construct(
        private ReviewService $reviewService
    ) {}

    public function store(StoreReviewRequest $request, Course $course)
    {
        $this->authorize('create', [Review::class, $course]);

        $this->reviewService->create(auth()->user(), $course, $request->validated());

        return redirect()->route('courses.show', $course)
            ->with('success', 'レビューを投稿しました。');
    }
}
