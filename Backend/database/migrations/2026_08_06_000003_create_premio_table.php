<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('premio', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campanha_id')->constrained('campanha')->cascadeOnDelete();
            $table->string('descricao');
            $table->decimal('valor_estimado', 12, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('premio');
    }
};
