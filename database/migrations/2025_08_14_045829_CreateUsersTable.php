<?php

namespace Database\Migrations;

use TheFramework\App\Schema;

class Migration_2025_08_14_045829_CreateUsersTable
{
    public function up()
    {
        Schema::create('users', function ($table) {
            $table->id();
            $table->string('uid')->unique();

            $table->string('name');
            $table->string('email');
            $table->string('password');
            $table->string('profile_picture')->nullable();
            $table->boolean('is_active')->default(1);
            $table->boolean('is_deleted')->default(0);

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('users');
    }
}
