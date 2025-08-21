<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BlogPostRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'excerpt' => ['nullable', 'string', 'max:1000'],
            'content' => ['required', 'string'],
            'category_id' => ['nullable', 'exists:blog_categories,id'],
            'tags' => ['array'],
            'tags.*' => ['exists:blog_tags,id'],
            'status' => ['required', 'in:draft,published,scheduled'],
            'published_at' => ['nullable', 'required_if:status,scheduled', 'date'],
            'cover_image' => ['nullable', 'image', 'max:5120'],
            'meta_title' => ['nullable', 'string', 'max:70'],
            'meta_description' => ['nullable', 'string', 'max:160'],
        ];
    }
}
