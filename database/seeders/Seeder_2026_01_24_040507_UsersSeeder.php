<?php

namespace Database\Seeders;

use Faker\Factory;
use TheFramework\Database\Seeder;
use TheFramework\Helpers\Helper;

class Seeder_2026_01_24_040507_UsersSeeder extends Seeder
{

    public function run()
    {
        $faker = Factory::create();
        Seeder::setTable('users');

        $data = [];
        for ($i = 0; $i < 10; $i++) {
            $data[] = [
                'uid' => Helper::uuid(36),
                'name' => $faker->name(),
                'email' => $faker->email(),
                'password' => Helper::hash_password('123456'),
            ];
        }

        Seeder::create($data);
    }
}
