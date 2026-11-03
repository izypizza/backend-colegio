# Sistema de Gestión Escolar — Backend (API)

API REST en **Laravel 12** para la gestión académica completa de la I.E. N° 51006 "Túpac Amaru": gestión académica, biblioteca, elecciones escolares, chat docente–padre, notificaciones, auditoría, permisos de auxiliares, configuraciones y portales por rol (admin, auxiliar, bibliotecario, docente, padre, estudiante). Incluye seeders con datos de ejemplo y autenticación con **Laravel Sanctum**.

> 🔎 El frontend oficial es una SPA en **Next.js** (repositorio `frontend-colegio`). Este backend es **headless**: responde JSON, sin vistas Blade (solo plantillas para reportes PDF).

## Stack

- PHP 8.2+, **Laravel 12**, MySQL 8
- **Laravel Sanctum** 4 — autenticación por tokens (Bearer). **Rate limiting** en login (`throttle:login`, 5 intentos/min).
- **Roles y permisos propios** (campo `role` en `users` + middlewares `RoleMiddleware` y `PermissionMiddleware`). _No usa Spatie._
- Reportes: **Laravel Excel** (maatwebsite) y **DomPDF**; imágenes con **Intervention Image**
- Vite + Tailwind para assets del backend (opcional)

## Requisitos

- PHP 8.2+ con extensiones típicas de Laravel
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
- FRONTEND_URL=http://localhost:3000

3. Generar clave y preparar base de datos:

```
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
```

4. Levantar servicios:

```
php artisan serve          # http://localhost:8000
```

- Para colas en segundo plano: `php artisan queue:work`
- Script integrado: `composer dev` (serve + queue + logs + vite)

## Credenciales de prueba (seed)

| Rol           | Email                    | Contraseña     |
| ------------- | ------------------------ | -------------- |
| Admin         | admin@colegio.pe         | admin123       |
| Auxiliar      | auxiliar@colegio.pe      | auxiliar123    |
| Bibliotecario | bibliotecario@colegio.pe | biblioteca2025 |
| Docente       | docente@colegio.pe       | docente123     |
| Padre         | padre@colegio.pe         | padre123       |
| Estudiante    | estudiante@colegio.pe    | estudiante123  |

Usuarios adicionales generados: `docente{n}`, `padre{n}`, `estudiante{n}` @colegio.pe (contraseña igual al sufijo).

## Datos generados por seeders

Reconstrucción completa desde cero:

```bash
php artisan migrate:fresh --seed
```

Los seeders usan la fecha actual, así que el período vigente y los datos se ajustan solos:

- **Estructura académica:** 11 grados, 54 secciones con turno, 11 materias y 8 bimestres con `fecha_inicio`/`fecha_fin`. El bimestre vigente se calcula según la fecha del servidor.
- **Personas:** ~424 estudiantes y ~31 padres, todos con usuario vinculado; 16 docentes.
- **Asignaciones:** ~1.300 registros para los 4 bimestres del año en curso (sin períodos futuros), con continuidad de docente por materia.
- **Asistencias y notas:** ~8.900 asistencias de los últimos días hábiles y ~10.500 calificaciones numéricas del año en curso.
- **Biblioteca:** 15 libros con ISBN, editorial, año y copias; préstamos activos y devueltos.
- **Elecciones:** una cerrada con resultados publicados y votos, y otra activa sin votos para demostrar la votación en vivo.
- **Usuarios de prueba:** el docente y el estudiante de prueba reciben clases, notas y asistencias reales.

No se generan valores nulos en períodos, estudiantes, libros, calificaciones ni préstamos.

## Módulos del API

- **Autenticación y usuarios** (login/logout/me, gestión de usuarios, estados de estudiante)
- **Académico:** grados, secciones, materias, períodos, horarios, asignaciones, docentes, estudiantes, padres
- **Asistencias y calificaciones** (con boletines, reportes y estadísticas; exportación Excel/PDF)
- **Biblioteca y préstamos** (flujo pendiente → aprobado/rechazado → devuelto)
- **Elecciones escolares** (elecciones, partidos, candidatos, votos únicos, resultados)
- **Comunicación:** chat docente–padre, notificaciones
- **Administración:** auditoría, configuración (módulos activos, mantenimiento), permisos temporales de auxiliares
- **Portales por rol:** `/mobile/docente`, `/mobile/estudiante`, `/mobile/padre` y endpoints `/docente/*`, `/estudiante/*`, `/padre/*`

## Roles y permisos

Los permisos por módulo/acción se definen en `app/Http/Middleware/PermissionMiddleware.php` (constante `PERMISSIONS`) y **deben coincidir** con el frontend (`frontend-colegio/src/lib/permissions.ts`). Reglas clave:

- Auxiliar: sin acceso a biblioteca/préstamos.
- Bibliotecario: gestión de biblioteca/préstamos.
- Gestión de usuarios/auxiliares/bibliotecarios, auditoría, configuraciones: solo admin.
- Votación en elecciones: solo estudiantes.

## Autorización por registro

Ocultar botones en la interfaz no basta: el alcance se valida en el backend.

- `Calificacion::visiblePara($usuario)` y `Asistencia::visiblePara($usuario)` aplican el alcance por rol en listados, detalle, reportes y estadísticas.
- Estudiante: solo sus registros. Padre: solo los de sus hijos. Docente: únicamente las combinaciones materia–sección–período que tenga asignadas; la lectura de tutoría requiere vigencia.
- La escritura docente valida materia, sección y período contra la asignación y respeta el límite de 3 modificaciones.
- `GET /api/estudiantes` devuelve solo los estudiantes de las secciones del docente.
- Las respuestas de autorización usan 403 o 404, nunca datos parciales.

## Rendimiento

- Índices simples y compuestos para las consultas del docente y del dashboard (migraciones `2026_01_08_*` y `2026_11_03_*`).
- Agregaciones en SQL (`AVG`, `COUNT`, `SUM` con `CASE`) en lugar de cargar filas en memoria.
- Subconsultas `EXISTS` en lugar de listas `IN` con miles de identificadores.
- Exportaciones Excel con `FromQuery` + `WithChunkReading` en lotes de 1000 filas.
- Caché de configuraciones con invalidación al actualizar.
- Comparaciones de fecha con `whereDate` y `whereBetween` para no invalidar los índices.

## Pruebas

```bash
php artisan test
```

32 pruebas y 203 aserciones: autorización por rol, portales, reglas de negocio, préstamos, votación y períodos. Las pruebas usan SQLite en memoria (`phpunit.xml`), por lo que no modifican la base local.

## Endpoints base

- Base URL: `http://localhost:8000/api`
- Auth: `POST /auth/login`, `POST /auth/logout`, `GET /auth/me` (Bearer token). Login con **throttle**.
- Listados con paginación y filtros en recursos principales.
- Validaciones: asistencias sin fechas futuras, con estados `presente|tarde|ausente` y sin duplicados; calificaciones con nota de 0 a 20 y sin duplicados por estudiante/materia/período; préstamos con control de stock y límite de préstamos activos.

## Estructura relevante

```
app/
  Exports/               # Exportaciones Excel
  Helpers/               # Helpers (edad académica, etc.)
  Http/
    Controllers/         # Auth, Dashboard, Grado, Seccion, Materia, Periodo, Horario,
                         # Calificacion, Asistencia, Libro, PrestamoLibro, Eleccion,
                         # Partido, Voto, Configuracion, Notificacion, AuditLog, Chat,
                         # Portales (Docente/Estudiante/Padre), Reportes, etc.
    Middleware/          # Role, Permission, CheckModuloActivo, CheckMaintenanceMode, AuditLog
    Requests/            # Validaciones de formulario (asistencias, calificaciones)
  Models/                # 25 modelos (incluye Partido, Candidato, Voto, Configuracion,
                         # Notificacion, AuditLog, ChatConversacion, ChatMensaje, ...)
  Services/  Traits/
database/
  factories/             # User, Docente, Estudiante, Grado, Materia, Padre, Periodo,
                         # Seccion, Eleccion, Partido, Candidato, Voto
  migrations/            # 56 migraciones
  seeders/               # 9 seeders (estructura, biblioteca, elecciones, préstamos, votos, configuración)
routes/api.php           # rutas API protegidas (auth:sanctum + role/permission/modulo)
```

## Notas

- Base de datos: `tupac_amaru_db`. Esquema detallado en `../documentacion/ESQUEMA_BASE_DE_DATOS.md`.
- El correo está en `MAIL_MAILER=log` (no envía correos reales; el email es solo identificador de login).
- Documentación académica y auditoría en `../documentacion/` (`TESIS_TAP_SCRUM.md`, `INFORME_PRUEBAS_Y_PLAN_CORRECCION.md`, `DIAGRAMA_BD_MERMAID_PLANTUML.md`).
- Repositorio: https://github.com/izypizza/backend-colegio
