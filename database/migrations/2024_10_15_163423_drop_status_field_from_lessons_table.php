<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->dropColumn('status');
            $table->dropColumn('published_at');

        });
    }

    public function down()
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->timestamp('published_at');
        });
    }
};
