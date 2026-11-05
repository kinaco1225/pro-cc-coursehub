<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Option;
use App\Models\Quiz;
use App\Models\Submission;
use Illuminate\Http\Request;

class QuizController extends Controller
{
    public function show(Course $course, Quiz $quiz)
    {
        $this->authorize('view', $course);

        $quiz->load('questions.options');

        return view('quizzes.show', compact('course', 'quiz'));
    }

    public function submit(Request $request, Course $course, Quiz $quiz)
    {
        $answers = $request->input('answers', []);

        $correctCount = 0;
        foreach ($quiz->questions as $question) {
            $userAnswer = collect($answers)->firstWhere('question_id', $question->id);
            $selectedOption = Option::find($userAnswer['option_id'] ?? null);
            if ($selectedOption && $selectedOption->is_correct) {
                $correctCount++;
            }
        }

        $score = (int) round($correctCount / $quiz->questions->count() * 100);

        $submission = Submission::create([
            'user_id' => auth()->id(),
            'quiz_id' => $quiz->id,
            'score' => $score,
            'answers' => $answers,
            'submitted_at' => now(),
        ]);

        return redirect()->route('courses.quizzes.result', [$course, $quiz]);
    }

    public function result(Course $course, Quiz $quiz)
    {
        $this->authorize('view', $course);

        $quiz->load('questions.options');

        $submission = Submission::where('user_id', auth()->id())
            ->where('quiz_id', $quiz->id)
            ->latest()
            ->firstOrFail();

        return view('quizzes.result', compact('course', 'quiz', 'submission'));
    }
}
