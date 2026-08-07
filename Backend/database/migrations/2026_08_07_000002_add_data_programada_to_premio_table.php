<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('premio', function (Blueprint $table) {
            $table->timestamp('data_programada')->nullable()->after('valor_estimado');
            $table->boolean('entregue')->default(false)->after('data_programada');
        });
    }

    public function down(): void
    {
        Schema::table('premio', function (Blueprint $table) {
            $table->dropColumn(['data_programada', 'entregue']);
        });
    }
};
