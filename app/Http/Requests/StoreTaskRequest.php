<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTaskRequest extends FormRequest
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
                'min:3',
                Rule::unique('tasks')->where(function ($query) {
                    return $query->where('user_id', auth()->id());
                })
            ],
            'description' => [
                'required', 
                'string',
                'min:10',
                'max:1000'
            ],
            'status' => [
                'required',
                Rule::in(['pending', 'in_progress', 'completed'])
            ],
            'due_date' => [
                'nullable',
                'date',
                'after:now',
                'before:1 year' // Prevent dates too far in future
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'title.unique' => 'You already have a task with this title.',
            'due_date.after' => 'Due date must be in the future.',
            'due_date.before' => 'Due date must be within one year.',
        ];
    }
}