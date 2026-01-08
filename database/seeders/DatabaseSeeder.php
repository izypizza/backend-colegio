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

        // 2. Crear Secciones según estructura real del colegio
        $gradosCreados = \App\Models\Grado::all();
        $estructuraSecciones = [
            '1° Primaria' => ['A', 'B', 'C', 'D', 'E'],
            '2° Primaria' => ['A', 'B', 'C', 'D', 'E'],
            '3° Primaria' => ['A', 'B', 'C', 'D', 'E', 'F'],
            '4° Primaria' => ['A', 'B', 'C', 'D', 'E', 'F'],
            '5° Primaria' => ['A', 'B', 'C', 'D', 'E', 'F'],
            '6° Primaria' => ['A', 'B', 'C', 'D', 'E', 'F'],
            '1° Secundaria' => ['A', 'B', 'C', 'D'],
            '2° Secundaria' => ['A', 'B', 'C', 'D'],
            '3° Secundaria' => ['A', 'B', 'C', 'D'],
            '4° Secundaria' => ['A', 'B', 'C', 'D'],
            '5° Secundaria' => ['A', 'B', 'C', 'D'],
        ];

        foreach ($gradosCreados as $grado) {
            $secciones = $estructuraSecciones[$grado->nombre] ?? ['A', 'B', 'C'];
            foreach ($secciones as $seccion) {
                \App\Models\Seccion::create([
                    'nombre' => $seccion,
                    'grado_id' => $grado->id,
                    'capacidad_maxima' => 40,
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
            $nombreCompleto = $docente->nombres.' '.$docente->apellido_paterno.' '.$docente->apellido_materno;
            $user = User::create([
                'name' => $nombreCompleto,
                'email' => 'docente'.($index + 1).'@colegio.pe',
                'password' => bcrypt('docente'.($index + 1)),
                'role' => 'docente',
                'is_active' => true,
            ]);

            $docente->update(['user_id' => $user->id]);
        }

        // 6. Crear Padres (30 padres con usuarios)
        $padres = \App\Models\Padre::factory(30)->create();

        // Crear usuarios para algunos padres (30% tendrán acceso al sistema)
        $padresConAcceso = $padres->random(10);
        foreach ($padresConAcceso as $index => $padre) {
            $nombreCompleto = $padre->nombres.' '.$padre->apellido_paterno.' '.$padre->apellido_materno;
            $user = User::create([
                'name' => $nombreCompleto,
                'email' => 'padre'.($index + 1).'@colegio.pe',
                'password' => bcrypt('padre'.($index + 1)),
                'role' => 'padre',
                'is_active' => true,
            ]);

            $padre->update(['user_id' => $user->id]);
        }

        // 7. Crear Estudiantes (distribuidos en las secciones con capacidad hasta 40)
        $secciones = \App\Models\Seccion::all();
        $estudiantesCreados = [];
        $docentesDisponibles = \App\Models\Docente::all();
        $estudianteCounter = 1;

        foreach ($secciones as $index => $seccion) {
            // Asignar un tutor único a cada sección
            $tutorIndex = $index % $docentesDisponibles->count();
            $seccion->update(['tutor_id' => $docentesDisponibles[$tutorIndex]->id]);

            // 8-12 estudiantes por sección (realista pero manejable)
            $cantidadEstudiantes = rand(8, 12);
            for ($i = 0; $i < $cantidadEstudiantes; $i++) {
                $estudiante = \App\Models\Estudiante::factory()->create([
                    'seccion_id' => $seccion->id,
                ]);
                
                // Crear usuario para cada estudiante
                $nombreCompleto = $estudiante->nombres.' '.$estudiante->apellido_paterno.' '.$estudiante->apellido_materno;
                $userEstudiante = User::create([
                    'name' => $nombreCompleto,
                    'email' => 'estudiante'.$estudianteCounter.'@colegio.pe',
                    'password' => bcrypt('estudiante'.$estudianteCounter),
                    'role' => 'estudiante',
                    'is_active' => true,
                ]);
                
                $estudiante->update(['user_id' => $userEstudiante->id]);
                $estudiantesCreados[] = $estudiante;
                $estudianteCounter++;

                // Asignar 1-2 padres a cada estudiante
                $padresDisponibles = \App\Models\Padre::inRandomOrder()->limit(rand(1, 2))->get();
                $estudiante->padres()->attach($padresDisponibles);
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
                    'periodo_academico_id' => $periodo2025->id,
                ]);
            }
        }

        // 9. Crear Horarios (más completo)
        $dias = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes'];
        $horas = [
            ['08:00', '08:45'],
            ['08:45', '09:30'],
            ['09:30', '10:15'],
            ['10:30', '11:15'],
            ['11:15', '12:00'],
            ['12:00', '12:45'],
        ];

        $asignaciones = \App\Models\AsignacionDocenteMateria::all();
        
        // Crear horarios para cada sección de forma organizada
        foreach ($secciones as $seccion) {
            $asignacionesSeccion = $asignaciones->where('seccion_id', $seccion->id);
            
            if ($asignacionesSeccion->isEmpty()) {
                continue;
            }
            
            // Distribuir las materias en la semana
            $horariosCreados = [];
            foreach ($asignacionesSeccion as $index => $asignacion) {
                // Cada materia aparece 2-3 veces por semana
                $vecesEnSemana = rand(2, 3);
                
                for ($i = 0; $i < $vecesEnSemana; $i++) {
                    $intentos = 0;
                    do {
                        $dia = $dias[array_rand($dias)];
                        $hora = $horas[array_rand($horas)];
                        $key = $seccion->id . '-' . $dia . '-' . $hora[0];
                        $intentos++;
                    } while (isset($horariosCreados[$key]) && $intentos < 10);
                    
                    if (!isset($horariosCreados[$key])) {
                        \App\Models\Horario::create([
                            'seccion_id' => $asignacion->seccion_id,
                            'materia_id' => $asignacion->materia_id,
                            'dia' => $dia,
                            'hora_inicio' => $hora[0],
                            'hora_fin' => $hora[1],
                        ]);
                        $horariosCreados[$key] = true;
                    }
                }
            }
        }

        // 10. Crear Asistencias (últimos 20 días - desarrollo rápido)
        $estudiantes = \App\Models\Estudiante::all();
        $asignacionesAll = \App\Models\AsignacionDocenteMateria::all();

        foreach ($estudiantes as $estudiante) {
            // Obtener las materias de su sección
            $materiasSeccion = $asignacionesAll->where('seccion_id', $estudiante->seccion_id)
                ->pluck('materia_id')->unique();

            if ($materiasSeccion->isEmpty()) {
                $materiasSeccion = $materiasCreadas->pluck('id')->take(5);
            }

            // Generar asistencias para los últimos 20 días
            for ($i = 0; $i < 20; $i++) {
                $fecha = now()->subDays($i);

                // Solo crear asistencias de lunes a viernes
                if ($fecha->dayOfWeek >= 1 && $fecha->dayOfWeek <= 5) {
                    // Asistencia por cada materia (4-6 materias por día)
                    $materiasDia = $materiasSeccion->count() > 0 
                        ? $materiasSeccion->random(rand(4, min(6, $materiasSeccion->count()))) 
                        : collect();
                        
                    foreach ($materiasDia as $materiaId) {
                        // Validar que la materia existe
                        if (!\App\Models\Materia::find($materiaId)) {
                            continue;
                        }
                        
                        // 90% presente, 10% ausente
                        $presente = fake()->boolean(90);

                        \App\Models\Asistencia::create([
                            'estudiante_id' => $estudiante->id,
                            'materia_id' => $materiaId,
                            'fecha' => $fecha->format('Y-m-d'),
                            'presente' => $presente,
                        ]);
                    }
                }
            }
        }

        // 11. Crear Calificaciones (para todos los estudiantes en todos los períodos)
        $estudiantes = \App\Models\Estudiante::all();
        $asignacionesAll = \App\Models\AsignacionDocenteMateria::all();

        foreach ($estudiantes as $estudiante) {
            // Obtener las materias de su sección a través de asignaciones
            $materiasSeccion = $asignacionesAll->where('seccion_id', $estudiante->seccion_id)
                ->pluck('materia_id')->unique();

            // Si no hay asignaciones, asignar materias básicas manualmente
            if ($materiasSeccion->isEmpty()) {
                $materiasSeccion = $materiasCreadas->pluck('id')->take(7);
            }

            // Crear calificaciones para cada período
            foreach ($periodosCreados as $periodo) {
                foreach ($materiasSeccion as $materiaId) {
                    // Validar que la materia existe
                    if (!\App\Models\Materia::find($materiaId)) {
                        continue;
                    }

                    // Generar notas de manera más realista (distribución normal)
                    $notaBase = rand(11, 18);
                    $variacion = rand(-2, 2);
                    $nota = max(0, min(20, $notaBase + $variacion));

                    // Algunos estudiantes destacados (20%)
                    if (rand(1, 10) > 8) {
                        $nota = rand(17, 20);
                    }

                    // Algunos estudiantes con dificultades (10%)
                    if (rand(1, 10) > 9) {
                        $nota = rand(8, 12);
                    }

                    \App\Models\Calificacion::create([
                        'estudiante_id' => $estudiante->id,
                        'materia_id' => $materiaId,
                        'periodo_academico_id' => $periodo->id,
                        'nota' => $nota,
                    ]);
                }
            }
        }

        // 12. Crear usuarios de prueba para cada rol

        // Usuario Administrador
        User::create([
            'name' => 'Administrador Principal',
            'email' => 'admin@colegio.pe',
            'password' => bcrypt('admin123'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        // Usuario Auxiliar
        User::create([
            'name' => 'Personal Auxiliar',
            'email' => 'auxiliar@colegio.pe',
            'password' => bcrypt('auxiliar123'),
            'role' => 'auxiliar',
            'is_active' => true,
        ]);

        // Usuario Docente Test con registro de docente asociado
        $userDocente = User::create([
            'name' => 'Roberto García López',
            'email' => 'docente@colegio.pe',
            'password' => bcrypt('docente123'),
            'role' => 'docente',
            'is_active' => true,
        ]);

        $docenteTest = \App\Models\Docente::create([
            'nombres' => 'Roberto',
            'apellido_paterno' => 'García',
            'apellido_materno' => 'López',
            'dni' => '12345678',
            'email' => 'roberto.garcia@colegio.pe',
            'telefono' => '987654321',
            'direccion' => 'Av. Los Maestros 123, Cusco',
            'especialidad' => 'Matemáticas',
            'user_id' => $userDocente->id,
        ]);

        // Asignar algunas materias al docente test
        $materiasMath = \App\Models\Materia::where('nombre', 'like', '%Matemática%')->take(2)->get();
        $seccionesTest = \App\Models\Seccion::take(2)->get();
        $periodoActual = \App\Models\PeriodoAcademico::first();

        foreach ($seccionesTest as $seccion) {
            foreach ($materiasMath as $materia) {
                \App\Models\AsignacionDocenteMateria::create([
                    'docente_id' => $docenteTest->id,
                    'materia_id' => $materia->id,
                    'seccion_id' => $seccion->id,
                    'periodo_academico_id' => $periodoActual->id,
                ]);
            }
        }

        // Usuario Padre Test con registro de padre asociado
        $userPadre = User::create([
            'name' => 'Juan Pérez Rojas',
            'email' => 'padre@colegio.pe',
            'password' => bcrypt('padre123'),
            'role' => 'padre',
            'is_active' => true,
        ]);

        $padreTest = \App\Models\Padre::create([
            'nombres' => 'Juan',
            'apellido_paterno' => 'Pérez',
            'apellido_materno' => 'Rojas',
            'dni' => '87654321',
            'email' => 'juan.perez@gmail.com',
            'telefono' => '999888777',
            'direccion' => 'Jr. Las Flores 456, Cusco',
            'ocupacion' => 'Comerciante',
            'user_id' => $userPadre->id,
        ]);

        // Asociar algunos estudiantes al padre test
        $estudiantesTest = \App\Models\Estudiante::take(2)->get();
        foreach ($estudiantesTest as $estudiante) {
            \DB::table('estudiante_padre')->insert([
                'estudiante_id' => $estudiante->id,
                'padre_id' => $padreTest->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Usuario Estudiante Test con registro de estudiante asociado
        $userEstudiante = User::create([
            'name' => 'Diego Martínez Silva',
            'email' => 'estudiante@colegio.pe',
            'password' => bcrypt('estudiante123'),
            'role' => 'estudiante',
            'is_active' => true,
        ]);

        $seccionTest = \App\Models\Seccion::first();
        $estudianteTest = \App\Models\Estudiante::create([
            'nombres' => 'Diego',
            'apellido_paterno' => 'Martínez',
            'apellido_materno' => 'Silva',
            'dni' => '11223344',
            'fecha_nacimiento' => '2010-01-01',
            'seccion_id' => $seccionTest->id,
            'user_id' => $userEstudiante->id,
        ]);

        // Asignar padres al estudiante test
        $padreTest->estudiantes()->attach($estudianteTest->id);

        // Crear calificaciones para el estudiante test en todos los períodos
        $materiasEstudianteTest = $asignaciones->where('seccion_id', $seccionTest->id)
            ->pluck('materia_id')->unique();

        if ($materiasEstudianteTest->isEmpty()) {
            $materiasEstudianteTest = $materiasCreadas->pluck('id')->take(7);
        }

        foreach ($periodosCreados as $periodo) {
            foreach ($materiasEstudianteTest as $materiaId) {
                $notaBase = rand(13, 17);
                \App\Models\Calificacion::create([
                    'estudiante_id' => $estudianteTest->id,
                    'materia_id' => $materiaId,
                    'periodo_academico_id' => $periodo->id,
                    'nota' => $notaBase,
                ]);
            }
        }

        // Crear asistencias para el estudiante test (últimos 20 días)
        for ($i = 0; $i < 20; $i++) {
            $fecha = now()->subDays($i);

            if ($fecha->dayOfWeek >= 1 && $fecha->dayOfWeek <= 5) {
                foreach ($materiasEstudianteTest->random(min(5, $materiasEstudianteTest->count())) as $materiaId) {
                    $presente = fake()->boolean(92);

                    \App\Models\Asistencia::create([
                        'estudiante_id' => $estudianteTest->id,
                        'materia_id' => $materiaId,
                        'fecha' => $fecha->format('Y-m-d'),
                        'presente' => $presente,
                    ]);
                }
            }
        }

        // Ejecutar seeders adicionales
        $this->call([
            BibliotecaSeeder::class,
            EleccionSeeder::class,
            BibliotecarioUserSeeder::class,
        ]);

        $this->command->info('✅ Base de datos poblada con datos del sistema educativo peruano');
        $this->command->info('📊 Grados: '.\App\Models\Grado::count());
        $this->command->info('📚 Secciones: '.\App\Models\Seccion::count());
        $this->command->info('👨‍🏫 Docentes: '.\App\Models\Docente::count());
        $this->command->info('👨‍👩‍👧 Padres: '.\App\Models\Padre::count());
        $this->command->info('👨‍🎓 Estudiantes: '.\App\Models\Estudiante::count());
        $this->command->info('📖 Materias: '.\App\Models\Materia::count());
        $this->command->info('📅 Periodos: '.\App\Models\PeriodoAcademico::count());
        $this->command->info('📝 Asignaciones: '.\App\Models\AsignacionDocenteMateria::count());
        $this->command->info('🕐 Horarios: '.\App\Models\Horario::count());
        $this->command->info('✓ Asistencias: '.\App\Models\Asistencia::count());
        $this->command->info('📊 Calificaciones: '.\App\Models\Calificacion::count());
        $this->command->info('📚 Libros: '.\App\Models\Libro::count());
        $this->command->info('🗳️ Elecciones: '.\App\Models\Eleccion::count());
    }
}
