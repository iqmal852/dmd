<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

use function Laravel\Prompts\password;

/**
 * The only way to change the admin's password outside the profile screen
 * — there is no self-service reset flow (ADR-006, no email
 * infrastructure for a single seeded admin). See
 * plan/phases/phase-08-admin-qr.md M8.1.
 */
class RotateAdminPassword extends Command
{
    protected $signature = 'batu:admin-password {--email= : The admin email to rotate; defaults to config(batu.admin.email)}';

    protected $description = "Rotate the admin user's password";

    public function handle(): int
    {
        $email = $this->option('email') ?: (string) config('batu.admin.email');

        $user = User::query()->where('email', $email)->first();

        if (! $user) {
            $this->error("No user found with email [{$email}].");

            return self::FAILURE;
        }

        $password = password(
            label: "New password for {$user->email}",
            validate: fn (string $value) => strlen($value) >= 8
                ? null
                : 'Password must be at least 8 characters.',
        );

        $confirmation = password(label: 'Confirm the new password');

        if ($password !== $confirmation) {
            $this->error('Passwords did not match. No changes were made.');

            return self::FAILURE;
        }

        $user->forceFill(['password' => $password])->save();

        $this->info("Password updated for {$user->email}.");

        return self::SUCCESS;
    }
}
