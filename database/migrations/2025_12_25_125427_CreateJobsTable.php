<?php

namespace Database\Migrations;

use TheFramework\App\Schema;

class Migration_2025_12_25_125427_CreateJobsTable
{
    public function up()
    {
        Schema::create('jobs', function ($table) {
            $table->id();
            $table->string('queue')->default('default')->index('queue');
            $table->longText('payload');
            $table->integer('attempts')->unsigned()->default(0);
            $table->integer('reserved_at')->unsigned()->nullable();
            $table->integer('available_at')->unsigned();
            $table->integer('created_at')->unsigned();
        });
    }

    public function down()
    {
        Schema::dropIfExists('jobs');
    }
}