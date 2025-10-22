<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Task Notification</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #3b82f6; color: white; padding: 20px; text-align: center; }
        .content { background: #f9fafb; padding: 20px; border-radius: 0 0 5px 5px; }
        .task-info { background: white; padding: 15px; border-radius: 5px; margin: 15px 0; }
        .status { display: inline-block; padding: 5px 10px; border-radius: 15px; font-size: 12px; font-weight: bold; }
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-in_progress { background: #dbeafe; color: #1e40af; }
        .status-completed { background: #d1fae5; color: #065f46; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Task Management System</h1>
        </div>
        <div class="content">
            <h2>Task {{ ucfirst($action) }}</h2>
            <p>Hello {{ $user->name }},</p>
            
            <div class="task-info">
                <h3>{{ $task->title }}</h3>
                <p><strong>Description:</strong> {{ $task->description }}</p>
                <p><strong>Status:</strong> 
                    <span class="status status-{{ $task->status }}">
                        {{ str_replace('_', ' ', ucfirst($task->status)) }}
                    </span>
                </p>
                @if($task->due_date)
                <p><strong>Due Date:</strong> {{ $task->due_date->format('M j, Y g:i A') }}</p>
                @endif
                <p><strong>Assigned To:</strong> {{ $task->user->name }}</p>
            </div>

            <p>
                @if($action === 'created')
                You can view and manage this task in your dashboard.
                @elseif($action === 'completed')
                This task has been successfully completed.
                @else
                This task has been updated. Please review the changes.
                @endif
            </p>

            <p>
                <a href="{{ url('/tasks/' . $task->id) }}" style="background: #3b82f6; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;">
                    View Task
                </a>
            </p>

            <p>Best regards,<br>Task Management System</p>
        </div>
    </div>
</body>
</html>