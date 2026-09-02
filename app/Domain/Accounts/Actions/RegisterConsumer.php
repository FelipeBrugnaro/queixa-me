<?php

declare(strict_types=1);

namespace App\Domain\Accounts\Actions;

use App\Domain\Accounts\Enums\ConsentType;
use App\Domain\Accounts\Enums\UserStatus;
use App\Domain\Accounts\Enums\UserType;
use App\Domain\Accounts\Models\User;
use App\Domain\Accounts\Services\ConsentRecorder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RegisterConsumer
{
    public function __construct(private readonly ConsentRecorder $consents) {}

    /** @param array<string,mixed> $data */
    public function handle(array $data): User
    {
        return DB::transaction(function () use ($data): User {
            $user = User::create([
                'uuid' => (string) Str::uuid(),
                'type' => UserType::Consumer,
                'status' => UserStatus::Active,
                'public_name' => $data['public_name'],
                'name' => trim($data['first_name'].' '.$data['last_name']),
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'birthdate' => $data['birthdate'],
                'gender' => $data['gender'],
                'email' => $data['email'],
                'password' => $data['password'],
                'country' => 'PT',
                'marketing_opt_in' => (bool) ($data['marketing_opt_in'] ?? false),
            ]);

            $this->consents->recordMany([
                ConsentType::Terms,
                ConsentType::Privacy,
                ConsentType::DataProtection,
            ], $user);

            if ($user->marketing_opt_in) {
                $this->consents->record(ConsentType::Marketing, $user);
            }

            return $user;
        });
    }
}
