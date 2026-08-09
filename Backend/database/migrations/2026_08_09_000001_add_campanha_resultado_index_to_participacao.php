<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('participacao', function (Blueprint $table) {
            $table->index(['campanha_id', 'resultado']);
        });
    }

    public function down(): void
    {
        Schema::table('participacao', function (Blueprint $table) {
            $table->dropIndex(['campanha_id', 'resultado']);
        });
    }
};
