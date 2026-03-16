<?php

namespace Database\Factories;

use TheFramework\App\Database\Factory;
use TheFramework\Models\User;

/**
 * Factory untuk model User.
 *
 * Usage:
 *   User::factory()->create();             // 1 user, simpan ke DB
 *   User::factory()->count(50)->create();  // 50 user, simpan ke DB
 *   User::factory()->make();               // 1 user, tanpa simpan
 *   User::factory()->state('admin')->create(); // 1 admin
 *
 * Dengan Seeder:
 *   public function run(): void {
 *       User::factory()->count(100)->create();
 *   }
 */
class UserFactory extends Factory
{
    /**
     * Nama model yang dihasilkan factory ini.
     */
    public function model(): string
    {
        return User::class;
    }

    /**
     * Definisi atribut default.
     */
    public function definition(): array
    {
        return [
            'uid'              => $this->fake()->uuid(),
            'name'             => $this->fake()->name(),
            'email'            => $this->fake()->unique()->safeEmail(),
            'password'         => password_hash('password', PASSWORD_BCRYPT),
            'profile_picture'  => null,
            'created_at'       => date('Y-m-d H:i:s'),
            'updated_at'       => date('Y-m-d H:i:s'),
        ];
    }

    // ─── STATES ─────────────────────────────────────────────

    /**
     * State: User dengan email terverifikasi.
     * Usage: User::factory()->state('verified')->create();
     */
    public function verified(): callable
    {
        return fn() => [
            'email_verified_at' => date('Y-m-d H:i:s'),
        ];
    }

    /**
     * State: User tanpa profile picture.
     * Usage: User::factory()->state('withoutAvatar')->create();
     */
    public function withoutAvatar(): callable
    {
        return fn() => [
            'profile_picture' => null,
        ];
    }
}
