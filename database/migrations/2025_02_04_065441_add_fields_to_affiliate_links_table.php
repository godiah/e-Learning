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
        Schema::table('affiliate_links', function (Blueprint $table) {
            // Drop Previous Fields
            $table->dropForeign(['affiliate_id']);
            $table->dropUnique(['affiliate_id']);
            $table->dropColumn('code');

            // Add the new fields
            $table->foreignId('course_id')->constrained()->onDelete('cascade')->after('affiliate_id');
            $table->string('tracking_code')->unique()->after('course_id');
            $table->string('short_url')->unique()->after('tracking_code');
            $table->decimal('commission_rate', 5, 2)->nullable()->default(10)->after('short_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('affiliate_links', function (Blueprint $table) {

            // $table->unique('affiliate_id');

            $table->dropColumn(['course_id', 'tracking_code', 'short_url', 'commission_rate']);

            $table->string('code')->unique();
        });
    }
};
