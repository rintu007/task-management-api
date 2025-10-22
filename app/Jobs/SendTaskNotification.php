<?php

namespace App\Jobs;

use App\Models\Task;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendTaskNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $task;
    public $action;
    public $user;

    /**
     * Create a new job instance.
     */
    public function __construct(Task $task, string $action, User $user)
    {
        $this->task = $task;
        $this->action = $action;
        $this->user = $user;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $subject = "Task {$this->action}: {$this->task->title}";
            $message = $this->getEmailMessage();

            // For demo purposes, we'll log the email instead of actually sending it
            // In production, you would use: Mail::to($this->user->email)->send(...)
            
            Log::info('Task notification email would be sent:', [
                'to' => $this->user->email,
                'subject' => $subject,
                'task_id' => $this->task->id,
                'task_title' => $this->task->title,
                'action' => $this->action,
                'user_name' => $this->user->name,
            ]);

            // Uncomment below to actually send emails when you configure mail
            /*
            Mail::send('emails.task-notification', [
                'task' => $this->task,
                'action' => $this->action,
                'user' => $this->user
            ], function ($message) use ($subject) {
                $message->to($this->user->email)
                        ->subject($subject);
            });
            */

        } catch (\Exception $e) {
            Log::error('Failed to send task notification email: ' . $e->getMessage());
        }
    }

    /**
     * Get the appropriate email message based on action
     */
    private function getEmailMessage(): string
    {
        $messages = [
            'created' => "A new task '{$this->task->title}' has been created and assigned to you.",
            'completed' => "The task '{$this->task->title}' has been marked as completed.",
            'updated' => "The task '{$this->task->title}' has been updated.",
        ];

        return $messages[$this->action] ?? "Task '{$this->task->title}' has been {$this->action}.";
    }
}