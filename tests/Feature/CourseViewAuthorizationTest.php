<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseViewAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private User $coach;
    private User $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->coach = User::factory()->coach()->create();
        $this->student = User::factory()->student()->create();
    }

    public function test_admin_can_view_draft_course(): void
    {
        $admin = User::factory()->admin()->create();
        $course = Course::factory()->draft()->create(['user_id' => $this->coach->id]);

        $response = $this->actingAs($admin)->get("/courses/{$course->id}");

        $response->assertStatus(200);
        $response->assertSee($course->title);
    }

    public function test_coach_can_view_own_draft_course(): void
    {
        $course = Course::factory()->draft()->create(['user_id' => $this->coach->id]);

        $response = $this->actingAs($this->coach)->get("/courses/{$course->id}");

        $response->assertStatus(200);
        $response->assertSee($course->title);
    }

    public function test_student_can_view_published_course(): void
    {
        $course = Course::factory()->published()->create(['user_id' => $this->coach->id]);

        $response = $this->actingAs($this->student)->get("/courses/{$course->id}");

        $response->assertStatus(200);
        $response->assertSee($course->title);
    }

    public function test_student_cannot_view_draft_course(): void
    {
        $course = Course::factory()->draft()->create(['user_id' => $this->coach->id]);

        $response = $this->actingAs($this->student)->get("/courses/{$course->id}");

        $response->assertStatus(403);
    }

    public function test_student_cannot_view_archived_course(): void
    {
        $course = Course::factory()->archived()->create(['user_id' => $this->coach->id]);

        $response = $this->actingAs($this->student)->get("/courses/{$course->id}");

        $response->assertStatus(403);
    }
}
