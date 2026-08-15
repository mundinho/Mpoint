<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('premio', function (Blueprint $table) {
            $table->foreignId('campanha_premio_id')->nullable()->after('campanha_id')->constrained('campanha_premio')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('premio', function (Blueprint $table) {
            $table->dropConstrainedForeignId('campanha_premio_id');
        });
    }
};
