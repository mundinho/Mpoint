<?php

namespace Database\Seeders;

use App\Models\Administrador;
use Illuminate\Database\Seeder;

class AdministradorSeeder extends Seeder
{
    public function run(): void
    {
        Administrador::firstOrCreate(
            ['telefone' => env('ADMIN_SEED_TELEFONE', '258840000000')],
            ['nome' => env('ADMIN_SEED_NOME', 'Administrador'), 'ativo' => true]
        );
    }
}
