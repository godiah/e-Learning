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
            $table->text('detailed_description')->nullable()->after('description');
            $table->string('language')->default('English')->after('level');
            $table->timestamp('last_updated')->useCurrent()->after('language');
            $table->json('objectives')->nullable()->after('last_updated');
            $table->integer('video_length')->default(0)->after('objectives')->comment('Total video length in minutes');
            $table->json('requirements')->nullable()->after('video_length');
            $table->json('who_is_for')->nullable()->after('requirements');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn([
                'detailed_description',
                'language',
                'last_updated',
                'objectives',
                'video_length',                
                'requirements',
                'who_is_for'
            ]);
        });
    }
};
