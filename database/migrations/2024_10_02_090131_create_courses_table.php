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
        Schema::create('courses', function (Blueprint $table) {
        $table->id();
        $table->foreignId('instructor_id')->constrained('users');
        $table->foreignId('category_id')->constrained('categories');
        $table->string('title');
        $table->text('description');
        $table->decimal('price', 8, 2);
        $table->enum('level', ['beginner', 'intermediate', 'advanced']);
        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
