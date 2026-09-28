<?php

namespace Database\Factories;

use App\Enums\AccountStatus;
use App\Enums\UserRole;
use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        $firstName = $this->faker->firstName();
        $lastName = $this->faker->lastName();

        return [
            'employee_id' => $this->faker->optional()->numerify('EMP-####'),
            'first_name' => $firstName,
            'middle_name' => $this->faker->optional()->lastName(),
            'last_name' => $lastName,
            'email' => strtolower($firstName.'.'.$lastName).'@'.$this->faker->safeEmailDomain(),
            'contact_number' => $this->faker->optional()->phoneNumber(),
            'department_id' => Department::factory(),
            'position' => $this->faker->optional()->jobTitle(),
            'role' => UserRole::Requester,
            'profile_photo_path' => null,
            'is_active' => true,
            'account_status' => AccountStatus::Approved,
            'must_change_password' => false,
            'last_login_at' => null,
            'password' => Hash::make('Password123!'),
            'remember_token' => Str::random(10),
        ];
    }

    public function admin(): static
    {
        return $this->state(fn () => ['role' => UserRole::Admin]);
    }

    public function support(): static
    {
        return $this->state(fn () => ['role' => UserRole::Support]);
    }

    public function requester(): static
    {
        return $this->state(fn () => ['role' => UserRole::Requester]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    public function pendingApproval(): static
    {
        return $this->state(fn () => ['is_active' => false, 'account_status' => AccountStatus::Pending]);
    }

    public function rejected(): static
    {
        return $this->state(fn () => ['is_active' => false, 'account_status' => AccountStatus::Rejected]);
    }

    public function mustChangePassword(): static
    {
        return $this->state(fn () => ['must_change_password' => true]);
    }
}
