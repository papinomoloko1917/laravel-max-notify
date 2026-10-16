<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('camera_events', function (Blueprint $table) {
            $table->string('rule_title')->nullable()->after('rule');
        });
    }

    public function down(): void
    {
        Schema::table('camera_events', function (Blueprint $table) {
            $table->dropColumn('rule_title');
        });
    }
};
