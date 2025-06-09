<?php

namespace Database\Migrations;

use {{NAMESPACE}}\App\Schema;

class CreateUsersTable {
    public function up() {
        Schema::create('users', function ($table) {
            $table->increments('id');
            $table->text('name', 100);
            $table->text('email')->unique();
            $table->text('password');
            $table->string('profile_picture')->nullable();
            $table->boolean('is_active')->default(1);
            $table->timestamp('created_at');
            $table->timestamp('updated_at');
        });
    }

    public function down() {
        Schema::dropIfExists('users');
    }
}
