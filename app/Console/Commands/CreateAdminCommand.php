<?php

namespace App\Console\Commands;

use App\Enums\AccountStatus;
use App\Enums\UserRole;
use App\Models\Department;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class CreateAdminCommand extends Command
{
    protected $signature = 'tsts:create-admin';

    protected $description = 'Interactively create the first administrator account';

    public function handle(): int
    {
        if (User::where('role', UserRole::Admin->value)->exists()) {
            $this->warn('An administrator account already exists. You can still create another one below.');

            if (! $this->confirm('Continue creating another administrator?', true)) {
                return self::SUCCESS;
            }
        }

        $firstName = $this->ask('First name');
        $lastName = $this->ask('Last name');
        $email = $this->ask('Email address');
        $password = $this->secret('Password (min 10 characters, mixed case, one number)');
        $passwordConfirmation = $this->secret('Confirm password');

        $departments = Department::query()->orderBy('name')->pluck('name', 'id');

        $departmentId = $this->choice('Department (optional)', $departments->prepend('None', '')->all(), default: 0);

        try {
            $validator = validator([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'password' => $password,
                'department_id' => $departmentId,
            ], [
                'first_name' => ['required', 'string', 'max:255'],
                'last_name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255', 'unique:users,email'],
                'department_id' => ['nullable', 'integer', 'exists:departments,id'],
                'password' => ['required', 'confirmed', Password::min(10)->mixedCase()->numbers(), 'max:72'],
                'password_confirmation' => ['required'],
            ]);

            $validator->setData(array_merge($validator->getData(), ['password_confirmation' => $passwordConfirmation]));

            $validator->validate();
        } catch (ValidationException $e) {
            foreach ($e->errors() as $error) {
                $this->error('- '.implode(' ', $error));
            }

            return self::FAILURE;
        }

        $user = User::create([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'department_id' => is_numeric($departmentId) ? (int) $departmentId : null,
            'role' => UserRole::Admin,
            'is_active' => true,
            'account_status' => AccountStatus::Approved,
            'must_change_password' => false,
            'password' => Hash::make($password),
        ]);

        $this->line('');
        $this->info('Administrator account created successfully.');
        $this->table(
            ['Field', 'Value'],
            [
                ['Name', $user->full_name],
                ['Email', $user->email],
                ['Role', 'Administrator'],
            ]
        );

        return self::SUCCESS;
    }
}
