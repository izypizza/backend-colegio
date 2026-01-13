<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Grado;
use App\Models\Seccion;
use App\Models\Materia;
use App\Models\PeriodoAcademico;
use App\Models\Docente;
use App\Models\Padre;
use App\Models\Estudiante;
use App\Models\AsignacionDocenteMateria;
use App\Models\Horario;
use App\Models\Asistencia;
use App\Models\Calificacion;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    // Configuración para testing rápido
    private const ESTUDIANTES_POR_SECCION = [6, 10]; // min, max
    private const DIAS_ASISTENCIA = 10; // Reducido de 20 a 10
    private const MATERIAS_POR_DIA = 3; // Reducido de 4-6 a 3
    
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Crear estructura base (grados, secciones, materias, períodos)
        $this->crearEstructuraBase();

        // 2. Crear personal y padres
        $docentes = $this->crearDocentes(15);
        $padres = $this->crearPadres(30, 10);

        // 3. Crear estudiantes y asignar tutores
        $secciones = Seccion::all();
        $estudiantes = $this->crearEstudiantes($secciones, $docentes, $padres);

        // 4. Crear asignaciones docente-materia
        $periodo2025 = PeriodoAcademico::first();
        $this->crearAsignaciones($secciones, $docentes, Materia::all(), $periodo2025);

        // 5. Crear horarios
        $this->crearHorarios($secciones);

        // 6. Crear asistencias y calificaciones
        $this->crearAsistenciasYCalificaciones($estudiantes, $secciones, PeriodoAcademico::all());

        // 7. Crear usuarios de prueba
        $this->crearUsuariosPrueba($periodo2025);

        // 8. Ejecutar seeders adicionales
        $this->call([
            ConfiguracionSeeder::class,
            BibliotecaSeeder::class,
            EleccionSeeder::class,
            BibliotecarioUserSeeder::class,
        ]);

        $this->mostrarEstadisticas();
    }

    /**
     * Crear grados, secciones, materias y períodos
     */
    private function crearEstructuraBase(): void
    {
        // Grados del sistema educativo peruano
        $gradosData = [
            ['nombre' => '1° Primaria', 'nivel' => 'primaria'],
            ['nombre' => '2° Primaria', 'nivel' => 'primaria'],
            ['nombre' => '3° Primaria', 'nivel' => 'primaria'],
            ['nombre' => '4° Primaria', 'nivel' => 'primaria'],
            ['nombre' => '5° Primaria', 'nivel' => 'primaria'],
            ['nombre' => '6° Primaria', 'nivel' => 'primaria'],
            ['nombre' => '1° Secundaria', 'nivel' => 'secundaria'],
            ['nombre' => '2° Secundaria', 'nivel' => 'secundaria'],
            ['nombre' => '3° Secundaria', 'nivel' => 'secundaria'],
            ['nombre' => '4° Secundaria', 'nivel' => 'secundaria'],
            ['nombre' => '5° Secundaria', 'nivel' => 'secundaria'],
        ];
        
        Grado::insert($gradosData);

        // Secciones según estructura del colegio
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

        $seccionesData = [];
        $grados = Grado::all();
        
        foreach ($grados as $grado) {
            $secciones = $estructuraSecciones[$grado->nombre] ?? ['A', 'B', 'C'];
            foreach ($secciones as $seccion) {
                $seccionesData[] = [
                    'nombre' => $seccion,
                    'grado_id' => $grado->id,
                    'capacidad_maxima' => 40,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }
        
        Seccion::insert($seccionesData);

        // Materias según Currículo Nacional Peruano
        $materiasData = [
            ['nombre' => 'Matemática', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Comunicación', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Ciencias Sociales', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Ciencia y Tecnología', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Educación Física', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Arte y Cultura', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Inglés', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Educación Religiosa', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Tutoría', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Educación para el Trabajo', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Desarrollo Personal, Ciudadanía y Cívica', 'created_at' => now(), 'updated_at' => now()],
        ];
        
        Materia::insert($materiasData);

        // Períodos Académicos 2025
        $periodosData = [
            ['nombre' => 'I Bimestre 2025', 'anio' => 2025, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'II Bimestre 2025', 'anio' => 2025, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'III Bimestre 2025', 'anio' => 2025, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'IV Bimestre 2025', 'anio' => 2025, 'created_at' => now(), 'updated_at' => now()],
        ];
        
        PeriodoAcademico::insert($periodosData);

        PeriodoAcademico::insert($periodosData);
    }

    /**
     * Crear docentes con sus usuarios
     */
    private function crearDocentes(int $cantidad): \Illuminate\Database\Eloquent\Collection
    {
        $docentes = Docente::factory($cantidad)->create();

        $usuariosData = [];
        foreach ($docentes as $index => $docente) {
            $nombreCompleto = "{$docente->nombres} {$docente->apellido_paterno} {$docente->apellido_materno}";
            $usuariosData[] = [
                'name' => $nombreCompleto,
                'email' => "docente" . ($index + 1) . "@colegio.pe",
                'password' => bcrypt('docente' . ($index + 1)),
                'role' => 'docente',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Crear usuarios en batch
        $usuarios = collect();
        foreach ($usuariosData as $userData) {
            $usuarios->push(User::create($userData));
        }

        // Vincular usuarios con docentes
        foreach ($docentes as $index => $docente) {
            $docente->update(['user_id' => $usuarios[$index]->id]);
        }

        return $docentes;
    }

    /**
     * Crear padres con algunos usuarios (30% tendrán acceso)
     */
    private function crearPadres(int $total, int $conAcceso): \Illuminate\Database\Eloquent\Collection
    {
        $padres = Padre::factory($total)->create();
        
        $padresConAcceso = $padres->random($conAcceso);
        $usuariosData = [];
        
        foreach ($padresConAcceso as $index => $padre) {
            $nombreCompleto = "{$padre->nombres} {$padre->apellido_paterno} {$padre->apellido_materno}";
            $usuariosData[] = [
                'name' => $nombreCompleto,
                'email' => "padre" . ($index + 1) . "@colegio.pe",
                'password' => bcrypt('padre' . ($index + 1)),
                'role' => 'padre',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        $usuarios = collect();
        foreach ($usuariosData as $userData) {
            $usuarios->push(User::create($userData));
        }

        foreach ($padresConAcceso as $index => $padre) {
            $padre->update(['user_id' => $usuarios[$index]->id]);
        }

        return $padres;
    }

    /**
     * Crear estudiantes distribuidos en secciones
     */
    private function crearEstudiantes($secciones, $docentes, $padres): \Illuminate\Support\Collection
    {
        $estudiantesCreados = collect();
        $estudianteCounter = 1;

        foreach ($secciones as $index => $seccion) {
            // Asignar tutor único a cada sección
            $tutorIndex = $index % $docentes->count();
            $seccion->update(['tutor_id' => $docentes[$tutorIndex]->id]);

            // Crear estudiantes por sección
            $cantidadEstudiantes = rand(...self::ESTUDIANTES_POR_SECCION);
            
            for ($i = 0; $i < $cantidadEstudiantes; $i++) {
                $estudiante = Estudiante::factory()->create([
                    'seccion_id' => $seccion->id,
                ]);
                
                // Crear usuario para el estudiante
                $nombreCompleto = "{$estudiante->nombres} {$estudiante->apellido_paterno} {$estudiante->apellido_materno}";
                $userEstudiante = User::create([
                    'name' => $nombreCompleto,
                    'email' => "estudiante{$estudianteCounter}@colegio.pe",
                    'password' => bcrypt("estudiante{$estudianteCounter}"),
                    'role' => 'estudiante',
                    'is_active' => true,
                ]);
                
                $estudiante->update(['user_id' => $userEstudiante->id]);
                $estudiantesCreados->push($estudiante);
                $estudianteCounter++;

                // Asignar 1-2 padres al estudiante
                $padresAsignados = $padres->random(rand(1, 2));
                $estudiante->padres()->attach($padresAsignados->pluck('id'));
            }
        }

        return $estudiantesCreados;
    }

    /**
     * Crear asignaciones docente-materia-sección
     */
    private function crearAsignaciones($secciones, $docentes, $materias, $periodo): void
    {
        $asignacionesData = [];

        foreach ($secciones as $seccion) {
            // 5-7 materias por sección
            $materiasAsignadas = $materias->random(rand(5, 7));

            foreach ($materiasAsignadas as $materia) {
                $docente = $docentes->random();
                
                $asignacionesData[] = [
                    'docente_id' => $docente->id,
                    'materia_id' => $materia->id,
                    'seccion_id' => $seccion->id,
                    'periodo_academico_id' => $periodo->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        // Insertar en batch para mayor eficiencia
        AsignacionDocenteMateria::insert($asignacionesData);
    }

    /**
     * Crear horarios para todas las secciones
     */
    private function crearHorarios($secciones): void
    {
        $dias = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes'];
        $horas = [
            ['08:00', '08:45'],
            ['08:45', '09:30'],
            ['09:30', '10:15'],
            ['10:30', '11:15'],
            ['11:15', '12:00'],
            ['12:00', '12:45'],
        ];

        $asignaciones = AsignacionDocenteMateria::all();
        $horariosData = [];
        
        foreach ($secciones as $seccion) {
            $asignacionesSeccion = $asignaciones->where('seccion_id', $seccion->id);
            
            if ($asignacionesSeccion->isEmpty()) continue;
            
            $horariosCreados = [];
            
            // Cada materia aparece 2-3 veces por semana
            foreach ($asignacionesSeccion as $asignacion) {
                $vecesEnSemana = rand(2, 3);
                
                for ($i = 0; $i < $vecesEnSemana; $i++) {
                    $intentos = 0;
                    do {
                        $dia = $dias[array_rand($dias)];
                        $hora = $horas[array_rand($horas)];
                        $key = "{$seccion->id}-{$dia}-{$hora[0]}";
                        $intentos++;
                    } while (isset($horariosCreados[$key]) && $intentos < 10);
                    
                    if (!isset($horariosCreados[$key])) {
                        $horariosData[] = [
                            'seccion_id' => $asignacion->seccion_id,
                            'materia_id' => $asignacion->materia_id,
                            'dia' => $dia,
                            'hora_inicio' => $hora[0],
                            'hora_fin' => $hora[1],
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                        $horariosCreados[$key] = true;
                    }
                }
            }
        }

        // Insertar en chunks para mejor rendimiento
        collect($horariosData)->chunk(500)->each(function ($chunk) {
            Horario::insert($chunk->toArray());
        });
    }

    /**
     * Crear asistencias y calificaciones para todos los estudiantes
     */
    private function crearAsistenciasYCalificaciones($estudiantes, $secciones, $periodos): void
    {
        $asignaciones = AsignacionDocenteMateria::all();
        $asistenciasData = [];
        $calificacionesData = [];

        foreach ($estudiantes as $estudiante) {
            // Obtener materias de la sección del estudiante
            $materiasSeccion = $asignaciones->where('seccion_id', $estudiante->seccion_id)
                ->pluck('materia_id')
                ->unique();

            // Asistencias (últimos N días)
            // Asistencias (últimos N días)
            for ($i = 0; $i < self::DIAS_ASISTENCIA; $i++) {
                $fecha = now()->subDays($i);

                // Solo días laborables (lunes a viernes)
                if ($fecha->dayOfWeek >= 1 && $fecha->dayOfWeek <= 5) {
                    $materiasDia = $materiasSeccion->count() > 0 
                        ? $materiasSeccion->random(min(self::MATERIAS_POR_DIA, $materiasSeccion->count())) 
                        : collect();
                        
                    foreach ($materiasDia as $materiaId) {
                        // 90% presente, 10% ausente
                        $asistenciasData[] = [
                            'estudiante_id' => $estudiante->id,
                            'materia_id' => $materiaId,
                            'fecha' => $fecha->format('Y-m-d'),
                            'presente' => fake()->boolean(90),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                }
            }

            // Calificaciones (para cada período)
            foreach ($periodos as $periodo) {
                foreach ($materiasSeccion as $materiaId) {
                    // Distribución normal de notas (11-18) con casos especiales
                    $notaBase = rand(11, 18);
                    $variacion = rand(-2, 2);
                    $nota = max(0, min(20, $notaBase + $variacion));

                    // 20% estudiantes destacados
                    if (rand(1, 10) > 8) {
                        $nota = rand(17, 20);
                    }

                    // 10% estudiantes con dificultades
                    if (rand(1, 10) > 9) {
                        $nota = rand(8, 12);
                    }

                    $calificacionesData[] = [
                        'estudiante_id' => $estudiante->id,
                        'materia_id' => $materiaId,
                        'periodo_academico_id' => $periodo->id,
                        'nota' => $nota,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }
        }

        // Insertar en chunks para mejor rendimiento
        collect($asistenciasData)->chunk(1000)->each(function ($chunk) {
            Asistencia::insert($chunk->toArray());
        });

        collect($calificacionesData)->chunk(1000)->each(function ($chunk) {
            Calificacion::insert($chunk->toArray());
        });
    }

    /**
     * Crear usuarios de prueba para testing
     */
    private function crearUsuariosPrueba($periodo): void
    {
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

        // Usuario Docente Test
        $userDocente = User::create([
            'name' => 'Roberto García López',
            'email' => 'docente@colegio.pe',
            'password' => bcrypt('docente123'),
            'role' => 'docente',
            'is_active' => true,
        ]);

        $docenteTest = Docente::create([
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

        // Asignar materias al docente test
        $materiasMath = Materia::where('nombre', 'like', '%Matemática%')->take(2)->get();
        $seccionesTest = Seccion::take(2)->get();

        foreach ($seccionesTest as $seccion) {
            foreach ($materiasMath as $materia) {
                AsignacionDocenteMateria::create([
                    'docente_id' => $docenteTest->id,
                    'materia_id' => $materia->id,
                    'seccion_id' => $seccion->id,
                    'periodo_academico_id' => $periodo->id,
                ]);
            }
        }

        // Usuario Padre Test
        $userPadre = User::create([
            'name' => 'Juan Pérez Rojas',
            'email' => 'padre@colegio.pe',
            'password' => bcrypt('padre123'),
            'role' => 'padre',
            'is_active' => true,
        ]);

        $padreTest = Padre::create([
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

        // Asociar estudiantes al padre test
        $estudiantesTest = Estudiante::take(2)->get();
        $padreTest->estudiantes()->attach($estudiantesTest->pluck('id'));

        // Usuario Estudiante Test
        $userEstudiante = User::create([
            'name' => 'Diego Martínez Silva',
            'email' => 'estudiante@colegio.pe',
            'password' => bcrypt('estudiante123'),
            'role' => 'estudiante',
            'is_active' => true,
        ]);

        $seccionTest = Seccion::first();
        $estudianteTest = Estudiante::create([
            'nombres' => 'Diego',
            'apellido_paterno' => 'Martínez',
            'apellido_materno' => 'Silva',
            'dni' => '11223344',
            'fecha_nacimiento' => '2010-01-01',
            'seccion_id' => $seccionTest->id,
            'user_id' => $userEstudiante->id,
        ]);

        $padreTest->estudiantes()->attach($estudianteTest->id);
    }

    /**
     * Mostrar estadísticas de datos creados
     */
    private function mostrarEstadisticas(): void
    {
        $this->command->info('✅ Base de datos poblada con datos del sistema educativo peruano');
        $this->command->info('📊 Grados: ' . Grado::count());
        $this->command->info('📚 Secciones: ' . Seccion::count());
        $this->command->info('👨‍🏫 Docentes: ' . Docente::count());
        $this->command->info('👨‍👩‍👧 Padres: ' . Padre::count());
        $this->command->info('👨‍🎓 Estudiantes: ' . Estudiante::count());
        $this->command->info('📖 Materias: ' . Materia::count());
        $this->command->info('📅 Periodos: ' . PeriodoAcademico::count());
        $this->command->info('📝 Asignaciones: ' . AsignacionDocenteMateria::count());
        $this->command->info('🕐 Horarios: ' . Horario::count());
        $this->command->info('✓ Asistencias: ' . Asistencia::count());
        $this->command->info('📊 Calificaciones: ' . Calificacion::count());
        $this->command->info('📚 Libros: ' . \App\Models\Libro::count());
        $this->command->info('🗳️ Elecciones: ' . \App\Models\Eleccion::count());
    }
}
