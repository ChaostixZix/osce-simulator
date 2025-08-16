<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBlogPostRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $postId = $this->route('post')->id;

        return [
            'title' => [
                'required',
                'string',
                'max:255',
                Rule::unique('posts', 'title')->ignore($postId)
            ],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('posts', 'slug')->ignore($postId)
            ],
            'excerpt' => 'nullable|string|max:500',
            'content' => 'required|string',
            'featured_image' => 'nullable|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'status' => ['required', Rule::in(['draft', 'published', 'archived'])],
            'published_at' => 'nullable|date',
            'meta_data' => 'nullable|array',
            'meta_data.meta_title' => 'nullable|string|max:60',
            'meta_data.meta_description' => 'nullable|string|max:160',
            'meta_data.keywords' => 'nullable|array',
            'meta_data.keywords.*' => 'string|max:50',
            'is_featured' => 'sometimes|boolean',
            'comments_enabled' => 'sometimes|boolean',
            'tags' => 'nullable|array',
            'tags.*' => 'exists:tags,id',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'category_id' => 'category',
            'meta_data.meta_title' => 'meta title',
            'meta_data.meta_description' => 'meta description',
            'meta_data.keywords' => 'keywords',
            'is_featured' => 'featured status',
            'comments_enabled' => 'comments setting',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'title.unique' => 'A post with this title already exists. Please choose a different title.',
            'slug.unique' => 'A post with this URL slug already exists.',
            'category_id.required' => 'Please select a category for your post.',
            'category_id.exists' => 'The selected category is invalid.',
            'content.required' => 'Post content is required.',
            'tags.*.exists' => 'One or more selected tags are invalid.',
            'meta_data.meta_title.max' => 'Meta title should not exceed 60 characters for SEO optimization.',
            'meta_data.meta_description.max' => 'Meta description should not exceed 160 characters for SEO optimization.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Clean up empty meta_data fields
        if ($this->has('meta_data')) {
            $metaData = array_filter($this->meta_data, function ($value) {
                return !is_null($value) && $value !== '';
            });
            $this->merge(['meta_data' => $metaData]);
        }
    }
}