<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Crear Grados del sistema educativo peruano
        $grados = [
            // Primaria
            ['nombre' => '1° Primaria'],
            ['nombre' => '2° Primaria'],
            ['nombre' => '3° Primaria'],
            ['nombre' => '4° Primaria'],
            ['nombre' => '5° Primaria'],
            ['nombre' => '6° Primaria'],
            // Secundaria
            ['nombre' => '1° Secundaria'],
            ['nombre' => '2° Secundaria'],
            ['nombre' => '3° Secundaria'],
            ['nombre' => '4° Secundaria'],
            ['nombre' => '5° Secundaria'],
        ];

        foreach ($grados as $grado) {
            \App\Models\Grado::create($grado);
        }

        // 2. Crear Secciones para cada grado (A, B, C)
        $gradosCreados = \App\Models\Grado::all();
        foreach ($gradosCreados as $grado) {
            foreach (['A', 'B', 'C'] as $seccion) {
                \App\Models\Seccion::create([
                    'nombre' => $seccion,
                    'grado_id' => $grado->id
                ]);
            }
        }

        // 3. Crear Materias según el Currículo Nacional Peruano
        $materias = [
            ['nombre' => 'Matemática'],
            ['nombre' => 'Comunicación'],
            ['nombre' => 'Ciencias Sociales'],
            ['nombre' => 'Ciencia y Tecnología'],
            ['nombre' => 'Educación Física'],
            ['nombre' => 'Arte y Cultura'],
            ['nombre' => 'Inglés'],
            ['nombre' => 'Educación Religiosa'],
            ['nombre' => 'Tutoría'],
            ['nombre' => 'Educación para el Trabajo'],
            ['nombre' => 'Desarrollo Personal, Ciudadanía y Cívica'],
        ];

        foreach ($materias as $materia) {
            \App\Models\Materia::create($materia);
        }

        // 4. Crear Periodos Académicos 2025
        $periodos = [
            ['nombre' => 'I Bimestre 2025', 'anio' => 2025],
            ['nombre' => 'II Bimestre 2025', 'anio' => 2025],
            ['nombre' => 'III Bimestre 2025', 'anio' => 2025],
            ['nombre' => 'IV Bimestre 2025', 'anio' => 2025],
        ];

        foreach ($periodos as $periodo) {
            \App\Models\PeriodoAcademico::create($periodo);
        }

        // 5. Crear Docentes (15 docentes con usuarios)
        $docentes = \App\Models\Docente::factory(15)->create();
        
        // Crear usuarios para cada docente
        foreach ($docentes as $index => $docente) {
            $user = User::create([
                'name' => $docente->nombre,
                'email' => 'docente' . ($index + 1) . '@colegio.pe',
                'password' => bcrypt('password'),
                'role' => 'docente',
                'is_active' => true,
            ]);
            
            $docente->update(['user_id' => $user->id]);
        }

        // 6. Crear Padres (50 padres con usuarios)
        $padres = \App\Models\Padre::factory(50)->create();
        
        // Crear usuarios para algunos padres (30% tendrán acceso al sistema)
        $padresConAcceso = $padres->random(15);
        foreach ($padresConAcceso as $index => $padre) {
            $user = User::create([
                'name' => $padre->nombre,
                'email' => 'padre' . ($index + 1) . '@colegio.pe',
                'password' => bcrypt('password'),
                'role' => 'padre',
                'is_active' => true,
            ]);
            
            $padre->update(['user_id' => $user->id]);
        }

        // 7. Crear Estudiantes (100 estudiantes distribuidos en las secciones)
        $secciones = \App\Models\Seccion::all();
        foreach ($secciones as $seccion) {
            // 3-4 estudiantes por sección
            $cantidadEstudiantes = rand(3, 4);
            for ($i = 0; $i < $cantidadEstudiantes; $i++) {
                $estudiante = \App\Models\Estudiante::factory()->create([
                    'seccion_id' => $seccion->id
                ]);

                // Asignar 1-2 padres a cada estudiante
                $padres = \App\Models\Padre::inRandomOrder()->limit(rand(1, 2))->get();
                $estudiante->padres()->attach($padres);
            }
        }

        // 8. Crear Asignaciones Docente-Materia
        $docentes = \App\Models\Docente::all();
        $materiasCreadas = \App\Models\Materia::all();
        $periodosCreados = \App\Models\PeriodoAcademico::all();
        $periodo2025 = $periodosCreados->first();

        foreach ($secciones as $seccion) {
            // Asignar 5-7 materias por sección
            $materiasAsignadas = $materiasCreadas->random(rand(5, 7));
            
            foreach ($materiasAsignadas as $materia) {
                $docente = $docentes->random();
                
                \App\Models\AsignacionDocenteMateria::create([
                    'docente_id' => $docente->id,
                    'materia_id' => $materia->id,
                    'seccion_id' => $seccion->id,
                    'periodo_academico_id' => $periodo2025->id
                ]);
            }
        }

        // 9. Crear Horarios
        $dias = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes'];
        $horas = [
            ['08:00', '08:45'],
            ['08:45', '09:30'],
            ['09:30', '10:15'],
            ['10:30', '11:15'],
            ['11:15', '12:00'],
        ];

        $asignaciones = \App\Models\AsignacionDocenteMateria::all();
        foreach ($asignaciones->take(50) as $asignacion) { // Solo algunas asignaciones para no saturar
            $dia = $dias[array_rand($dias)];
            $hora = $horas[array_rand($horas)];
            
            \App\Models\Horario::create([
                'seccion_id' => $asignacion->seccion_id,
                'materia_id' => $asignacion->materia_id,
                'dia' => $dia,
                'hora_inicio' => $hora[0],
                'hora_fin' => $hora[1]
            ]);
        }

        // 10. Crear Asistencias (últimos 30 días)
        $estudiantes = \App\Models\Estudiante::all();
        foreach ($estudiantes->take(30) as $estudiante) { // Solo algunos estudiantes
            for ($i = 0; $i < 10; $i++) { // 10 registros de asistencia por estudiante
                $materia = $materiasCreadas->random();
                $fecha = now()->subDays(rand(1, 30));
                
                \App\Models\Asistencia::create([
                    'estudiante_id' => $estudiante->id,
                    'materia_id' => $materia->id,
                    'fecha' => $fecha,
                    'presente' => rand(0, 10) > 1 // 90% de asistencia
                ]);
            }
        }

        // 11. Crear Calificaciones
        foreach ($estudiantes->take(30) as $estudiante) { // Solo algunos estudiantes
            $materiasEstudiante = $materiasCreadas->random(rand(5, 8));
            
            foreach ($materiasEstudiante as $materia) {
                \App\Models\Calificacion::create([
                    'estudiante_id' => $estudiante->id,
                    'materia_id' => $materia->id,
                    'periodo_academico_id' => $periodo2025->id,
                    'nota' => rand(11, 20) // Notas del sistema vigesimal peruano (0-20)
                ]);
            }
        }

        // 12. Crear usuarios de prueba para cada rol
        
        // Usuario Administrador
        User::create([
            'name' => 'Administrador',
            'email' => 'admin@colegio.pe',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        // Usuario Docente Test
        User::create([
            'name' => 'Docente Test',
            'email' => 'docente@colegio.pe',
            'password' => bcrypt('password'),
            'role' => 'docente',
            'is_active' => true,
        ]);

        // Usuario Padre Test
        User::create([
            'name' => 'Padre Test',
            'email' => 'padre@colegio.pe',
            'password' => bcrypt('password'),
            'role' => 'padre',
            'is_active' => true,
        ]);

        // Usuario Estudiante Test
        User::create([
            'name' => 'Estudiante Test',
            'email' => 'estudiante@colegio.pe',
            'password' => bcrypt('password'),
            'role' => 'estudiante',
            'is_active' => true,
        ]);

        $this->command->info('✅ Base de datos poblada con datos del sistema educativo peruano');
        $this->command->info('📊 Grados: ' . \App\Models\Grado::count());
        $this->command->info('📚 Secciones: ' . \App\Models\Seccion::count());
        $this->command->info('👨‍🏫 Docentes: ' . \App\Models\Docente::count());
        $this->command->info('👨‍👩‍👧 Padres: ' . \App\Models\Padre::count());
        $this->command->info('👨‍🎓 Estudiantes: ' . \App\Models\Estudiante::count());
        $this->command->info('📖 Materias: ' . \App\Models\Materia::count());
        $this->command->info('📅 Periodos: ' . \App\Models\PeriodoAcademico::count());
        $this->command->info('📝 Asignaciones: ' . \App\Models\AsignacionDocenteMateria::count());
        $this->command->info('🕐 Horarios: ' . \App\Models\Horario::count());
        $this->command->info('✓ Asistencias: ' . \App\Models\Asistencia::count());
        $this->command->info('📊 Calificaciones: ' . \App\Models\Calificacion::count());
    }
}