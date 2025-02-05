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
        Schema::create('click_tracking', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affiliate_link_id')->constrained()->onDelete('cascade');
            $table->string('ip_address');
            $table->string('user_agent');
            $table->timestamp('clicked_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('click_tracking');
    }
};
