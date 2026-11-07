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

        if (auth()->user()->cannot('create', [Submission::class, $quiz])) {
            return redirect()->route('courses.quizzes.result', [$course, $quiz])
                ->with('success', 'この小テストは合格済みです。');
        }

        $quiz->load('questions.options');

        return view('quizzes.show', compact('course', 'quiz'));
    }

    public function submit(Request $request, Course $course, Quiz $quiz)
    {
        $this->authorize('create', [Submission::class, $quiz]);

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

        $submissions = $quiz->submissions()
            ->where('user_id', auth()->id())
            ->latest('submitted_at')
            ->latest('id')
            ->get();

        abort_if($submissions->isEmpty(), 404);

        $submission = $submissions->first();
        $canRetake = auth()->user()->can('create', [Submission::class, $quiz]);

        return view('quizzes.result', compact('course', 'quiz', 'submission', 'submissions', 'canRetake'));
    }
}
