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
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('transaction_id')->nullable(); // Pesapal transaction ID
            $table->string('tracking_id')->nullable(); // Pesapal tracking ID
            $table->string('reference')->unique(); // Our internal reference
            $table->decimal('amount', 10, 2);
            $table->string('currency')->default('KES');
            $table->string('payment_method')->nullable(); // MPESA, CARD, etc.
            $table->string('status')->default('pending'); // pending, completed, failed, cancelled
            $table->json('payment_data')->nullable(); // Store additional payment data
            $table->text('ipn_url')->nullable(); // Instant Payment Notification URL
            $table->timestamp('paid_at')->nullable(); // When payment was confirmed
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
    }
};
