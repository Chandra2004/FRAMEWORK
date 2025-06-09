<?php

namespace Database\Migrations;

use {{NAMESPACE}}\App\Schema;

class Migration_2025_06_09_030820_CreateUsersTable
{
    public function up()
    {
        Schema::create('users', function ($table) {
            // Define columns
        });
    }

    public function down()
    {
        Schema::dropIfExists('users');
    }
}
