<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization is handled by the TaskPolicy
        return true;
    }

    public function rules(): array
    {
        $taskId = $this->route('task')->id;
        
        return [
            'title' => 'required|string|max:255|unique:tasks,title,' . $taskId . ',id,user_id,' . auth()->id(),
            'description' => 'required|string',
            'status' => 'required|in:pending,in_progress,completed',
            'due_date' => 'nullable|date|after:now',
        ];
    }
}