<?php

namespace Database\Factories;

use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class UserFactory extends Factory
{
    public function definition(): array
    {
        // Get or create user role
        $userRole = Role::where('name', 'User')->first();
        
        if (!$userRole) {
            $userRole = Role::create(['name' => 'User', 'description' => 'Regular User']);
        }

        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'password' => Hash::make('password'),
            'role_id' => $userRole->id,
        ];
    }

    public function admin(): static
    {
        // Get or create admin role
        $adminRole = Role::where('name', 'Admin')->first();
        
        if (!$adminRole) {
            $adminRole = Role::create(['name' => 'Admin', 'description' => 'Administrator']);
        }

        return $this->state(fn (array $attributes) => [
            'role_id' => $adminRole->id,
        ]);
    }
}
