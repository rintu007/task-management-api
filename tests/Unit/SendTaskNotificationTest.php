<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Jobs\SendTaskNotification;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

class SendTaskNotificationTest extends TestCase
{
    public function test_notification_job_is_dispatched(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $task = Task::factory()->create(['user_id' => $user->id]);

        SendTaskNotification::dispatch($task, 'created', $user);

        Queue::assertPushed(SendTaskNotification::class, function ($job) use ($task, $user) {
            return $job->task->id === $task->id 
                && $job->user->id === $user->id
                && $job->action === 'created';
        });
    }

    public function test_notification_job_logs_message(): void
    {
        Log::shouldReceive('info')
            ->once()
            ->withArgs(function ($message, $context) {
                return str_contains($message, 'Task notification email would be sent:');
            });

        $user = User::factory()->create();
        $task = Task::factory()->create(['user_id' => $user->id]);

        $job = new SendTaskNotification($task, 'created', $user);
        $job->handle();
    }
}