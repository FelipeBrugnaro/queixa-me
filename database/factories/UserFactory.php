<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Accounts\Enums\Gender;
use App\Domain\Accounts\Enums\UserType;
use App\Domain\Accounts\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    protected static ?string $password = null;

    public function definition(): array
    {
        $first = fake()->firstName();
        $last = fake()->lastName();

        return [
            'uuid' => (string) Str::uuid(),
            'type' => UserType::Consumer,
            'status' => 'active',
            'public_name' => Str::lower($first).fake()->unique()->numberBetween(10, 9999),
            'name' => $first.' '.$last,
            'first_name' => $first,
            'last_name' => $last,
            'birthdate' => fake()->dateTimeBetween('-60 years', '-18 years'),
            'gender' => fake()->randomElement(Gender::cases()),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'phone' => '9'.fake()->numerify('########'),
            'country' => 'PT',
            'district' => fake()->randomElement(['Lisboa', 'Porto', 'Braga', 'Faro', 'Coimbra', 'Setúbal', 'Aveiro']),
            'locality' => fake()->city(),
            'marketing_opt_in' => fake()->boolean(30),
            'remember_token' => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn () => ['email_verified_at' => null]);
    }

    public function business(): static
    {
        return $this->state(fn () => [
            'type' => UserType::Business,
            'public_name' => null,
            'birthdate' => null,
            'gender' => null,
        ]);
    }

    public function moderator(): static
    {
        return $this->state(fn () => ['type' => UserType::Moderator, 'is_staff' => true]);
    }

    public function admin(): static
    {
        return $this->state(fn () => ['type' => UserType::Admin, 'is_staff' => true]);
    }
}
