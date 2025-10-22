<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use App\Jobs\SendTaskNotification;


class TaskController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $status = $request->get('status');
        $user = $request->user();
        
        $tasks = Task::with('user')
            ->forUser($user)
            ->statusFilter($status)
            ->latest()
            ->get();
        
        return response()->json([
            'data' => $tasks,
            'meta' => [
                'total' => $tasks->count(),
                'filters' => ['status' => $status]
            ]
        ]);
    }

    public function store(StoreTaskRequest $request): JsonResponse
    {
        $task = $request->user()->tasks()->create($request->validated());

        // Dispatch job for task creation notification
        SendTaskNotification::dispatch($task, 'created', $task->user);
        
        return response()->json([
            'data' => $task->load('user'),
            'message' => 'Task created successfully.'
        ], 201);
    }

    public function show(Task $task): JsonResponse
    {
        // Use Gate facade to authorize view
        if (!Gate::allows('view', $task)) {
            abort(403, 'Unauthorized action.');
        }
        
        return response()->json([
            'data' => $task->load('user')
        ]);
    }

    public function update(UpdateTaskRequest $request, Task $task): JsonResponse
    {
        // Use Gate facade to authorize update
        if (!Gate::allows('update', $task)) {
            abort(403, 'Unauthorized action.');
        }

        $oldStatus = $task->status;
        
        $task->update($request->validated());

        // Dispatch job if task was completed
        if ($oldStatus !== 'completed' && $task->status === 'completed') {
            SendTaskNotification::dispatch($task, 'completed', $task->user);
        }
        // Dispatch job for general updates (optional)
        else {
            SendTaskNotification::dispatch($task, 'updated', $task->user);
        }
        
        return response()->json([
            'data' => $task->load('user'),
            'message' => 'Task updated successfully.'
        ]);
    }

    public function destroy(Task $task): JsonResponse
    {
        // Use Gate facade to authorize delete
        if (!Gate::allows('delete', $task)) {
            abort(403, 'Unauthorized action.');
        }
        
        $task->delete();
        
        return response()->json([
            'message' => 'Task deleted successfully.'
        ]);
    }
}