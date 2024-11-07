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
        Schema::table('courses', function (Blueprint $table) {
            $table->integer('total_lessons')->default(0)->after('who_is_for');
            $table->integer('total_quizzes')->default(0)->after('total_lessons');
            $table->integer('total_assignments')->default(0)->after('total_quizzes');
            $table->integer('total_content')->default(0)->after('total_assignments'); // Sum of lessons, quizzes, and assignments
            $table->integer('duration')->default(0)->after('total_content'); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn(['total_lessons', 'total_quizzes', 'total_assignments', 'total_content', 'duration']);
        });
    }
};
