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
            // Configuraciones Generales
            [
                'clave' => 'general_nombre_institucion',
                'valor' => 'Institución Educativa Modelo',
                'tipo' => 'string',
                'descripcion' => 'Nombre de la Institución Educativa',
                'categoria' => 'general'
            ],
            [
                'clave' => 'general_año_academico',
                'valor' => '2026',
                'tipo' => 'integer',
                'descripcion' => 'Año Académico Actual',
                'categoria' => 'general'
            ],
            [
                'clave' => 'general_email_contacto',
                'valor' => 'contacto@colegio.pe',
                'tipo' => 'string',
                'descripcion' => 'Email de Contacto Institucional',
                'categoria' => 'general'
            ],

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
                'clave' => 'seguridad_proteccion_grados',
                'valor' => 'true',
                'tipo' => 'boolean',
                'descripcion' => 'Protección de Grados (requiere confirmación)',
                'categoria' => 'seguridad'
            ],
            [
                'clave' => 'seguridad_proteccion_secciones',
                'valor' => 'true',
                'tipo' => 'boolean',
                'descripcion' => 'Protección de Secciones (requiere confirmación)',
                'categoria' => 'seguridad'
            ],
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

        $this->command->info('✅ Configuraciones del sistema creadas');
    }
}
