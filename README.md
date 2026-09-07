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

- Estructura académica completa (grados, secciones con turno, materias, períodos activos)
- Usuarios por rol con relaciones establecidas (padres vinculados a hijos, docentes asignados)
- Calificaciones, asistencias y horarios de muestra
- Biblioteca con catálogo inicial y préstamos en distintos estados
- **Elecciones con partidos y candidatos** (`EleccionSeeder`) y **votos** (`VotosSeeder`)
- Configuraciones base (modo mantenimiento, preferencias de tema)

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

## Endpoints base

- Base URL: `http://localhost:8000/api`
- Auth: `POST /auth/login`, `POST /auth/logout`, `GET /auth/me` (Bearer token). Login con **throttle**.
- Listados con paginación y filtros en recursos principales.
- Validaciones: asistencias sin fechas futuras ni duplicados; préstamos con control de stock; calificaciones filtradas por período activo.

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
  migrations/            # 53 migraciones
  seeders/
routes/api.php           # rutas API protegidas (auth:sanctum + role/permission/modulo)
```

## Notas

- Base de datos: `tupac_amaru_db`. Esquema detallado en `../documentacion/ESQUEMA_BASE_DE_DATOS.md`.
- El correo está en `MAIL_MAILER=log` (no envía correos reales; el email es solo identificador de login).
- Repositorio: https://github.com/izypizza/backend-colegio
