<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class GenerateOsceCaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sources' => ['required', 'array', 'min:1', 'max:5'],
            'sources.*' => [
                'file',
                'max:10240', // 10 MB per file
                'mimetypes:text/plain,text/markdown,application/json,application/pdf',
                'mimes:txt,md,markdown,json,pdf',
            ],
            'instructions' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'sources.required' => 'At least one reference file is required to generate an OSCE case.',
            'sources.*.mimetypes' => 'Only text, markdown, JSON, or PDF files are supported right now.',
        ];
    }
}
