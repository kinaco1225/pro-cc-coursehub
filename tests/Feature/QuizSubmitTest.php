<?php

namespace Tests\Feature;

use App\Models\Chapter;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Option;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuizSubmitTest extends TestCase
{
    use RefreshDatabase;

    private User $student;
    private Course $course;
    private Quiz $quiz;
    private Question $question1;
    private Question $question2;
    private Option $correctOption1;

    protected function setUp(): void
    {
        parent::setUp();

        $this->student = User::factory()->student()->create();
        $this->course = Course::factory()->published()->create();
        $chapter = Chapter::factory()->create(['course_id' => $this->course->id]);
        $lesson = Lesson::factory()->create(['chapter_id' => $chapter->id]);
        $this->quiz = Quiz::factory()->create(['lesson_id' => $lesson->id]);

        $this->question1 = Question::factory()->create(['quiz_id' => $this->quiz->id, 'order' => 1]);
        $this->correctOption1 = Option::factory()->correct()->create(['question_id' => $this->question1->id]);
        Option::factory()->create(['question_id' => $this->question1->id]);

        $this->question2 = Question::factory()->create(['quiz_id' => $this->quiz->id, 'order' => 2]);
        Option::factory()->correct()->create(['question_id' => $this->question2->id]);
        Option::factory()->create(['question_id' => $this->question2->id]);
    }

    public function test_submitting_with_unanswered_question_redirects_to_result(): void
    {
        $response = $this->actingAs($this->student)->post(
            "/courses/{$this->course->id}/quizzes/{$this->quiz->id}/submit",
            ['answers' => $this->answersWithSecondQuestionUnanswered()]
        );

        $response->assertRedirect(route('courses.quizzes.result', [$this->course, $this->quiz]));
    }

    public function test_unanswered_question_is_scored_as_incorrect(): void
    {
        $this->actingAs($this->student)->post(
            "/courses/{$this->course->id}/quizzes/{$this->quiz->id}/submit",
            ['answers' => $this->answersWithSecondQuestionUnanswered()]
        );

        // 2問中1問正解・1問未回答 = 50点
        $this->assertDatabaseHas('submissions', [
            'user_id' => $this->student->id,
            'quiz_id' => $this->quiz->id,
            'score' => 50,
        ]);
    }

    private function answersWithSecondQuestionUnanswered(): array
    {
        // 未回答の問題はラジオボタンが送信されず、hidden の question_id だけが届く
        return [
            ['question_id' => $this->question1->id, 'option_id' => $this->correctOption1->id],
            ['question_id' => $this->question2->id],
        ];
    }
}
