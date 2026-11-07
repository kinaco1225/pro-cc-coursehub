@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto">
    {{-- コース情報ヘッダー --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 mb-6">
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
            <div class="flex-1">
                <h1 class="text-2xl font-bold text-gray-900 mb-3">{{ $course->title }}</h1>
                <div class="flex flex-wrap items-center gap-3 text-sm text-gray-500 mb-4">
                    <span class="flex items-center gap-1">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        {{ $course->user->name }}
                    </span>
                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium
                        {{ $course->difficulty === 'beginner' ? 'bg-green-50 text-green-700' : ($course->difficulty === 'intermediate' ? 'bg-amber-50 text-amber-700' : 'bg-red-50 text-red-700') }}">
                        {{ $course->difficulty === 'beginner' ? '初級' : ($course->difficulty === 'intermediate' ? '中級' : '上級') }}
                    </span>
                    @if($course->category)
                        <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-700">{{ $course->category->name }}</span>
                    @endif
                </div>
                @if($course->tags->isNotEmpty())
                    <div class="flex flex-wrap gap-2 mb-4">
                        @foreach($course->tags as $tag)
                            <span class="inline-flex items-center rounded-full bg-indigo-50 px-2.5 py-0.5 text-xs font-medium text-indigo-700">{{ $tag->name }}</span>
                        @endforeach
                    </div>
                @endif
            </div>

            @if(auth()->user()->isStudent())
                @if($enrollment)
                    <span class="inline-flex items-center rounded-full bg-green-50 px-3 py-1.5 text-sm font-medium text-green-700">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        受講中
                    </span>
                @else
                    <form method="POST" action="{{ route('courses.enroll', $course) }}">
                        @csrf
                        <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg px-5 py-2.5 shadow-sm transition-all duration-150">受講登録</button>
                    </form>
                @endif
            @endif
        </div>

        <p class="text-gray-700 leading-relaxed">{{ $course->description }}</p>
    </div>

    {{-- チャプター一覧 --}}
    <div class="space-y-4">
        @foreach($course->chapters->sortBy('order') as $chapter)
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                <h2 class="text-lg font-semibold text-gray-900 mb-3">
                    <span class="text-indigo-600 mr-1">{{ $chapter->order }}.</span> {{ $chapter->title }}
                </h2>
                <ul class="space-y-1">
                    @foreach($chapter->lessons->where('is_published', true)->sortBy('order') as $lesson)
                        <li class="flex items-center justify-between py-2.5 px-3 hover:bg-gray-50 rounded-lg transition-colors">
                            <a href="{{ route('courses.lessons.show', [$course, $lesson]) }}" class="text-indigo-600 hover:text-indigo-700 font-medium text-sm">
                                {{ $lesson->order }}. {{ $lesson->title }}
                            </a>
                            @if($lesson->quiz)
                                <a href="{{ route('courses.quizzes.show', [$course, $lesson->quiz]) }}" class="inline-flex items-center rounded-full bg-indigo-50 px-2.5 py-0.5 text-xs font-medium text-indigo-700 hover:bg-indigo-100 transition-colors">小テスト</a>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </div>

    {{-- レビュー --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 mt-6">
        <div class="flex flex-wrap items-center gap-3 mb-4">
            <h2 class="text-lg font-semibold text-gray-900">レビュー</h2>
            @if($course->reviews->isNotEmpty())
                @php
                    $averageRating = $course->reviews->avg('rating');
                @endphp
                <span class="text-amber-500">{{ str_repeat('★', (int) round($averageRating)) }}<span class="text-gray-300">{{ str_repeat('★', 5 - (int) round($averageRating)) }}</span></span>
                <span class="text-sm text-gray-700 font-medium">{{ number_format($averageRating, 1) }}</span>
                <span class="text-sm text-gray-500">（{{ $course->reviews->count() }}件）</span>
            @endif
        </div>

        @if($canReview)
            <form method="POST" action="{{ route('courses.reviews.store', $course) }}" class="mb-6 p-4 bg-gray-50 rounded-xl border border-gray-100">
                @csrf
                <p class="text-sm font-medium text-gray-700 mb-2">評価</p>
                <div class="flex flex-wrap gap-4 mb-4">
                    @foreach(range(1, 5) as $rating)
                        <label class="flex items-center gap-1 cursor-pointer text-sm text-gray-700">
                            <input type="radio" name="rating" value="{{ $rating }}" {{ (int) old('rating') === $rating ? 'checked' : '' }}
                                class="text-indigo-600 border-gray-300 focus:ring-indigo-500">
                            <span class="text-amber-500">{{ str_repeat('★', $rating) }}</span>
                        </label>
                    @endforeach
                </div>
                <label for="comment" class="block text-sm font-medium text-gray-700 mb-2">コメント（任意）</label>
                <textarea id="comment" name="comment" rows="3" maxlength="1000"
                    class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('comment') }}</textarea>
                <button type="submit" class="mt-3 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg px-4 py-2 shadow-sm transition-all duration-150 text-sm">レビューを投稿する</button>
            </form>
        @elseif(auth()->user()->isStudent())
            <p class="mb-6 text-sm text-gray-500">
                {{ $course->reviews->contains('user_id', auth()->id()) ? 'レビュー投稿済みです。' : '受講完了後にレビューを投稿できます。' }}
            </p>
        @endif

        @forelse($course->reviews as $review)
            <div class="py-4 {{ $loop->first ? '' : 'border-t border-gray-100' }}">
                <div class="flex flex-wrap items-center gap-2 mb-1">
                    <span class="text-sm font-medium text-gray-900">{{ $review->user->name }}</span>
                    <span class="text-amber-500 text-sm">{{ str_repeat('★', $review->rating) }}<span class="text-gray-300">{{ str_repeat('★', 5 - $review->rating) }}</span></span>
                    <span class="text-xs text-gray-400">{{ $review->created_at->format('Y/m/d') }}</span>
                </div>
                @if($review->comment)
                    <p class="text-sm text-gray-700 whitespace-pre-line">{{ $review->comment }}</p>
                @endif
            </div>
        @empty
            <p class="text-sm text-gray-500">まだレビューはありません。</p>
        @endforelse
    </div>
</div>
@endsection
