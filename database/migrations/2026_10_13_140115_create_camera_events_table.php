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
        Schema::create('camera_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('camera_id')->nullable()->constrained()->nullOnDelete();
            $table->string('camera_name');
            $table->unsignedInteger('channel');
            $table->string('rule', 100);
            $table->string('status')->default('pending');
            $table->unsignedInteger('recipients_total')->default(0);
            $table->unsignedInteger('recipients_sent')->default(0);
            $table->string('failure_type')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('camera_events');
    }
};
