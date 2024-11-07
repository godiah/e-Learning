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
        Schema::create('course_discounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('courses')->onDelete('cascade'); 
            $table->decimal('discount_rate', 5, 2); 
            $table->date('start_date'); 
            $table->date('end_date');
            $table->boolean('is_active')->default(false)->nullable(false);
            $table->timestamps();

            $table->unique(['course_id', 'start_date', 'end_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_discounts');
    }
};
