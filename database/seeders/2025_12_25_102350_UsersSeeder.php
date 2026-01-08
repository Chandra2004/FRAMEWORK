<?php

namespace Database\Seeders;

use Faker\Factory;
use TheFramework\Database\Seeder;
use TheFramework\Helpers\Helper;

class Seeder_2025_12_25_102350_UsersSeeder
{

    public function run()
    {
        $faker = Factory::create();
        Seeder::setTable('users');

        for ($i = 0; $i < 10; $i++) {
            Seeder::create([
                [
                    'uid' => Helper::uuid(36),
                    'name' => $faker->name(),
                    'email' => $faker->email(),
                    'password' => Helper::hash_password('123456'),
                    'created_at' => Helper::updateAt(),
                    'updated_at' => Helper::updateAt()
                ]
            ]);
        }
    }
}
