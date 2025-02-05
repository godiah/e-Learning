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
        Schema::create('conversion_tracking', function (Blueprint $table) {
            $table->id();
            $table->foreignId('click_tracking_id')->constrained('click_tracking')->onDelete('cascade');
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->decimal('sale_amount', 10, 2);            
            $table->timestamp('converted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversion_tracking');
    }
};
