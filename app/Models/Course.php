<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'category_id',
        'title',
        'slug',
        'description',
        'difficulty',
        'image_path',
        'status',
        'published_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function chapters(): HasMany
    {
        return $this->hasMany(Chapter::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'course_tag');
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function getAllLessonIds(): array
    {
        return Lesson::whereIn('chapter_id', $this->chapters()->pluck('id'))
            ->where('is_published', true)
            ->pluck('id')
            ->toArray();
    }

    public function getProgressRate($userId): int
    {
        $lessonIds = $this->getAllLessonIds();
        $totalLessons = count($lessonIds);

        $completedLessons = LessonProgress::where('user_id', $userId)
            ->whereIn('lesson_id', $lessonIds)
            ->where('status', 'completed')
            ->count();

        return $totalLessons > 0 ? (int) round($completedLessons / $totalLessons * 100) : 0;
    }
}
