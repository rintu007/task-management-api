<?php

namespace App\Services;

use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class TaskService
{
    /**
     * Get cached task with user
     */
    public function getCachedTask(int $taskId): ?Task
    {
        $cacheKey = "task_{$taskId}_details";
        $duration = config('cache.durations.task_detail', 600);
        
        return Cache::remember($cacheKey, $duration, function () use ($taskId) {
            return Task::with('user')->find($taskId);
        });
    }

    /**
     * Get cached task counts for user
     */
    public function getCachedTaskCounts(int $userId): array
    {
        $cacheKey = "user_{$userId}_task_counts";
        $duration = config('cache.durations.counts', 300);
        
        return Cache::remember($cacheKey, $duration, function () use ($userId) {
            return Task::getCountsByStatus($userId);
        });
    }

    /**
     * Get cached user tasks with filters
     */
    public function getCachedUserTasks(int $userId, ?string $status = null)
    {
        $cacheKey = "user_{$userId}_tasks_" . ($status ? "status_{$status}" : 'all');
        $duration = config('cache.durations.tasks_list', 300);
        
        $user = User::find($userId);
        
        return Cache::remember($cacheKey, $duration, function () use ($user, $status) {
            return Task::with('user')
                ->forUser($user)
                ->statusFilter($status)
                ->latest()
                ->get();
        });
    }

    /**
     * Clear task-related caches for user
     */
    public function clearUserTaskCaches(int $userId): void
    {
        $patterns = [
            "user_{$userId}_tasks_%",
            "user_{$userId}_task_counts",
        ];

        foreach ($patterns as $pattern) {
            $this->clearCacheByPattern($pattern);
        }
    }

    /**
     * Clear specific task cache
     */
    public function clearTaskCache(int $taskId): void
    {
        Cache::forget("task_{$taskId}_details");
    }

    private function clearCacheByPattern(string $pattern): void
    {
        $cacheDriver = config('cache.default');
        
        if ($cacheDriver === 'database') {
            $table = config('cache.stores.database.table', 'cache');
            
            \Illuminate\Support\Facades\DB::table($table)
                ->where('key', 'like', $pattern)
                ->delete();
                
        } elseif ($cacheDriver === 'redis') {
            $redis = Cache::getRedis();
            
            $cursor = 0;
            do {
                list($cursor, $keys) = $redis->scan($cursor, ['match' => "*{$pattern}*", 'count' => 100]);
                
                if (!empty($keys)) {
                    foreach ($keys as $key) {
                        Cache::forget($key);
                    }
                }
            } while ($cursor != 0);
        }
    }
}