<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Task;
use Laravel\Sanctum\Sanctum;

abstract class TestCase extends BaseTestCase
{
    /**
     * Ensure base test data like roles exist.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Automatically seed roles if not found
        if (Role::count() === 0) {
            Role::factory()->create(['name' => 'Admin']);
            Role::factory()->create(['name' => 'User']);
        }
    }

    protected function createAdminUser(): User
    {
        $adminRole = Role::firstOrCreate(['name' => 'Admin']);

        return User::factory()->create([
            'role_id' => $adminRole->id,
        ]);
    }

    protected function createRegularUser(): User
    {
        $userRole = Role::firstOrCreate(['name' => 'User']);

        return User::factory()->create([
            'role_id' => $userRole->id,
        ]);
    }

    protected function createTaskForUser(User $user, array $data = []): Task
    {
        return Task::factory()->create(array_merge([
            'user_id' => $user->id,
        ], $data));
    }

    protected function actingAsAdmin(): User
    {
        $admin = $this->createAdminUser();
        Sanctum::actingAs($admin);
        return $admin;
    }

    protected function actingAsUser(): User
    {
        $user = $this->createRegularUser();
        Sanctum::actingAs($user);
        return $user;
    }
}
