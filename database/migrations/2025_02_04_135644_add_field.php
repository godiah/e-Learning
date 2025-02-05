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
        Schema::table('conversion_tracking', function (Blueprint $table) {
            $table->unsignedBigInteger('affiliate_link_id')->nullable()->after('id');
            $table->foreign('affiliate_link_id')
                  ->references('id')
                  ->on('affiliate_links')
                  ->onDelete('set null');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('conversion_tracking', function (Blueprint $table) {
            //
        });
    }
};
