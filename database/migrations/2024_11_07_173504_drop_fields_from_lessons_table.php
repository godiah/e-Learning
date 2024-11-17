<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->dropColumn(['video_url', 'order_index', 'video_duration', 'resource_path']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->string('video_url')->nullable();
            $table->integer('order_index')->nullable();
            $table->integer('video_duration')->nullable();
            $table->string('resource_path')->nullable();
        });
    }
};
