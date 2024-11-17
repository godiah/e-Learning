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
        Schema::create('subcontent_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('lesson_id')->constrained()->onDelete('cascade');
            $table->foreignId('subcontent_id')->constrained('lesson_subcontents')->onDelete('cascade');
            $table->integer('watch_time')->default(0); // Track watch time in minutes
            $table->boolean('is_completed')->default(false);
            $table->integer('last_position')->nullable(); // Tracks the last watched position
            $table->timestamp('completed_at')->nullable(); // Time when subcontent was completed
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subcontent_progress');
    }
};
