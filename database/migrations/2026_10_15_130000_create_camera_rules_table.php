<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('camera_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('camera_id')->constrained()->cascadeOnDelete();
            $table->string('code', 100);
            $table->string('title');
            $table->timestamps();

            $table->unique(['camera_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('camera_rules');
    }
};
