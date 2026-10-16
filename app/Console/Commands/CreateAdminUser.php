<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class CreateAdminUser extends Command
{
    /** @var string */
    protected $signature = 'admin:create {--name=} {--email=}';

    /** @var string */
    protected $description = 'Create the administrator account interactively';

    public function handle(): int
    {
        $name = (string) ($this->option('name') ?: $this->ask('Имя администратора'));
        $email = (string) ($this->option('email') ?: $this->ask('Email администратора'));
        $password = (string) $this->secret('Пароль (минимум 12 символов)');
        $confirmation = (string) $this->secret('Повторите пароль');

        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'password_confirmation' => $confirmation,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(12)],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ]);

        $user->forceFill(['email_verified_at' => now()])->save();

        $this->info("Администратор {$email} создан.");

        return self::SUCCESS;
    }
}
