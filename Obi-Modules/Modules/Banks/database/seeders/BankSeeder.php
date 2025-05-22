<?php

namespace Modules\Banks\database\seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BankSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('banks')->insert([
            ['name' => 'Administradora Andes'],
            ['name' => 'Afianza'],
            ['name' => 'AMH penta hipotecarios'],
            ['name' => 'AMH Servicios Financieros'],
            ['name' => 'Banco Chilena Consolidada'],
            ['name' => 'Banco Seguro HDI'],
            ['name' => 'Banefe'],
            ['name' => 'BBVA (SCOTIABANK)'],
            ['name' => 'BCI'],
            ['name' => 'Bice'],
            ['name' => 'Bnp Paribas Cardif Seguros generales S.A'],
            ['name' => 'Crédito Hipotecario Leasing Mapsa'],
            ['name' => 'Capredena'],
            ['name' => 'Cardif'],
            ['name' => 'Caja los Andes'],
            ['name' => 'Cencosud'],
            ['name' => 'Chile'],
            ['name' => 'Chubb'],
            ['name' => 'CrediChile'],
            ['name' => 'Concreces Leasing'],
            ['name' => 'Condell'],
            ['name' => 'Consorcio'],
            ['name' => 'Coopeuch'],
            ['name' => 'Creditu'],
            ['name' => 'Edwards'],
            ['name' => 'Estado'],
            ['name' => 'Falabella'],
            ['name' => 'Hipotecaria de la construcción'],
            ['name' => 'Hipotecaria la Construccion Leasing'],
            ['name' => 'Hipotecario Andes'],
            ['name' => 'Itaú Corpbanca'],
            ['name' => 'Leasing'],
            ['name' => 'Leasing Hipotecaria Andes'],
            ['name' => 'Leasing Unidad'],
            ['name' => 'Liberty'],
            ['name' => 'Los Andes'],
            ['name' => 'Mapfre'],
            ['name' => 'Metlife'],
            ['name' => 'Mutualidad de Carabineros (Mutucar)'],
            ['name' => 'MYV Hipotecarios'],
            ['name' => 'MYV Mutuos'],
            ['name' => 'Penta Vida'],
            ['name' => 'Renta Nacional'],
            ['name' => 'Ripley'],
            ['name' => 'Security'],
            ['name' => 'Santander'],
            ['name' => 'Seguro Consorcio'],
            ['name' => 'Seguro Falabella'],
            ['name' => 'Scotiabank'],
            ['name' => 'Sura'],
            ['name' => 'Zurich'],
        ]);
    }
}



