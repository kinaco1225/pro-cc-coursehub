@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="mb-6">
        <a href="{{ route('courses.show', $course) }}" class="inline-flex items-center text-sm text-gray-500 hover:text-indigo-600 transition-colors">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            {{ $course->title }} に戻る
        </a>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
        <h1 class="text-2xl font-bold text-gray-900 mb-6">{{ $quiz->title }} - 結果</h1>

        {{-- スコア表示 --}}
        <div class="text-center py-8 mb-6 rounded-xl {{ $submission->score >= $quiz->passing_score ? 'bg-green-50 border border-green-100' : 'bg-red-50 border border-red-100' }}">
            <p class="text-5xl font-bold {{ $submission->score >= $quiz->passing_score ? 'text-green-600' : 'text-red-600' }}">
                {{ $submission->score }}%
            </p>
            <p class="mt-2 text-lg font-semibold {{ $submission->score >= $quiz->passing_score ? 'text-green-700' : 'text-red-700' }}">
                {{ $submission->score >= $quiz->passing_score ? '合格' : '不合格' }}
            </p>
            <p class="text-sm text-gray-500 mt-1">合格点: {{ $quiz->passing_score }}%</p>
        </div>

        {{-- 解答詳細 --}}
        @php
            $userAnswers = collect($submission->answers);
        @endphp

        <div class="space-y-4">
            @foreach($quiz->questions->sortBy('order') as $index => $question)
                @php
                    $userAnswer = $userAnswers->firstWhere('question_id', $question->id);
                    $selectedOptionId = $userAnswer['option_id'] ?? null;
                    $correctOption = $question->options->firstWhere('is_correct', true);
                    $isCorrect = $selectedOptionId && $correctOption && $selectedOptionId == $correctOption->id;
                @endphp
                <div class="p-4 rounded-xl border {{ $isCorrect ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200' }}">
                    <p class="font-medium text-gray-900 mb-2 flex items-center gap-2">
                        @if($isCorrect)
                            <svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        @else
                            <svg class="w-5 h-5 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        @endif
                        問{{ $index + 1 }}. {{ $question->body }}
                    </p>
                    @foreach($question->options as $option)
                        <p class="ml-7 text-sm py-0.5 {{ $option->is_correct ? 'text-green-700 font-medium' : 'text-gray-600' }}">
                            {{ $option->id == $selectedOptionId ? '▶ ' : '　' }}{{ $option->body }}
                            @if($option->is_correct) <span class="text-green-600">(正解)</span> @endif
                        </p>
                    @endforeach
                </div>
            @endforeach
        </div>

        {{-- 受験履歴 --}}
        <div class="mt-8">
            <h2 class="text-lg font-semibold text-gray-900 mb-3">受験履歴</h2>
            <div class="overflow-x-auto rounded-xl border border-gray-200">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left font-medium text-gray-500">回</th>
                            <th class="px-4 py-2 text-left font-medium text-gray-500">受験日時</th>
                            <th class="px-4 py-2 text-left font-medium text-gray-500">スコア</th>
                            <th class="px-4 py-2 text-left font-medium text-gray-500">結果</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @foreach($submissions as $history)
                            @php
                                $historyPassed = $history->score >= $quiz->passing_score;
                            @endphp
                            <tr class="{{ $history->is($submission) ? 'bg-indigo-50' : '' }}">
                                <td class="px-4 py-2 text-gray-700">{{ $submissions->count() - $loop->index }}</td>
                                <td class="px-4 py-2 text-gray-700">{{ $history->submitted_at->format('Y/m/d H:i') }}</td>
                                <td class="px-4 py-2 font-medium text-gray-900">{{ $history->score }}%</td>
                                <td class="px-4 py-2 font-medium {{ $historyPassed ? 'text-green-600' : 'text-red-600' }}">{{ $historyPassed ? '合格' : '不合格' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-8 flex flex-wrap gap-3">
            @if($canRetake)
                <a href="{{ route('courses.quizzes.show', [$course, $quiz]) }}" class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg px-4 py-2.5 shadow-sm transition-all duration-150">再受験する</a>
            @endif
            <a href="{{ route('courses.show', $course) }}" class="bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 font-medium rounded-lg px-4 py-2.5 transition-all duration-150">コースに戻る</a>
        </div>
    </div>
</div>
@endsection
