<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    private User $coach;
    private User $student;
    private Course $course;

    protected function setUp(): void
    {
        parent::setUp();

        $this->coach = User::factory()->coach()->create();
        $this->student = User::factory()->student()->create();
        $this->course = Course::factory()->published()->create(['user_id' => $this->coach->id]);
    }

    public function test_completed_student_can_post_review(): void
    {
        $this->enroll($this->student, completed: true);

        $response = $this->actingAs($this->student)->post($this->reviewUrl(), [
            'rating' => 4,
            'comment' => 'わかりやすいコースでした。',
        ]);

        $response->assertRedirect(route('courses.show', $this->course));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('reviews', [
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'rating' => 4,
            'comment' => 'わかりやすいコースでした。',
        ]);
    }

    public function test_completed_student_can_post_review_without_comment(): void
    {
        $this->enroll($this->student, completed: true);

        $this->actingAs($this->student)
            ->post($this->reviewUrl(), ['rating' => 5])
            ->assertRedirect(route('courses.show', $this->course));

        $this->assertDatabaseHas('reviews', [
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'rating' => 5,
            'comment' => null,
        ]);
    }

    public function test_active_student_cannot_post_review(): void
    {
        $this->enroll($this->student, completed: false);

        $this->actingAs($this->student)
            ->post($this->reviewUrl(), ['rating' => 4])
            ->assertStatus(403);

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_not_enrolled_student_cannot_post_review(): void
    {
        $this->actingAs($this->student)
            ->post($this->reviewUrl(), ['rating' => 4])
            ->assertStatus(403);

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_student_cannot_post_second_review_for_same_course(): void
    {
        $this->enroll($this->student, completed: true);
        Review::factory()->create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
        ]);

        $this->actingAs($this->student)
            ->post($this->reviewUrl(), ['rating' => 3])
            ->assertStatus(403);

        $this->assertDatabaseCount('reviews', 1);
    }

    public function test_coach_and_admin_cannot_post_review(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($this->coach)
            ->post($this->reviewUrl(), ['rating' => 4])
            ->assertStatus(403);

        $this->actingAs($admin)
            ->post($this->reviewUrl(), ['rating' => 4])
            ->assertStatus(403);

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_rating_must_be_between_1_and_5(): void
    {
        $this->enroll($this->student, completed: true);

        foreach ([['rating' => 0], ['rating' => 6], []] as $data) {
            $this->actingAs($this->student)
                ->post($this->reviewUrl(), $data)
                ->assertSessionHasErrors('rating');
        }

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_review_form_is_shown_only_to_completed_student(): void
    {
        $this->enroll($this->student, completed: true);
        $admin = User::factory()->admin()->create();

        $this->actingAs($this->student)
            ->get(route('courses.show', $this->course))
            ->assertSee('レビューを投稿する');

        $this->actingAs($this->coach)
            ->get(route('courses.show', $this->course))
            ->assertDontSee('レビューを投稿する');

        $this->actingAs($admin)
            ->get(route('courses.show', $this->course))
            ->assertDontSee('レビューを投稿する');
    }

    public function test_reviews_are_listed_for_all_roles(): void
    {
        $reviewer = User::factory()->student()->create(['name' => 'レビュー太郎']);
        Review::factory()->create([
            'user_id' => $reviewer->id,
            'course_id' => $this->course->id,
            'rating' => 5,
            'comment' => 'とても良いコースです',
        ]);
        $admin = User::factory()->admin()->create();

        foreach ([$this->student, $this->coach, $admin] as $user) {
            $this->actingAs($user)
                ->get(route('courses.show', $this->course))
                ->assertStatus(200)
                ->assertSee('レビュー太郎')
                ->assertSee('とても良いコースです');
        }
    }

    private function reviewUrl(): string
    {
        return route('courses.reviews.store', $this->course);
    }

    private function enroll(User $user, bool $completed): Enrollment
    {
        $factory = Enrollment::factory();

        return ($completed ? $factory->completed() : $factory)->create([
            'user_id' => $user->id,
            'course_id' => $this->course->id,
        ]);
    }
}
