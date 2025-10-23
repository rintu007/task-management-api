<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Task;
use App\Models\Role;

class ExampleTest extends TestCase
{
    public function test_user_creation(): void
    {
        $user = User::factory()->create();
        
        $this->assertModelExists($user);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => $user->email,
        ]);
    }

    public function test_task_creation(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create(['user_id' => $user->id]);
        
        $this->assertModelExists($task);
        $this->assertEquals($user->id, $task->user_id);
    }
}