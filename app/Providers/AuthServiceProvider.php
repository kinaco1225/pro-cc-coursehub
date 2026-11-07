<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        \App\Models\Course::class => \App\Policies\CoursePolicy::class,
        \App\Models\Chapter::class => \App\Policies\ChapterPolicy::class,
        \App\Models\Lesson::class => \App\Policies\LessonPolicy::class,
        \App\Models\Quiz::class => \App\Policies\QuizPolicy::class,
        \App\Models\Submission::class => \App\Policies\SubmissionPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
