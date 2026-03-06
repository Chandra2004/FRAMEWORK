<?php

namespace Database\Seeders;

use Faker\Factory;
use TheFramework\Database\Seeder;
use TheFramework\Helpers\Helper;

class Seeder_2026_02_27_124238_UserSeeder extends Seeder
{

    public function run(): void
    {
        $faker = Factory::create();

        $this->table('users')->insert([
            [
                'uid' => Helper::uuid(),
                'name' => $faker->name,
                'email' => $faker->email,
                'password' => Helper::hash_password('password123') // ⚠️ WARNING: Development only!
            ]
        ]);
    }
}
