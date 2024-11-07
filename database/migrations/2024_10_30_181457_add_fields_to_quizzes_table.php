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
        Schema::table('quizzes', function (Blueprint $table) {
            $table->integer('time_limit')->after('title');
            $table->integer('max_attempts')->default(1)->after('time_limit');                   
            $table->text('instructions')->nullable()->after('max_attempts');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            $table->dropColumn(['time_limit', 'max_attempts', 'instructions']);
        });
    }
};
