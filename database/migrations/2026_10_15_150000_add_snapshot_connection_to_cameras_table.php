<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cameras', function (Blueprint $table) {
            $table->string('snapshot_base_url', 2048)->nullable()->after('channel');
            $table->string('snapshot_username')->nullable()->after('snapshot_base_url');
            $table->text('snapshot_password')->nullable()->after('snapshot_username');

            $table->dropUnique(['channel']);
            $table->unique(['snapshot_base_url', 'channel']);
        });
    }

    public function down(): void
    {
        Schema::table('cameras', function (Blueprint $table) {
            $table->dropUnique(['snapshot_base_url', 'channel']);
            $table->dropColumn([
                'snapshot_base_url',
                'snapshot_username',
                'snapshot_password',
            ]);
            $table->unique('channel');
        });
    }
};
