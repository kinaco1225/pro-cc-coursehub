@extends('layouts.app')

@section('content')
<div>
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-900">コース一覧</h1>
        <p class="mt-1 text-sm text-gray-500">興味のあるコースを見つけて学習を始めましょう</p>
    </div>

    {{-- 検索フィルター --}}
    <form method="GET" action="{{ route('courses.index') }}" class="mb-8 bg-white rounded-xl border border-gray-200 shadow-sm p-5">
        <div class="flex flex-wrap gap-4 items-end">
            <div class="flex-1 min-w-[200px]">
                <label for="search" class="block text-sm font-medium text-gray-700 mb-1">キーワード</label>
                <input type="text" name="search" id="search" value="{{ request('search') }}"
                    class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 px-3 py-2.5 border" placeholder="コース名で検索...">
            </div>
            <div>
                <label for="category" class="block text-sm font-medium text-gray-700 mb-1">カテゴリ</label>
                <select name="category" id="category" class="rounded-lg border-gray-300 shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 px-3 py-2.5 border">
                    <option value="">すべて</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ request('category') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="difficulty" class="block text-sm font-medium text-gray-700 mb-1">難易度</label>
                <select name="difficulty" id="difficulty" class="rounded-lg border-gray-300 shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 px-3 py-2.5 border">
                    <option value="">すべて</option>
                    <option value="beginner" {{ request('difficulty') === 'beginner' ? 'selected' : '' }}>初級</option>
                    <option value="intermediate" {{ request('difficulty') === 'intermediate' ? 'selected' : '' }}>中級</option>
                    <option value="advanced" {{ request('difficulty') === 'advanced' ? 'selected' : '' }}>上級</option>
                </select>
            </div>
            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg px-4 py-2.5 shadow-sm transition-all duration-150">検索</button>
        </div>
    </form>

    {{-- コースグリッド --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($courses as $course)
            <a href="{{ route('courses.show', $course) }}" class="group bg-white rounded-xl border border-gray-200 shadow-sm hover:shadow-md transition-shadow duration-200 overflow-hidden">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-3">
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium
                            {{ $course->difficulty === 'beginner' ? 'bg-green-50 text-green-700' : ($course->difficulty === 'intermediate' ? 'bg-amber-50 text-amber-700' : 'bg-red-50 text-red-700') }}">
                            {{ $course->difficulty === 'beginner' ? '初級' : ($course->difficulty === 'intermediate' ? '中級' : '上級') }}
                        </span>
                        <span class="text-xs text-gray-500">{{ $course->category->name ?? '' }}</span>
                    </div>
                    <h2 class="text-lg font-semibold text-gray-900 group-hover:text-indigo-600 transition-colors mb-2">{{ $course->title }}</h2>
                    <p class="text-sm text-gray-500 mb-4 line-clamp-2">{{ Str::limit($course->description, 100) }}</p>
                    <div class="flex items-center gap-3 text-xs text-gray-400">
                        <span class="flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            {{ $course->user->name ?? '不明' }}
                        </span>
                        <span>{{ $course->chapters_count }} チャプター</span>
                        <span>{{ $course->enrollments_count }}名受講中</span>
                    </div>
                </div>
            </a>
        @empty
            <div class="col-span-full flex flex-col items-center justify-center py-16">
                <svg class="w-12 h-12 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                <p class="text-gray-500">コースが見つかりませんでした。</p>
            </div>
        @endforelse
    </div>

    <div class="mt-8">
        {{ $courses->withQueryString()->links() }}
    </div>
</div>
@endsection
