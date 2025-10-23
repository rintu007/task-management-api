<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TaskTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_task(): void
{
    $user = $this->actingAsUser();

    $response = $this->postJson('/api/tasks', [
        'title' => 'Test Task',
        'description' => 'This is a test task description that is long enough',
        'status' => 'pending',
        'due_date' => now()->addDays(7)->format('Y-m-d H:i:s'),
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'data' => ['id', 'title', 'description', 'status', 'user'],
            'message'
        ])
        ->assertJson([
            'data' => [
                'title' => 'Test Task',
                'description' => 'This is a test task description that is long enough',
                'status' => 'pending',
            ]
        ]);

    $this->assertDatabaseHas('tasks', [
        'title' => 'Test Task',
        'user_id' => $user->id,
    ]);
}

    public function test_user_can_view_their_tasks(): void
    {
        $user = $this->actingAsUser();
        $task = $this->createTaskForUser($user);

        $response = $this->getJson('/api/tasks');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'meta' => ['total', 'filters']
            ])
            ->assertJsonFragment([
                'title' => $task->title,
            ]);
    }

    public function test_user_can_view_their_specific_task(): void
    {
        $user = $this->actingAsUser();
        $task = $this->createTaskForUser($user);

        $response = $this->getJson("/api/tasks/{$task->id}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $task->id,
                    'title' => $task->title,
                ]
            ]);
    }

    public function test_user_can_update_their_task(): void
    {
        $user = $this->actingAsUser();
        $task = $this->createTaskForUser($user);

        $response = $this->putJson("/api/tasks/{$task->id}", [
            'title' => 'Updated Task Title',
            'description' => 'Updated description',
            'status' => 'completed',
            'due_date' => now()->addDays(14)->format('Y-m-d H:i:s'),
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'title' => 'Updated Task Title',
                    'status' => 'completed',
                ],
                'message' => 'Task updated successfully.'
            ]);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => 'Updated Task Title',
            'status' => 'completed',
        ]);
    }

    public function test_user_can_delete_their_task(): void
    {
        $user = $this->actingAsUser();
        $task = $this->createTaskForUser($user);

        $response = $this->deleteJson("/api/tasks/{$task->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Task deleted successfully.']);

        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }

    public function test_user_cannot_update_other_users_task(): void
    {
        $user1 = $this->actingAsUser();
        $user2 = $this->createRegularUser();
        $task = $this->createTaskForUser($user2);

        $response = $this->putJson("/api/tasks/{$task->id}", [
            'title' => 'Unauthorized Update',
            'description' => 'This should fail',
            'status' => 'completed',
        ]);

        $response->assertStatus(403);
    }

    public function test_user_cannot_delete_other_users_task(): void
    {
        $user1 = $this->actingAsUser();
        $user2 = $this->createRegularUser();
        $task = $this->createTaskForUser($user2);

        $response = $this->deleteJson("/api/tasks/{$task->id}");

        $response->assertStatus(403);
    }

    public function test_admin_can_view_all_tasks(): void
    {
        $admin = $this->actingAsAdmin();
        $user = $this->createRegularUser();
        
        $adminTask = $this->createTaskForUser($admin);
        $userTask = $this->createTaskForUser($user);

        $response = $this->getJson('/api/tasks');

        $response->assertStatus(200);
        
        // Admin should see both tasks
        $tasks = $response->json('data');
        $taskTitles = collect($tasks)->pluck('title')->toArray();
        
        $this->assertContains($adminTask->title, $taskTitles);
        $this->assertContains($userTask->title, $taskTitles);
    }

    public function test_admin_can_update_any_task(): void
    {
        $admin = $this->actingAsAdmin();
        $user = $this->createRegularUser();
        $task = $this->createTaskForUser($user);

        $response = $this->putJson("/api/tasks/{$task->id}", [
            'title' => 'Admin Updated Task',
            'description' => 'Admin updated this task',
            'status' => 'completed',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => 'Admin Updated Task',
            'status' => 'completed',
        ]);
    }

    public function test_task_validation(): void
    {
        $user = $this->actingAsUser();

        $response = $this->postJson('/api/tasks', [
            'title' => '', // Empty title should fail
            'description' => '', // Empty description should fail
            'status' => 'invalid_status', // Invalid status
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'description', 'status']);
    }

    public function test_task_title_must_be_unique_per_user(): void
    {
        $user = $this->actingAsUser();
        
        // Create first task
        $this->postJson('/api/tasks', [
            'title' => 'Unique Task',
            'description' => 'First task',
            'status' => 'pending',
        ]);

        // Try to create another task with same title for same user
        $response = $this->postJson('/api/tasks', [
            'title' => 'Unique Task', // Same title
            'description' => 'Second task with same title',
            'status' => 'pending',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    }

    public function test_user_can_get_task_counts(): void
    {
        $user = $this->actingAsUser();
        
        // Create tasks with different statuses
        $this->createTaskForUser($user, ['status' => 'pending']);
        $this->createTaskForUser($user, ['status' => 'in_progress']);
        $this->createTaskForUser($user, ['status' => 'completed']);

        $response = $this->getJson('/api/tasks/counts');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => ['total', 'pending', 'in_progress', 'completed']
            ])
            ->assertJson([
                'data' => [
                    'total' => 3,
                    'pending' => 1,
                    'in_progress' => 1,
                    'completed' => 1,
                ]
            ]);
    }
  
}