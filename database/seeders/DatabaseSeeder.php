<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use App\Models\Task;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create Roles
        $adminRole = Role::create([
            'name' => 'Admin',
            'description' => 'Administrator with full access'
        ]);

        $userRole = Role::create([
            'name' => 'User',
            'description' => 'Regular user'
        ]);

        // Create Users
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role_id' => $adminRole->id,
        ]);

        $user1 = User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => Hash::make('password'),
            'role_id' => $userRole->id,
        ]);

        $user2 = User::create([
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'password' => Hash::make('password'),
            'role_id' => $userRole->id,
        ]);

        // Create Tasks
        Task::create([
            'user_id' => $user1->id,
            'title' => 'Complete project documentation',
            'description' => 'Write comprehensive documentation for the new project',
            'status' => 'in_progress',
            'due_date' => now()->addDays(7),
        ]);

        Task::create([
            'user_id' => $user1->id,
            'title' => 'Fix authentication bug',
            'description' => 'Resolve the issue with user authentication',
            'status' => 'pending',
            'due_date' => now()->addDays(3),
        ]);

        Task::create([
            'user_id' => $user2->id,
            'title' => 'Design new UI components',
            'description' => 'Create design system for the application',
            'status' => 'completed',
            'due_date' => now()->subDays(2),
        ]);

        Task::create([
            'user_id' => $admin->id,
            'title' => 'Setup deployment pipeline',
            'description' => 'Configure CI/CD pipeline for the application',
            'status' => 'in_progress',
            'due_date' => now()->addDays(5),
        ]);
    }
}