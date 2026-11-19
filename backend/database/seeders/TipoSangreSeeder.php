<?php

namespace Database\Seeders;

use App\Models\TipoSangre;
use Illuminate\Database\Seeder;

class TipoSangreSeeder extends Seeder
{
    /**
     * Tabla de compatibilidad de glóbulos rojos.
     * "puede_recibir_de" = tipos de sangre de donantes que le sirven a ese receptor.
     *  - O- es donante universal (aparece en todas las listas).
     *  - AB+ es receptor universal (puede recibir de los 8 tipos).
     */
    public function run(): void
    {
        $tipos = [
            ['codigo' => 'O-',  'grupo' => 'O',  'factor_rh' => '-', 'puede_recibir_de' => ['O-'],                                         'descripcion' => 'Donante universal'],
            ['codigo' => 'O+',  'grupo' => 'O',  'factor_rh' => '+', 'puede_recibir_de' => ['O+', 'O-'],                                   'descripcion' => null],
            ['codigo' => 'A-',  'grupo' => 'A',  'factor_rh' => '-', 'puede_recibir_de' => ['A-', 'O-'],                                   'descripcion' => null],
            ['codigo' => 'A+',  'grupo' => 'A',  'factor_rh' => '+', 'puede_recibir_de' => ['A+', 'A-', 'O+', 'O-'],                       'descripcion' => null],
            ['codigo' => 'B-',  'grupo' => 'B',  'factor_rh' => '-', 'puede_recibir_de' => ['B-', 'O-'],                                   'descripcion' => null],
            ['codigo' => 'B+',  'grupo' => 'B',  'factor_rh' => '+', 'puede_recibir_de' => ['B+', 'B-', 'O+', 'O-'],                       'descripcion' => null],
            ['codigo' => 'AB-', 'grupo' => 'AB', 'factor_rh' => '-', 'puede_recibir_de' => ['AB-', 'A-', 'B-', 'O-'],                      'descripcion' => null],
            ['codigo' => 'AB+', 'grupo' => 'AB', 'factor_rh' => '+', 'puede_recibir_de' => ['AB+', 'AB-', 'A+', 'A-', 'B+', 'B-', 'O+', 'O-'], 'descripcion' => 'Receptor universal'],
        ];

        foreach ($tipos as $tipo) {
            TipoSangre::updateOrCreate(['codigo' => $tipo['codigo']], $tipo);
        }
    }
}
