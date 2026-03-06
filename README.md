# Sistema de Gestion Escolar - Backend

API REST en Laravel 12 para la gestion academica completa, biblioteca, elecciones, configuraciones y portales por rol (admin, auxiliar, bibliotecario, docente, padre, estudiante). Incluye seeders con datos de ejemplo y autenticacion con Laravel Sanctum.

## Stack

- PHP 8.2+, Laravel 12, MySQL 8
- Sanctum para tokens; roles/permisos con Spatie Laravel Permission
- Exportacion: Laravel Excel y DomPDF; imagenes con Intervention Image
- Vite + Tailwind para assets (opcional si recompilas vistas Blade)

## Requisitos

- PHP 8.2+ con extensiones tipicas de Laravel
- Composer
- MySQL 8.x
- Node 20+ y npm (solo si recompilas assets con Vite)

## Puesta en marcha (local)

1. Instalar dependencias:

```
composer install
npm install   # solo si necesitas recompilar assets
```

2. Copiar entorno y ajustar variables:

```
cp .env.example .env
```

- APP_URL=http://localhost:8000
- DB_DATABASE/DB_USERNAME/DB_PASSWORD
- SANCTUM_STATEFUL_DOMAINS=localhost:3000
- SESSION_DOMAIN=localhost

3. Generar clave y preparar base de datos:

```
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
```

4. Levantar servicios:

```
php artisan serve
```

- Para colas en segundo plano: `php artisan queue:work`
- Script integrado: `composer dev` (serve + queue + logs + vite)

## Scripts utiles

- `composer dev`: servidor, queue listener, pail y Vite en paralelo
- `composer test` o `php artisan test`: pruebas
- `npm run dev | build`: ciclo de assets con Vite (solo si usas vistas Blade)

## Variables .env clave

```
APP_NAME=Sistema Gestion Escolar
APP_URL=http://localhost:8000
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=tupac_amaru_db
DB_USERNAME=root
DB_PASSWORD=
SANCTUM_STATEFUL_DOMAINS=localhost:3000
SESSION_DOMAIN=localhost
```

## Credenciales de prueba (seed)

| Rol           | Email                    | Contrasena     |
| ------------- | ------------------------ | -------------- |
| Admin         | admin@colegio.pe         | admin123       |
| Auxiliar      | auxiliar@colegio.pe      | auxiliar123    |
| Bibliotecario | bibliotecario@colegio.pe | biblioteca2025 |
| Docente       | docente@colegio.pe       | docente123     |
| Padre         | padre@colegio.pe         | padre123       |
| Estudiante    | estudiante@colegio.pe    | estudiante123  |

Usuarios adicionales generados:

- docente{n}@colegio.pe / docente{n}
- padre{n}@colegio.pe / padre{n}
- estudiante{n}@colegio.pe / estudiante{n}

## Datos generados por seeders

- Estructura academica completa (grados, secciones con turno, materias, periodos activos)
- Usuarios por cada rol con relaciones establecidas (padres vinculados a hijos, docentes asignados)
- Calificaciones, asistencias y horarios de muestra
- Biblioteca con catalogo inicial y prestamos en distintos estados
- Elecciones configuradas con candidatos y votos de prueba
- Configuraciones base (modo mantenimiento, preferencias de tema)

## Endpoints base

- Base URL: http://localhost:8000/api
- Auth: POST /auth/login, POST /auth/logout, GET /auth/me (Bearer token)
- Listados usan paginacion obligatoria y filtros en recursos principales (calificaciones, asistencias, usuarios, biblioteca)
- Validaciones clave: asistencias sin fechas futuras ni duplicados; prestamos con control de stock y limite por usuario; calificaciones filtradas por periodo activo por defecto.

## Estructura relevante

```
app/
  Http/Controllers/ (Auth, Dashboard, Grado, Seccion, Materia, Periodo, Horario,
    Calificacion, Asistencia, Libro, PrestamoLibro, Eleccion, Voto, Configuracion,
    Portales: DocentePortal, EstudiantePortal, PadrePortal)
  Models/ (User, Estudiante, Docente, Padre, Grado, Seccion, Materia,
    PeriodoAcademico, Horario, Calificacion, Asistencia, Libro, PrestamoLibro,
    Eleccion, Candidato, Voto)
routes/api.php (rutas API protegidas con auth:sanctum y middleware de rol)
database/migrations (esquema completo)
database/seeders (datos base y configuraciones)
```

## Mantenimiento y depuracion

- Limpiar caches: `php artisan optimize:clear`
- Listar rutas: `php artisan route:list`
- Logs de aplicacion: `storage/logs/laravel.log`
- Para errores CORS, verifica SANCTUM_STATEFUL_DOMAINS y APP_URL
