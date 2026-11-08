<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => [
                'required',
                'string',
                'max:255',
                // タイトルの重複チェック（同じコーチ内で）
                Rule::unique('courses', 'title')->where('user_id', $this->user()->id),
            ],
            'category_id' => ['required', 'exists:categories,id'],
            'description' => ['required', 'string'],
            'difficulty' => ['required', 'in:beginner,intermediate,advanced'],
            'status' => ['required', 'in:draft,published'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['exists:tags,id'],
            'new_tags' => ['nullable', 'string'], // カンマ区切りで新規タグを指定可能
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'コースタイトルは必須です。',
            'title.unique' => '同じタイトルのコースが既に存在します。',
            'category_id.required' => 'カテゴリを選択してください。',
            'description.required' => 'コースの説明は必須です。',
            'difficulty.required' => '難易度を選択してください。',
            'status.required' => '公開ステータスを選択してください。',
        ];
    }
}
