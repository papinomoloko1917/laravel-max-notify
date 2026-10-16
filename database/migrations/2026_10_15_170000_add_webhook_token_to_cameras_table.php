<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cameras', function (Blueprint $table) {
            $table->text('webhook_token')->nullable()->after('webhook_password');
        });

        DB::table('cameras')
            ->select('id')
            ->orderBy('id')
            ->eachById(function (object $camera): void {
                DB::table('cameras')
                    ->where('id', $camera->id)
                    ->update([
                        'webhook_token' => Crypt::encryptString(Str::random(64)),
                    ]);
            });
    }

    public function down(): void
    {
        Schema::table('cameras', function (Blueprint $table) {
            $table->dropColumn('webhook_token');
        });
    }
};
