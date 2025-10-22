<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $taskId = $this->route('task')->id;
        
        return [
            'title' => [
                'required',
                'string',
                'max:255',
                'min:3',
                Rule::unique('tasks')->where(function ($query) use ($taskId) {
                    return $query->where('user_id', auth()->id())
                                 ->where('id', '!=', $taskId);
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
                'before:1 year'
            ],
        ];
    }
}