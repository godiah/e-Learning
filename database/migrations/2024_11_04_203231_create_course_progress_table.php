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
        Schema::create('course_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->float('quiz_average')->default(0);
            $table->float('assignment_average')->default(0);
            $table->float('total_grade')->default(0);
            $table->integer('completed_items_count')->default(0);
            $table->integer('total_items_count')->default(0);
            $table->enum('status', ['in_progress', 'completed', 'failed'])->default('in_progress');
            $table->timestamps();

            // Unique constraint to prevent duplicate records
            $table->unique(['course_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_progress');
    }
};
