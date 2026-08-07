<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('log', function (Blueprint $table) {
            $table->id();
            $table->string('hash', 64)->unique();
            $table->string('previos_hash', 64);
            $table->string('class');
            $table->string('action');
            $table->boolean('success')->default(true);
            $table->timestamp('timestamp');
            $table->text('description')->nullable();
            $table->string('device_id')->nullable();
            $table->string('device_ip', 45)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('log');
    }
};
