<?php

namespace App\Http\Requests\Admin;

use App\Models\Article;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'excerpt' => ['required', 'string', 'max:300'],
            'content' => ['required', 'string', 'max:50000'],
            'category' => ['required', Rule::in(array_keys(Article::CATEGORIES))],
            'status' => ['required', Rule::in(['draft', 'published'])],
            'published_at' => ['nullable', 'date'],
            'is_featured' => ['nullable', 'boolean'],
            'thumbnail' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'], // KB
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Vui lòng nhập tiêu đề.',
            'excerpt.required' => 'Vui lòng nhập mô tả ngắn.',
            'excerpt.max' => 'Mô tả ngắn tối đa 300 ký tự.',
            'content.required' => 'Vui lòng nhập nội dung bài viết.',
            'category.in' => 'Chuyên mục không hợp lệ.',
            'thumbnail.image' => 'Ảnh đại diện phải là tệp hình ảnh.',
            'thumbnail.mimes' => 'Ảnh đại diện chỉ nhận JPG, PNG hoặc WEBP.',
            'thumbnail.max' => 'Ảnh đại diện tối đa 2MB.',
        ];
    }
}
