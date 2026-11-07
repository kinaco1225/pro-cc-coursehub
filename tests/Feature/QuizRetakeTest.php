<?php

namespace Tests\Feature;

use App\Models\Chapter;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Option;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuizRetakeTest extends TestCase
{
    use RefreshDatabase;

    private User $student;
    private Course $course;
    private Quiz $quiz;
    private Question $question;
    private Option $correctOption;
    private Option $wrongOption;

    protected function setUp(): void
    {
        parent::setUp();

        $this->student = User::factory()->student()->create();
        $this->course = Course::factory()->published()->create();
        $chapter = Chapter::factory()->create(['course_id' => $this->course->id]);
        $lesson = Lesson::factory()->create(['chapter_id' => $chapter->id]);
        $this->quiz = Quiz::factory()->create(['lesson_id' => $lesson->id, 'passing_score' => 70]);

        $this->question = Question::factory()->create(['quiz_id' => $this->quiz->id]);
        $this->correctOption = Option::factory()->correct()->create(['question_id' => $this->question->id]);
        $this->wrongOption = Option::factory()->create(['question_id' => $this->question->id]);
    }

    public function test_student_can_retake_quiz_after_failing(): void
    {
        $this->createSubmission(0, now()->subHour());

        $this->actingAs($this->student)
            ->get($this->quizUrl())
            ->assertStatus(200);

        $this->actingAs($this->student)
            ->post($this->quizUrl() . '/submit', $this->answers($this->correctOption))
            ->assertRedirect($this->quizUrl() . '/result');

        $this->assertSame(2, Submission::where('user_id', $this->student->id)->count());
    }

    public function test_student_cannot_submit_quiz_after_passing(): void
    {
        $this->createSubmission(100, now()->subHour());

        $this->actingAs($this->student)
            ->post($this->quizUrl() . '/submit', $this->answers($this->wrongOption))
            ->assertStatus(403);

        $this->assertSame(1, Submission::where('user_id', $this->student->id)->count());
    }

    public function test_quiz_page_redirects_to_result_after_passing(): void
    {
        $this->createSubmission(100, now()->subHour());

        $this->actingAs($this->student)
            ->get($this->quizUrl())
            ->assertRedirect($this->quizUrl() . '/result');
    }

    public function test_student_cannot_retake_after_passing_on_retake(): void
    {
        $this->createSubmission(0, now()->subHours(2));
        $this->createSubmission(100, now()->subHour());

        $this->actingAs($this->student)
            ->post($this->quizUrl() . '/submit', $this->answers($this->correctOption))
            ->assertStatus(403);
    }

    public function test_result_page_shows_submission_history(): void
    {
        $this->createSubmission(0, now()->setDate(2026, 11, 1)->setTime(10, 0));
        $this->createSubmission(50, now()->setDate(2026, 11, 2)->setTime(11, 30));

        $response = $this->actingAs($this->student)->get($this->quizUrl() . '/result');

        $response->assertStatus(200);
        $response->assertSee('受験履歴');
        $response->assertSeeInOrder(['2026/11/02 11:30', '50%', '2026/11/01 10:00', '0%']);
    }

    public function test_result_page_shows_retake_button_when_failed(): void
    {
        $this->createSubmission(0, now());

        $this->actingAs($this->student)
            ->get($this->quizUrl() . '/result')
            ->assertSee('再受験する');
    }

    public function test_result_page_hides_retake_button_when_passed(): void
    {
        $this->createSubmission(0, now()->subHour());
        $this->createSubmission(100, now());

        $this->actingAs($this->student)
            ->get($this->quizUrl() . '/result')
            ->assertDontSee('再受験する');
    }

    private function quizUrl(): string
    {
        return "/courses/{$this->course->id}/quizzes/{$this->quiz->id}";
    }

    private function answers(Option $option): array
    {
        return [
            'answers' => [
                ['question_id' => $this->question->id, 'option_id' => $option->id],
            ],
        ];
    }

    private function createSubmission(int $score, $submittedAt): Submission
    {
        return Submission::factory()->create([
            'user_id' => $this->student->id,
            'quiz_id' => $this->quiz->id,
            'score' => $score,
            'submitted_at' => $submittedAt,
        ]);
    }
}
