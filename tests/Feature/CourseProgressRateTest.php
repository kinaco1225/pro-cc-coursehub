<?php

namespace Tests\Feature;

use App\Models\Chapter;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseProgressRateTest extends TestCase
{
    use RefreshDatabase;

    private User $student;
    private Course $course;

    protected function setUp(): void
    {
        parent::setUp();

        $this->student = User::factory()->student()->create();
        $this->course = Course::factory()->published()->create();
    }

    public function test_progress_is_100_percent_when_all_published_lessons_are_completed_even_with_unpublished_lessons(): void
    {
        $chapter1 = Chapter::factory()->create(['course_id' => $this->course->id, 'order' => 1]);
        $chapter2 = Chapter::factory()->create(['course_id' => $this->course->id, 'order' => 2]);

        $publishedLessons = collect([
            Lesson::factory()->create(['chapter_id' => $chapter1->id, 'order' => 1]),
            Lesson::factory()->create(['chapter_id' => $chapter1->id, 'order' => 2]),
            Lesson::factory()->create(['chapter_id' => $chapter2->id, 'order' => 1]),
        ]);
        Lesson::factory()->unpublished()->create(['chapter_id' => $chapter2->id, 'order' => 2]);

        foreach ($publishedLessons as $lesson) {
            $this->completeLesson($lesson);
        }

        $this->assertSame(100, $this->course->getProgressRate($this->student->id));
    }

    public function test_progress_is_calculated_from_published_lessons_when_partially_completed(): void
    {
        $chapter = Chapter::factory()->create(['course_id' => $this->course->id]);

        $publishedLessons = Lesson::factory()->count(4)->create(['chapter_id' => $chapter->id]);
        Lesson::factory()->unpublished()->create(['chapter_id' => $chapter->id]);

        $this->completeLesson($publishedLessons[0]);
        LessonProgress::factory()->create([
            'user_id' => $this->student->id,
            'lesson_id' => $publishedLessons[1]->id,
            'status' => 'not_started',
            'completed_at' => null,
        ]);

        // 公開 4 件中 1 件完了 = 25%（非公開を分母に含めると 20% になる）
        $this->assertSame(25, $this->course->getProgressRate($this->student->id));
    }

    public function test_progress_is_0_percent_when_course_has_no_lessons(): void
    {
        Chapter::factory()->create(['course_id' => $this->course->id]);

        $this->assertSame(0, $this->course->getProgressRate($this->student->id));
    }

    private function completeLesson(Lesson $lesson): void
    {
        LessonProgress::factory()->create([
            'user_id' => $this->student->id,
            'lesson_id' => $lesson->id,
            'status' => 'completed',
        ]);
    }
}
