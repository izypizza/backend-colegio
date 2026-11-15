<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Configuracion;

class ConfiguracionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $configuraciones = [
            // Módulos del Sistema
            [
                'clave' => 'modulos_biblioteca',
                'valor' => 'true',
                'tipo' => 'boolean',
                'descripcion' => 'Módulo de Biblioteca',
                'categoria' => 'modulos'
            ],
            [
                'clave' => 'modulos_elecciones',
                'valor' => 'true',
                'tipo' => 'boolean',
                'descripcion' => 'Módulo de Elecciones',
                'categoria' => 'modulos'
            ],
            [
                'clave' => 'modulos_permisos',
                'valor' => 'true',
                'tipo' => 'boolean',
                'descripcion' => 'Módulo de Permisos Auxiliares',
                'categoria' => 'modulos'
            ],
            [
                'clave' => 'modulos_calificaciones',
                'valor' => 'true',
                'tipo' => 'boolean',
                'descripcion' => 'Módulo de Calificaciones',
                'categoria' => 'modulos'
            ],
            [
                'clave' => 'modulos_asistencias',
                'valor' => 'true',
                'tipo' => 'boolean',
                'descripcion' => 'Módulo de Asistencias',
                'categoria' => 'modulos'
            ],
            [
                'clave' => 'modulos_horarios',
                'valor' => 'true',
                'tipo' => 'boolean',
                'descripcion' => 'Módulo de Horarios',
                'categoria' => 'modulos'
            ],

            // Seguridad
            [
                'clave' => 'seguridad_intentos_login',
                'valor' => '5',
                'tipo' => 'integer',
                'descripcion' => 'Máximo de Intentos de Login',
                'categoria' => 'seguridad'
            ],

            // Sistema
            [
                'clave' => 'sistema_modo_mantenimiento',
                'valor' => 'false',
                'tipo' => 'boolean',
                'descripcion' => 'Modo de Mantenimiento',
                'categoria' => 'sistema'
            ],
            [
                'clave' => 'sistema_mensaje_mantenimiento',
                'valor' => 'El sistema está en mantenimiento. Por favor, intenta nuevamente más tarde.',
                'tipo' => 'string',
                'descripcion' => 'Mensaje de Mantenimiento',
                'categoria' => 'sistema'
            ],
        ];

        foreach ($configuraciones as $config) {
            Configuracion::updateOrCreate(
                ['clave' => $config['clave']],
                $config
            );
        }

        $this->command->info('Configuraciones del sistema creadas');
    }
}
