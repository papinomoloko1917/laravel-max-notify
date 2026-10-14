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
        Schema::table('camera_client', function (Blueprint $table) {
            $table->index('client_id');
        });

        Schema::table('camera_events', function (Blueprint $table) {
            $table->index('camera_id');
            $table->index(['status', 'id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('camera_events', function (Blueprint $table) {
            $table->dropIndex(['status', 'id']);
            $table->dropIndex(['camera_id']);
        });

        Schema::table('camera_client', function (Blueprint $table) {
            $table->dropIndex(['client_id']);
        });
    }
};
