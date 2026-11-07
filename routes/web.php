<?php

use App\Http\Controllers\AdminCourseController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AdminStudentController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ChapterController;
use App\Http\Controllers\CoachCourseController;
use App\Http\Controllers\CoachLessonController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\CourseStudentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\MyCourseController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\QuizManageController;
use App\Http\Controllers\ReviewController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return redirect()->route('login');
});

// Authenticated routes
Route::middleware('auth')->group(function () {

    // Dashboard redirect based on role
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/home', [DashboardController::class, 'index'])->name('home');

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

    // Student routes
    Route::middleware('role:student')->group(function () {
        Route::get('/my-courses', [MyCourseController::class, 'index'])->name('my-courses.index');
        Route::post('/courses/{course}/enroll', [EnrollmentController::class, 'store'])->name('courses.enroll');
        Route::post('/courses/{course}/lessons/{lesson}/complete', [LessonController::class, 'complete'])->name('courses.lessons.complete');
        Route::post('/courses/{course}/quizzes/{quiz}/submit', [QuizController::class, 'submit'])->name('courses.quizzes.submit');
        Route::get('/courses/{course}/quizzes/{quiz}/result', [QuizController::class, 'result'])->name('courses.quizzes.result');
        Route::post('/courses/{course}/reviews', [ReviewController::class, 'store'])->name('courses.reviews.store');
    });

    // Course browsing (student + coach + admin can view)
    Route::get('/courses', [CourseController::class, 'index'])->name('courses.index');
    Route::get('/courses/{course}', [CourseController::class, 'show'])->name('courses.show');
    Route::get('/courses/{course}/lessons/{lesson}', [LessonController::class, 'show'])->name('courses.lessons.show');
    Route::get('/courses/{course}/quizzes/{quiz}', [QuizController::class, 'show'])->name('courses.quizzes.show');

    // Coach routes
    Route::middleware('role:coach')->prefix('coach')->group(function () {
        Route::get('/', [CoachCourseController::class, 'dashboard'])->name('coach.dashboard');

        Route::get('/courses', [CoachCourseController::class, 'index'])->name('coach.courses.index');
        Route::get('/courses/create', [CoachCourseController::class, 'create'])->name('coach.courses.create');
        Route::post('/courses', [CoachCourseController::class, 'store'])->name('coach.courses.store');
        Route::get('/courses/{course}/edit', [CoachCourseController::class, 'edit'])->name('coach.courses.edit');
        Route::put('/courses/{course}', [CoachCourseController::class, 'update'])->name('coach.courses.update');
        Route::delete('/courses/{course}', [CoachCourseController::class, 'destroy'])->name('coach.courses.destroy');

        Route::get('/courses/{course}/chapters', [ChapterController::class, 'index'])->name('coach.courses.chapters.index');
        Route::get('/courses/{course}/chapters/create', [ChapterController::class, 'create'])->name('coach.courses.chapters.create');
        Route::post('/courses/{course}/chapters', [ChapterController::class, 'store'])->name('coach.courses.chapters.store');
        Route::get('/courses/{course}/chapters/{chapter}/edit', [ChapterController::class, 'edit'])->name('coach.courses.chapters.edit');
        Route::put('/courses/{course}/chapters/{chapter}', [ChapterController::class, 'update'])->name('coach.courses.chapters.update');
        Route::delete('/courses/{course}/chapters/{chapter}', [ChapterController::class, 'destroy'])->name('coach.courses.chapters.destroy');
        Route::post('/courses/{course}/chapters/order', [ChapterController::class, 'updateOrder'])->name('coach.courses.chapters.order');

        Route::get('/courses/{course}/chapters/{chapter}/lessons', [CoachLessonController::class, 'index'])->name('coach.courses.chapters.lessons.index');
        Route::get('/courses/{course}/chapters/{chapter}/lessons/create', [CoachLessonController::class, 'create'])->name('coach.courses.chapters.lessons.create');
        Route::post('/courses/{course}/chapters/{chapter}/lessons', [CoachLessonController::class, 'store'])->name('coach.courses.chapters.lessons.store');
        Route::get('/courses/{course}/chapters/{chapter}/lessons/{lesson}/edit', [CoachLessonController::class, 'edit'])->name('coach.courses.chapters.lessons.edit');
        Route::put('/courses/{course}/chapters/{chapter}/lessons/{lesson}', [CoachLessonController::class, 'update'])->name('coach.courses.chapters.lessons.update');
        Route::delete('/courses/{course}/chapters/{chapter}/lessons/{lesson}', [CoachLessonController::class, 'destroy'])->name('coach.courses.chapters.lessons.destroy');

        Route::get('/courses/{course}/lessons/{lesson}/quizzes', [QuizManageController::class, 'index'])->name('coach.courses.lessons.quizzes.index');
        Route::post('/courses/{course}/lessons/{lesson}/quizzes', [QuizManageController::class, 'store'])->name('coach.courses.lessons.quizzes.store');
        Route::put('/courses/{course}/lessons/{lesson}/quizzes', [QuizManageController::class, 'update'])->name('coach.courses.lessons.quizzes.update');
        Route::delete('/courses/{course}/lessons/{lesson}/quizzes', [QuizManageController::class, 'destroy'])->name('coach.courses.lessons.quizzes.destroy');
        Route::post('/courses/{course}/lessons/{lesson}/quizzes/questions', [QuizManageController::class, 'storeQuestion'])->name('coach.courses.lessons.quizzes.questions.store');
        Route::delete('/courses/{course}/lessons/{lesson}/quizzes/questions/{question}', [QuizManageController::class, 'destroyQuestion'])->name('coach.courses.lessons.quizzes.questions.destroy');

        Route::get('/courses/{course}/students', [CourseStudentController::class, 'index'])->name('coach.courses.students.index');
    });

    // Admin routes
    Route::middleware('role:admin')->prefix('admin')->group(function () {
        Route::get('/', [AdminDashboardController::class, 'index'])->name('admin.dashboard');

        Route::get('/users', [AdminUserController::class, 'index'])->name('admin.users.index');
        Route::put('/users/{user}/role', [AdminUserController::class, 'updateRole'])->name('admin.users.updateRole');

        Route::get('/students', [AdminStudentController::class, 'index'])->name('admin.students.index');

        Route::get('/courses', [AdminCourseController::class, 'index'])->name('admin.courses.index');
        Route::delete('/courses/{course}', [AdminCourseController::class, 'destroy'])->name('admin.courses.destroy');

        Route::get('/categories', [CategoryController::class, 'index'])->name('admin.categories.index');
        Route::get('/categories/create', [CategoryController::class, 'create'])->name('admin.categories.create');
        Route::post('/categories', [CategoryController::class, 'store'])->name('admin.categories.store');
        Route::get('/categories/{category}/edit', [CategoryController::class, 'edit'])->name('admin.categories.edit');
        Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('admin.categories.update');
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('admin.categories.destroy');
    });
});
