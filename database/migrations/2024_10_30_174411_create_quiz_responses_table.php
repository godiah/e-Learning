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
        Schema::create('quiz_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_attempt_id')->constrained()->onDelete('cascade');
            $table->foreignId('quiz_question_id')->constrained()->onDelete('cascade');
            $table->foreignId('selected_answer_id')->nullable()->constrained('quiz_answers')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->float('score')->default(0);
            $table->boolean('is_correct')->default(false);
            $table->timestamps();

            // Ensure unique response per question per attempt per user
            $table->unique(['quiz_attempt_id', 'quiz_question_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quiz_responses');
    }
};
