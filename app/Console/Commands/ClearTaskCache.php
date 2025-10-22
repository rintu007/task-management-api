<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ClearTaskCache extends Command
{
    protected $signature = 'cache:tasks-clear {user_id?}';
    protected $description = 'Clear task-related caches';

    public function handle(): void
    {
        $userId = $this->argument('user_id');

        if ($userId) {
            $this->clearUserTaskCaches($userId);
            $this->info("Task caches cleared for user ID: {$userId}");
        } else {
            $this->clearAllTaskCaches();
            $this->info('All task caches cleared.');
        }
    }

    private function clearUserTaskCaches(int $userId): void
    {
        $patterns = [
            "user_{$userId}_tasks_%",
            "user_{$userId}_task_counts",
            "user_{$userId}_with_role"
        ];

        foreach ($patterns as $pattern) {
            $this->clearCacheByPattern($pattern);
        }
    }

    private function clearAllTaskCaches(): void
    {
        $patterns = [
            'user_%_tasks_%',
            'user_%_task_counts',
            'task_%_details',
            'task_%_with_user'
        ];

        foreach ($patterns as $pattern) {
            $this->clearCacheByPattern($pattern);
        }
    }

    private function clearCacheByPattern(string $pattern): void
    {
        $cacheDriver = config('cache.default');
        
        if ($cacheDriver === 'database') {
            $this->clearDatabaseCacheByPattern($pattern);
        } elseif ($cacheDriver === 'redis') {
            $this->clearRedisCacheByPattern($pattern);
        } elseif ($cacheDriver === 'file') {
            $this->clearFileCacheByPattern($pattern);
        } else {
            $this->warn("Cache driver '{$cacheDriver}' not supported for pattern clearing.");
        }
    }

    private function clearDatabaseCacheByPattern(string $pattern): void
    {
        $table = config('cache.stores.database.table', 'cache');
        
        DB::table($table)
            ->where('key', 'like', $pattern)
            ->delete();
            
        $this->line("Cleared cache pattern: {$pattern}");
    }

    private function clearRedisCacheByPattern(string $pattern): void
    {
        $redis = Cache::getRedis();
        
        // Use SCAN instead of KEYS for better performance
        $cursor = 0;
        do {
            list($cursor, $keys) = $redis->scan($cursor, ['match' => "*{$pattern}*", 'count' => 100]);
            
            if (!empty($keys)) {
                foreach ($keys as $key) {
                    Cache::forget($key);
                }
            }
        } while ($cursor != 0);
        
        $this->line("Cleared Redis cache pattern: {$pattern}");
    }

    private function clearFileCacheByPattern(string $pattern): void
    {
        $files = glob(storage_path('framework/cache/*'));
        
        $cleared = 0;
        foreach ($files as $file) {
            if (is_file($file) && str_contains(basename($file), str_replace(['%', '*'], '', $pattern))) {
                unlink($file);
                $cleared++;
            }
        }
        
        $this->line("Cleared {$cleared} file cache entries for pattern: {$pattern}");
    }
}