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
use App\Services\TaskService;

class TaskController extends Controller
{
    public function __construct(
        private TaskService $taskService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $status = $request->get('status');
        $user = $request->user();
        
        $tasks = $this->taskService->getCachedUserTasks($user->id, $status);
        
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

        // Clear relevant caches
        $this->taskService->clearUserTaskCaches($request->user()->id);
        
        SendTaskNotification::dispatch($task, 'created', $task->user);
        
        return response()->json([
            'data' => $task->load('user'),
            'message' => 'Task created successfully.'
        ], 201);
    }

    public function show(Task $task): JsonResponse
    {
        if (!Gate::allows('view', $task)) {
            abort(403, 'Unauthorized action.');
        }
        
        $cachedTask = $this->taskService->getCachedTask($task->id);
        
        return response()->json([
            'data' => $cachedTask
        ]);
    }

    public function update(UpdateTaskRequest $request, Task $task): JsonResponse
    {
        if (!Gate::allows('update', $task)) {
            abort(403, 'Unauthorized action.');
        }

        $oldStatus = $task->status;
        $task->update($request->validated());

        // Clear relevant caches
        $this->taskService->clearUserTaskCaches($task->user_id);
        $this->taskService->clearTaskCache($task->id);
        
        if ($oldStatus !== 'completed' && $task->status === 'completed') {
            SendTaskNotification::dispatch($task, 'completed', $task->user);
        } else {
            SendTaskNotification::dispatch($task, 'updated', $task->user);
        }
        
        return response()->json([
            'data' => $task->load('user'),
            'message' => 'Task updated successfully.'
        ]);
    }

    public function destroy(Task $task): JsonResponse
    {
        if (!Gate::allows('delete', $task)) {
            abort(403, 'Unauthorized action.');
        }
        
        $userId = $task->user_id;
        $taskId = $task->id;
        
        $task->delete();
        
        // Clear relevant caches
        $this->taskService->clearUserTaskCaches($userId);
        $this->taskService->clearTaskCache($taskId);
        
        return response()->json([
            'message' => 'Task deleted successfully.'
        ]);
    }

    /**
     * Get task counts by status for the authenticated user
     */
    public function getCounts(Request $request): JsonResponse
    {
        $user = $request->user();
        $counts = $this->taskService->getCachedTaskCounts($user->id);
        
        return response()->json([
            'data' => $counts
        ]);
    }
}