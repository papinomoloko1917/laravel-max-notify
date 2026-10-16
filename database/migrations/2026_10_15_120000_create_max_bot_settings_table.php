<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('max_bot_settings', function (Blueprint $table) {
            $table->id();
            $table->text('token');
            $table->unsignedBigInteger('bot_user_id');
            $table->string('bot_name');
            $table->string('bot_username')->nullable();
            $table->timestamps();
        });

        DB::statement('ALTER TABLE max_bot_settings ADD CONSTRAINT max_bot_settings_single_row CHECK (id = 1)');
    }

    public function down(): void
    {
        Schema::dropIfExists('max_bot_settings');
    }
};
