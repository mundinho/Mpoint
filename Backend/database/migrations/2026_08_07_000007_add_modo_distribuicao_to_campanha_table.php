<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE campanha ADD COLUMN modo_distribuicao ENUM('manual','aleatorio') NOT NULL DEFAULT 'manual' AFTER estado");
    }

    public function down(): void
    {
        Schema::table('campanha', function ($table) {
            $table->dropColumn('modo_distribuicao');
        });
    }
};
