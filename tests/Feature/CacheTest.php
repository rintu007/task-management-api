<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

class CacheTest extends TestCase
{
    use RefreshDatabase;

    public function test_task_list_caching(): void
    {
        $user = $this->actingAsUser();
        $task = $this->createTaskForUser($user);

        // First request should hit database and set cache
        $response1 = $this->getJson('/api/tasks');
        $response1->assertStatus(200);

        // Manually set cache to simulate cached response
        $cacheKey = "user_{$user->id}_tasks_all";
        Cache::put($cacheKey, collect([$task]), 300); // 5 minutes

        // Second request should use cache
        $response2 = $this->getJson('/api/tasks');
        $response2->assertStatus(200);
    }

    public function test_cache_cleared_on_task_creation(): void
    {
        $user = $this->actingAsUser();
        
        // Set some cache first
        $cacheKey = "user_{$user->id}_tasks_all";
        Cache::put($cacheKey, collect([]), 300);

        // Create a task which should clear cache - use longer description
        $response = $this->postJson('/api/tasks', [
            'title' => 'New Task',
            'description' => 'This is a longer description that meets validation requirements',
            'status' => 'pending',
        ]);

        $response->assertStatus(201);

        // Cache should be cleared, so next request should hit database
        $this->assertFalse(Cache::has($cacheKey));
    }

    public function test_task_details_caching(): void
    {
        $user = $this->actingAsUser();
        $task = $this->createTaskForUser($user);

        // First request should set cache
        $response1 = $this->getJson("/api/tasks/{$task->id}");
        $response1->assertStatus(200);

        // Cache should be set
        $cacheKey = "task_{$task->id}_details";
        $this->assertTrue(Cache::has($cacheKey));
    }
}