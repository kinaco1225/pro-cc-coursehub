<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Course;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CourseTest extends TestCase
{
    use RefreshDatabase;

    private User $coach;
    private User $student;
    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->coach = User::factory()->create(['role' => 'coach']);
        $this->student = User::factory()->create(['role' => 'student']);
        $this->category = Category::factory()->create();
    }

    public function test_student_can_view_course_list(): void
    {
        $course = Course::factory()->create([
            'user_id' => $this->coach->id,
            'category_id' => $this->category->id,
            'status' => 'published',
        ]);

        $response = $this->actingAs($this->student)->get('/courses');

        $response->assertStatus(200);
        $response->assertSee($course->title);
    }

    public function test_student_can_view_published_course(): void
    {
        $course = Course::factory()->create([
            'user_id' => $this->coach->id,
            'category_id' => $this->category->id,
            'status' => 'published',
        ]);

        $response = $this->actingAs($this->student)->get("/courses/{$course->id}");

        $response->assertStatus(200);
        $response->assertSee($course->title);
    }

    public function test_coach_can_create_course(): void
    {
        $tags = Tag::factory()->count(2)->create();

        $response = $this->actingAs($this->coach)->post('/coach/courses', [
            'title' => 'テストコース',
            'category_id' => $this->category->id,
            'description' => 'テストコースの説明文です。',
            'difficulty' => 'beginner',
            'status' => 'draft',
            'tags' => $tags->pluck('id')->toArray(),
        ]);

        $response->assertRedirect('/coach/courses');
        $this->assertDatabaseHas('courses', [
            'title' => 'テストコース',
            'user_id' => $this->coach->id,
        ]);
    }

    public function test_coach_can_create_course_with_image(): void
    {
        Storage::fake('public');

        $response = $this->actingAs($this->coach)->post('/coach/courses', [
            'title' => '画像付きコース',
            'category_id' => $this->category->id,
            'description' => '画像付きコースの説明文です。',
            'difficulty' => 'beginner',
            'status' => 'draft',
            'image' => UploadedFile::fake()->image('cover.jpg', 640, 360),
        ]);

        $response->assertRedirect('/coach/courses');

        $course = Course::where('title', '画像付きコース')->firstOrFail();
        $this->assertNotNull($course->image_path);
        $this->assertMatchesRegularExpression('#^courses/\d+_[A-Za-z0-9]{10}\.jpg$#', $course->image_path);
        Storage::disk('public')->assertExists($course->image_path);
    }

    public function test_published_course_has_published_at(): void
    {
        $response = $this->actingAs($this->coach)->post('/coach/courses', [
            'title' => '公開コース',
            'category_id' => $this->category->id,
            'description' => '公開コースの説明文です。',
            'difficulty' => 'intermediate',
            'status' => 'published',
        ]);

        $response->assertRedirect('/coach/courses');

        $course = Course::where('title', '公開コース')->firstOrFail();
        $this->assertSame('published', $course->status);
        $this->assertNotNull($course->published_at);
    }

    public function test_draft_course_has_no_published_at(): void
    {
        $this->actingAs($this->coach)->post('/coach/courses', [
            'title' => '下書きコース',
            'category_id' => $this->category->id,
            'description' => '下書きコースの説明文です。',
            'difficulty' => 'beginner',
            'status' => 'draft',
        ]);

        $course = Course::where('title', '下書きコース')->firstOrFail();
        $this->assertNull($course->published_at);
    }

    public function test_existing_tags_are_attached_on_course_creation(): void
    {
        $tags = Tag::factory()->count(2)->create();
        $unselectedTag = Tag::factory()->create();

        $this->actingAs($this->coach)->post('/coach/courses', [
            'title' => 'タグ付きコース',
            'category_id' => $this->category->id,
            'description' => 'タグ付きコースの説明文です。',
            'difficulty' => 'beginner',
            'status' => 'draft',
            'tags' => $tags->pluck('id')->toArray(),
        ]);

        $course = Course::where('title', 'タグ付きコース')->firstOrFail();
        $this->assertEqualsCanonicalizing(
            $tags->pluck('id')->toArray(),
            $course->tags()->pluck('tags.id')->toArray()
        );
        $this->assertDatabaseMissing('course_tag', [
            'course_id' => $course->id,
            'tag_id' => $unselectedTag->id,
        ]);
    }

    public function test_new_tags_are_created_and_attached_on_course_creation(): void
    {
        $selectedTag = Tag::factory()->create(['name' => 'Laravel', 'slug' => 'laravel']);
        $existingTag = Tag::factory()->create(['name' => 'PHP', 'slug' => 'php']);

        $this->actingAs($this->coach)->post('/coach/courses', [
            'title' => '新規タグ付きコース',
            'category_id' => $this->category->id,
            'description' => '新規タグ付きコースの説明文です。',
            'difficulty' => 'beginner',
            'status' => 'draft',
            'tags' => [$selectedTag->id],
            'new_tags' => 'php, Docker, , Docker, laravel',
        ]);

        $course = Course::where('title', '新規タグ付きコース')->firstOrFail();

        // 既存 slug のタグは再利用され、新規タグは1件だけ作成される
        $this->assertSame(3, Tag::count());
        $dockerTag = Tag::where('slug', 'docker')->firstOrFail();
        $this->assertSame('Docker', $dockerTag->name);

        // 選択タグ + 新規タグが重複なく紐付く
        $this->assertEqualsCanonicalizing(
            [$selectedTag->id, $existingTag->id, $dockerTag->id],
            $course->tags()->pluck('tags.id')->toArray()
        );
    }

    public function test_initial_chapter_is_created_on_course_creation(): void
    {
        $this->actingAs($this->coach)->post('/coach/courses', [
            'title' => 'チャプター自動作成コース',
            'category_id' => $this->category->id,
            'description' => 'チャプター自動作成の確認用コースです。',
            'difficulty' => 'beginner',
            'status' => 'draft',
        ]);

        $course = Course::where('title', 'チャプター自動作成コース')->firstOrFail();
        $this->assertSame(1, $course->chapters()->count());
        $this->assertDatabaseHas('chapters', [
            'course_id' => $course->id,
            'title' => 'はじめに',
            'order' => 1,
        ]);
    }

    public function test_course_creation_requires_title(): void
    {
        $response = $this->actingAs($this->coach)->post('/coach/courses', [
            'category_id' => $this->category->id,
            'description' => 'タイトルなしのコースです。',
            'difficulty' => 'beginner',
            'status' => 'draft',
        ]);

        $response->assertSessionHasErrors('title');
        $this->assertDatabaseCount('courses', 0);
    }

    public function test_coach_cannot_create_course_with_duplicate_title(): void
    {
        Course::factory()->create([
            'user_id' => $this->coach->id,
            'category_id' => $this->category->id,
            'title' => '重複タイトル',
        ]);

        $response = $this->actingAs($this->coach)->post('/coach/courses', [
            'title' => '重複タイトル',
            'category_id' => $this->category->id,
            'description' => '重複タイトルのコースです。',
            'difficulty' => 'beginner',
            'status' => 'draft',
        ]);

        $response->assertSessionHasErrors(['title' => '同じタイトルのコースが既に存在します。']);
        $this->assertSame(1, Course::where('title', '重複タイトル')->count());
    }

    public function test_coach_can_create_course_with_same_title_as_other_coach(): void
    {
        $otherCoach = User::factory()->create(['role' => 'coach']);
        Course::factory()->create([
            'user_id' => $otherCoach->id,
            'category_id' => $this->category->id,
            'title' => '共通タイトル',
        ]);

        $response = $this->actingAs($this->coach)->post('/coach/courses', [
            'title' => '共通タイトル',
            'category_id' => $this->category->id,
            'description' => '他のコーチと同じタイトルのコースです。',
            'difficulty' => 'beginner',
            'status' => 'draft',
        ]);

        $response->assertRedirect('/coach/courses');
        $this->assertDatabaseHas('courses', [
            'title' => '共通タイトル',
            'user_id' => $this->coach->id,
        ]);
    }

    public function test_slug_is_generated_from_title(): void
    {
        $this->actingAs($this->coach)->post('/coach/courses', [
            'title' => 'Laravel Basics',
            'category_id' => $this->category->id,
            'description' => 'スラッグ生成の確認用コースです。',
            'difficulty' => 'beginner',
            'status' => 'draft',
        ]);

        $this->assertDatabaseHas('courses', [
            'title' => 'Laravel Basics',
            'slug' => 'laravel-basics',
        ]);
    }

    public function test_slug_gets_suffix_when_already_taken(): void
    {
        Course::factory()->create([
            'category_id' => $this->category->id,
            'title' => '既存コース',
            'slug' => 'laravel-basics',
        ]);

        $this->actingAs($this->coach)->post('/coach/courses', [
            'title' => 'Laravel Basics',
            'category_id' => $this->category->id,
            'description' => 'スラッグ重複の確認用コースです。',
            'difficulty' => 'beginner',
            'status' => 'draft',
        ]);

        $this->assertDatabaseHas('courses', [
            'title' => 'Laravel Basics',
            'slug' => 'laravel-basics-1',
        ]);
    }

    public function test_slug_falls_back_when_title_has_no_ascii(): void
    {
        $this->actingAs($this->coach)->post('/coach/courses', [
            'title' => 'プログラミング入門',
            'category_id' => $this->category->id,
            'description' => '日本語タイトルのスラッグ確認用コースです。',
            'difficulty' => 'beginner',
            'status' => 'draft',
        ]);

        $course = Course::where('title', 'プログラミング入門')->firstOrFail();
        $this->assertMatchesRegularExpression('/^course-\d+$/', $course->slug);
    }

    public function test_coach_can_update_course(): void
    {
        $course = Course::factory()->create([
            'user_id' => $this->coach->id,
            'category_id' => $this->category->id,
        ]);

        $response = $this->actingAs($this->coach)->put("/coach/courses/{$course->id}", [
            'title' => '更新されたタイトル',
            'category_id' => $this->category->id,
            'description' => '更新された説明文です。',
            'difficulty' => 'intermediate',
            'status' => 'published',
        ]);

        $response->assertRedirect('/coach/courses');
        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'title' => '更新されたタイトル',
        ]);
    }

    public function test_coach_can_delete_own_course(): void
    {
        $course = Course::factory()->create([
            'user_id' => $this->coach->id,
            'category_id' => $this->category->id,
        ]);

        $response = $this->actingAs($this->coach)->delete("/coach/courses/{$course->id}");

        $response->assertRedirect('/coach/courses');
        $this->assertDatabaseMissing('courses', ['id' => $course->id]);
    }

    public function test_coach_cannot_delete_other_coaches_course(): void
    {
        $otherCoach = User::factory()->create(['role' => 'coach']);
        $course = Course::factory()->create([
            'user_id' => $otherCoach->id,
            'category_id' => $this->category->id,
        ]);

        $response = $this->actingAs($this->coach)->delete("/coach/courses/{$course->id}");

        $response->assertStatus(403);
    }

    public function test_student_cannot_create_course(): void
    {
        $response = $this->actingAs($this->student)->get('/coach/courses/create');

        $response->assertStatus(403);
    }

    public function test_course_list_can_be_filtered_by_category(): void
    {
        $otherCategory = Category::factory()->create();

        Course::factory()->create([
            'user_id' => $this->coach->id,
            'category_id' => $this->category->id,
            'status' => 'published',
            'title' => 'カテゴリAのコース',
        ]);

        Course::factory()->create([
            'user_id' => $this->coach->id,
            'category_id' => $otherCategory->id,
            'status' => 'published',
            'title' => 'カテゴリBのコース',
        ]);

        $response = $this->actingAs($this->student)
            ->get("/courses?category={$this->category->id}");

        $response->assertStatus(200);
        $response->assertSee('カテゴリAのコース');
        $response->assertDontSee('カテゴリBのコース');
    }

    public function test_course_list_can_be_searched(): void
    {
        Course::factory()->create([
            'user_id' => $this->coach->id,
            'category_id' => $this->category->id,
            'status' => 'published',
            'title' => 'Laravel入門',
        ]);

        Course::factory()->create([
            'user_id' => $this->coach->id,
            'category_id' => $this->category->id,
            'status' => 'published',
            'title' => 'React基礎',
        ]);

        $response = $this->actingAs($this->student)
            ->get('/courses?search=Laravel');

        $response->assertStatus(200);
        $response->assertSee('Laravel入門');
        $response->assertDontSee('React基礎');
    }
}
