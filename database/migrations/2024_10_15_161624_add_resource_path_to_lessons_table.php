<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->dropColumn('resources'); // Drop the existing JSON column
            $table->string('resource_path')->nullable(); // Add a new column for file path
        });
    }

    public function down()
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->json('resources')->nullable();
            $table->dropColumn('resource_path');
        });
    }
};
