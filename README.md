# Sistema de Gestion Escolar - Backend API

API REST desarrollada con Laravel 12 para la gestion integral de la I.E. N° 51006 "TUPAC AMARU". Sistema completo con autenticacion JWT, roles, gestion academica, biblioteca digital y sistema electoral.

## Tecnologias

- Framework: Laravel 12.0
- PHP: 8.2+
- Base de Datos: MySQL 8.0
- Autenticacion: Laravel Sanctum (JWT)
- Autorizacion: Spatie Laravel Permission
- Exportacion: Laravel Excel, DomPDF
- Imagenes: Intervention Image

## Requisitos Previos

- PHP 8.2 o superior
- Composer
- MySQL 8.0 o superior
- XAMPP/WAMP o servidor web local

## Instalacion

```bash
# Clonar repositorio
git clone https://github.com/izypizza/backend-colegio.git
cd backend

# Instalar dependencias
composer install

# Copiar configuración
cp .env.example .env

# Generar clave de aplicación
php artisan key:generate

# Configurar base de datos en .env
DB_DATABASE=tupac_amaru_db
DB_USERNAME=root
DB_PASSWORD=

# Migrar y poblar base de datos
php artisan migrate:fresh --seed

# Iniciar servidor
php artisan serve
```

La API estará disponible en `http://localhost:8000`

## Credenciales de Prueba

| Rol          | Email                    | Contrasena     |
| ------------ | ------------------------ | -------------- |
| Admin        | admin@colegio.pe         | admin123       |
| Auxiliar     | auxiliar@colegio.pe      | auxiliar123    |
| Bibliotecario| bibliotecario@colegio.pe | biblioteca2025 |
| Docente      | docente@colegio.pe       | docente123     |
| Padre        | padre@colegio.pe         | padre123       |
| Estudiante   | estudiante@colegio.pe    | estudiante123  |

### Usuarios Adicionales

- Docentes: docente{numero}@colegio.pe con contrasena docente{numero}
- Padres: padre{numero}@colegio.pe con contrasena padre{numero}
- Estudiantes: estudiante{numero}@colegio.pe con contrasena estudiante{numero}

NOTA: Cambiar todas las credenciales antes de desplegar en produccion

## Datos Generados por el Seeder

Despues de ejecutar php artisan migrate:fresh --seed:

### Estructura Academica

- Grados: Primaria (1-6) + Secundaria (1-5) = 11 grados
- Secciones: 54 secciones distribuidas con turnos (Manana/Tarde)
- Materias: Curriculo Nacional Peruano completo
- Periodos: 8 periodos academicos (bimestres)

### Personal y Usuarios

- Docentes: 16 docentes con usuario y especialidades
- Padres: 31 padres vinculados
- Estudiantes: ~450 estudiantes con usuario activo

### Datos Academicos

- Asignaciones: 327 asignaciones docente-materia-seccion
- Horarios: ~810 horarios distribuidos en la semana
- Asistencias: ~8000 registros de asistencia (presente/tarde/ausente)
- Calificaciones: ~21000 calificaciones en todos los periodos

### Biblioteca y Elecciones

- Libros: 15 libros en catalogo con ISBN, editorial y stock
- Elecciones: 2 elecciones configuradas
- Configuraciones: Sistema de mantenimiento y preferencias

NOTA: Todas las relaciones validadas sin valores NULL, integridad referencial completa

## Endpoints API

### Base URL

```
http://localhost:8000/api
```

### Autenticación

```http
POST   /auth/login         # Iniciar sesión (retorna token)
POST   /auth/logout        # Cerrar sesión (Auth)
GET    /auth/me            # Usuario autenticado (Auth)
```

### Dashboard

```http
GET    /dashboard/stats    # Estadísticas según rol (Auth)
```

### Gestión de Personal (Admin/Auxiliar)

```http
# Estudiantes
GET    /estudiantes           # Listar
GET    /estudiantes/{id}      # Ver detalle
POST   /estudiantes           # Crear
PUT    /estudiantes/{id}      # Actualizar
DELETE /estudiantes/{id}      # Eliminar

# Docentes (mismo patrón CRUD)
/docentes

# Padres (mismo patrón CRUD)
/padres

# Usuarios (Admin)
/usuarios
```

### Gestión Académica (Admin)

```http
# Grados, Secciones, Materias, Períodos
GET    /grados           # Listar (todos los roles)
POST   /grados           # Crear (Admin)
PUT    /grados/{id}      # Actualizar (Admin)
DELETE /grados/{id}      # Eliminar (Admin)

# Mismo patrón para: /secciones, /materias, /periodos
```

### Horarios (Todos)

```http
GET    /horarios                    # Listar (filtrado automático por rol)
POST   /horarios                    # Crear (Admin)
PUT    /horarios/{id}               # Actualizar (Admin)
DELETE /horarios/{id}               # Eliminar (Admin)
GET    /estudiante/mi-horario       # Horario del estudiante autenticado
```

### Calificaciones

```http
GET    /calificaciones                        # Listar (filtrado por rol)
POST   /calificaciones                        # Crear (Admin/Auxiliar/Docente)
PUT    /calificaciones/{id}                   # Actualizar
DELETE /calificaciones/{id}                   # Eliminar
GET    /calificaciones/estadisticas-avanzadas # Estadísticas (Admin/Auxiliar)

# Portales específicos
GET    /docente/mis-calificaciones     # Calificaciones de materias asignadas
GET    /estudiante/mis-calificaciones  # Calificaciones del estudiante autenticado
GET    /padre/calificaciones-hijos     # Calificaciones de todos los hijos
```

### Asistencias (Admin/Auxiliar/Docente)

```http
GET    /asistencias                            # Listar con filtros
POST   /asistencias                            # Registrar (Admin/Auxiliar/Docente)
PUT    /asistencias/{id}                       # Actualizar (Admin/Auxiliar/Docente)
DELETE /asistencias/{id}                       # Eliminar (Admin/Auxiliar)
GET    /asistencias/reporte/estudiante/{id}   # Reporte por estudiante
GET    /asistencias/reporte/seccion/{id}      # Reporte por sección

# Portales
GET    /estudiante/mis-asistencias       # Asistencias del estudiante
GET    /padre/asistencias-hijo/{hijo_id} # Asistencias de un hijo
GET    /docente/mis-asistencias          # Asistencias de materias asignadas
POST   /docente/registrar-asistencia     # Registrar (alternativa)
```

**Campos de Asistencia:**

```json
{
    "estudiante_id": 1,
    "materia_id": 2,
    "fecha": "2026-01-14",
    "estado": "presente|tarde|ausente", // ✅ 3 estados disponibles
    "observaciones": "Opcional, máx 500 caracteres"
}
```

**Estados:**

-   🟢 `presente`: Asistió puntualmente
-   🟡 `tarde`: Llegó tarde
-   🔴 `ausente`: No asistió

**Validaciones:**

-   ✅ No registrar con más de 60 días de antigüedad
-   ✅ No registrar fechas futuras
-   ✅ No duplicar (estudiante + materia + fecha)
-   ✅ Docentes: verificación automática de asignación de materia y sección

### Biblioteca

```http
# Libros (Admin/Bibliotecario)
GET    /libros               # Listar
POST   /libros               # Crear (campos: titulo, autor, isbn, editorial, anio_publicacion, cantidad_total)
PUT    /libros/{id}          # Actualizar
DELETE /libros/{id}          # Eliminar

# Préstamos
GET    /prestamos                  # Listar (Admin/Bibliotecario)
POST   /prestamos                  # Solicitar (Estudiante) - queda en estado "pendiente"
POST   /prestamos/{id}/aprobar     # Aprobar (Admin/Bibliotecario)
POST   /prestamos/{id}/rechazar    # Rechazar con motivo (Admin/Bibliotecario)
POST   /prestamos/{id}/devolver    # Marcar como devuelto (Admin/Bibliotecario)

# Portal Estudiante
GET    /estudiante/biblioteca      # Catálogo de libros disponibles
GET    /estudiante/mis-prestamos   # Préstamos del estudiante
```

**Validaciones de Préstamos:**

-   Stock disponible (cantidad_total - préstamos aprobados activos)
-   Límite 3 préstamos activos por usuario
-   No préstamos vencidos
-   No duplicados del mismo libro
-   Estados: `pendiente` → `aprobado`/`rechazado` → `devuelto`

### Elecciones

```http
# Gestión (Admin)
GET    /elecciones              # Listar
POST   /elecciones              # Crear
PUT    /elecciones/{id}         # Actualizar
DELETE /elecciones/{id}         # Eliminar

# Candidatos (Admin)
POST   /candidatos              # Crear
PUT    /candidatos/{id}         # Actualizar
DELETE /candidatos/{id}         # Eliminar

# Votación (Estudiante)
GET    /estudiante/elecciones   # Elecciones disponibles
POST   /votar                   # Votar (1 vez por elección)
GET    /elecciones/{id}/resultados  # Ver resultados (si están publicados)
```

**Estados de Elección:**

-   `pendiente`: No comenzó
-   `activa`: En curso, se puede votar
-   `cerrada`: Finalizada, resultados pueden o no estar publicados

### Configuraciones (Admin)

```http
GET    /configuraciones                     # Listar todas
PUT    /configuraciones/{clave}             # Actualizar valor
GET    /configuraciones/modo-mantenimiento  # Ver estado mantenimiento
```

**Configuraciones disponibles:**

-   `modo_mantenimiento`: `true`/`false`
-   `mensaje_mantenimiento`: Texto personalizado
-   Preferencias de accesibilidad (frontend)

### Portales Específicos

#### Portal Docente

```http
GET    /docente/mis-clases          # Asignaciones materia-sección
GET    /docente/mis-estudiantes     # Estudiantes de materias asignadas
GET    /docente/mis-asignaciones    # Asignaciones con detalles
GET    /docente/mis-calificaciones  # Calificaciones de materias asignadas
```

#### Portal Estudiante

```http
GET    /estudiante/mi-horario         # Horario semanal
GET    /estudiante/mis-calificaciones # Calificaciones por período
GET    /estudiante/mis-asistencias    # Asistencias del estudiante
GET    /estudiante/biblioteca         # Catálogo de libros
GET    /estudiante/mis-prestamos      # Préstamos activos
GET    /estudiante/elecciones         # Elecciones disponibles
```

#### Portal Padre

```http
GET    /padre/mis-hijos                 # Lista de hijos
GET    /padre/calificaciones-hijos      # Calificaciones de todos los hijos
GET    /padre/asistencias-hijo/{id}     # Asistencias de un hijo específico
GET    /padre/boletin-hijo/{hijo_id}/{periodo_id}  # Boletín de un hijo
```

## 🔒 Middleware y Autenticación

### Autenticación (Sanctum)

Todas las rutas protegidas requieren header:

```http
Authorization: Bearer {token}
```

El token se obtiene al hacer login exitoso.

### Middleware de Roles

```php
Route::middleware(['auth:sanctum', 'role:admin,auxiliar'])->group(function() {
    // Solo admin y auxiliar
});
```

**Roles disponibles:**

-   `admin`: Acceso total
-   `auxiliar`: Gestión académica
-   `bibliotecario`: Gestión de biblioteca
-   `docente`: Portal docente
-   `padre`: Portal padre
-   auxiliar: Gestion academica
-   bibliotecario: Gestion de biblioteca
-   docente: Portal docente
-   padre: Portal padre
-   estudiante: Portal estudiante

## Estructura del Proyecto

```
backend/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── AuthController.php              # Autenticación
│   │   │   ├── DashboardController.php         # Estadísticas
│   │   │   ├── EstudianteController.php        # CRUD estudiantes
│   │   │   ├── DocenteController.php           # CRUD docentes
│   │   │   ├── PadreController.php             # CRUD padres
│   │   │   ├── GradoController.php             # CRUD grados
│   │   │   ├── SeccionController.php           # CRUD secciones
│   │   │   ├── MateriaController.php           # CRUD materias
│   │   │   ├── PeriodoAcademicoController.php  # CRUD períodos
│   │   │   ├── HorarioController.php           # CRUD horarios
│   │   │   ├── CalificacionController.php      # Gestión calificaciones
│   │   │   ├── AsistenciaController.php        # Gestión asistencias
│   │   │   ├── LibroController.php             # Gestión libros
│   │   │   ├── PrestamoLibroController.php     # Gestión préstamos
│   │   │   ├── EleccionController.php          # Gestión elecciones
│   │   │   ├── VotoController.php              # Sistema de votación
│   │   │   ├── ConfiguracionController.php     # Configuraciones
│   │   │   ├── DocentePortalController.php     # Portal docente
│   │   │   ├── EstudiantePortalController.php  # Portal estudiante
│   │   │   └── PadrePortalController.php       # Portal padre
│   │   └── Middleware/
│   │       └── RoleMiddleware.php              # Middleware de roles
│   ├── Models/
│   │   ├── User.php                # Usuario (con relaciones)
│   │   ├── Estudiante.php          # Con accessors: nombre, apellido, codigo
│   │   ├── Docente.php
│   │   ├── Padre.php
│   │   ├── Grado.php
│   │   ├── Seccion.php
│   │   ├── Materia.php
│   │   ├── PeriodoAcademico.php
│   │   ├── Horario.php
│   │   ├── Calificacion.php
│   │   ├── Asistencia.php
│   │   ├── Libro.php               # Con accessor: cantidad_disponible
│   │   ├── PrestamoLibro.php       # Con estados
│   │   ├── Eleccion.php
│   │   ├── Candidato.php
│   │   └── Voto.php
│   └── Providers/
├── config/
│   └── cors.php                    # Configuración CORS
├── database/
│   ├── migrations/                 # 39 migraciones
│   │   ├── 2025_12_01_180000_create_grados_table.php
│   │   ├── 2025_12_01_180001_create_secciones_table.php
│   │   ├── ...
│   │   ├── 2026_01_07_185334_add_fields_to_libros_table.php
│   │   └── 2026_01_07_191531_add_estado_to_prestamos_libros_table.php
│   └── seeders/
│       ├── DatabaseSeeder.php         # Seeder principal
│       ├── BibliotecaSeeder.php       # 15 libros
│       ├── EleccionSeeder.php         # 2 elecciones
│       └── BibliotecarioUserSeeder.php
├── routes/
│   ├── api.php                     # Rutas API (243 líneas)
│   └── web.php
├── storage/
│   └── logs/                       # Logs de Laravel
├── .env.example
├── composer.json
├── phpunit.xml
└── README.md
```

## 🎯 Modelos Principales

### Estudiante

```php
// Campos principales
nombres, apellido_paterno, apellido_materno, dni, fecha_nacimiento, seccion_id, user_id

// Accessors
nombre         // Retorna "nombres"
apellido       // Retorna "apellido_paterno apellido_materno"
codigo         // Retorna "EST-00001"
nombre_completo // Retorna formato completo

// Relaciones
user(), seccion(), padres(), calificaciones(), asistencias()
```

### Libro

```php
// Campos
titulo, autor, isbn, editorial, anio_publicacion, cantidad_total, categoria_id

// Accessor calculado
cantidad_disponible // cantidad_total - préstamos aprobados activos

// Relaciones
prestamos(), categoria()
```

### PrestamoLibro

```php
// Campos
estudiante_id, libro_id, user_id, fecha_prestamo, fecha_devolucion_esperada,
devuelto, fecha_devolucion_real, estado, aprobado_por, fecha_respuesta, motivo_rechazo

// Estados
pendiente  // Solicitado por estudiante
aprobado   // Aprobado por bibliotecario
rechazado  // Rechazado con motivo
devuelto   // Libro devuelto (campo booleano adicional)

// Relaciones
estudiante(), libro(), usuario(), aprobador()
```

## 🔧 Configuración

### Variables de Entorno

```env
APP_NAME="Sistema Gestión Escolar"
APP_ENV=local
APP_KEY=base64:...
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=tupac_amaru_db
DB_USERNAME=root
DB_PASSWORD=

# Sanctum (Frontend URL)
SANCTUM_STATEFUL_DOMAINS=localhost:3000
SESSION_DOMAIN=localhost
```

### CORS

Configurado en `config/cors.php` para aceptar requests de `http://localhost:3000`

## 🚀 Comandos Útiles

```bash
# Desarrollo
php artisan serve                    # Servidor desarrollo (puerto 8000)
php artisan migrate:fresh --seed     # Resetear BD con datos

# Base de datos
php artisan migrate                  # Ejecutar migraciones
php artisan migrate:rollback         # Revertir última migración
php artisan db:seed                  # Ejecutar seeders

# Cache y optimización
php artisan config:clear             # Limpiar cache de configuración
php artisan cache:clear              # Limpiar cache de aplicación
php artisan route:clear              # Limpiar cache de rutas
php artisan optimize:clear           # Limpiar todos los caches

# Depuración
php artisan route:list               # Listar todas las rutas
php artisan tinker                   # REPL de Laravel

# Tests
php artisan test                     # Ejecutar tests
```

## Resolucion de Problemas

### Error 500 al hacer login

- Causa: Base de datos no migrada o credenciales incorrectas
- Solucion: php artisan migrate:fresh --seed

### CORS error en frontend

- Causa: URL frontend no en SANCTUM_STATEFUL_DOMAINS
- Solucion: Agregar en .env y reiniciar servidor

### Prestamo no se crea

- Causas:
  1. Estudiante sin user_id
  2. Stock agotado
  3. Limite de 3 alcanzado
- Solucion: Verificar seeders, validaciones en consola

### Padre no ve hijos

- Causa: Usuario padre sin relacion en tabla padres o sin hijos en estudiante_padre
- Solucion: Verificar que el padre tenga user_id y relaciones en BD

### Calificaciones vacias para docente

- Causa: No tiene asignaciones en asignacion_docente_materia
- Solucion: Crear asignaciones desde el admin

### Error "No tiene permisos para acceder a este recurso"

Roles autorizados para asistencias:
- admin: Acceso completo
- auxiliar: Acceso completo (eliminar solo admin/auxiliar)
- docente: Ver y registrar asistencias de sus estudiantes

Roles NO autorizados:
- estudiante: Solo puede ver sus propias asistencias
- padre: Solo puede ver asistencias de sus hijos
- bibliotecario: Sin acceso a asistencias

Usuario con rol incorrecto - En Laravel Tinker:
```bash
php artisan tinker
$user = User::where('email', 'usuario@example.com')->first();
$user->role = 'auxiliar';
$user->save();
```

Token corrupto - En consola del navegador:
```javascript
localStorage.removeItem("auth_token");
localStorage.removeItem("refresh_token");
localStorage.removeItem("user_data");
window.location.href = "/login";
```

## Historial de Actualizaciones

### Version 1.3.0 (19 Enero 2026) - Consolidacion y Limpieza

Cambios principales:
- Sistema de Grados y Secciones unificado en una sola vista
- Navegacion directa a Configuraciones (sin dropdown)
- Campo turno agregado a secciones (Manana/Tarde)
- Limpieza de migraciones innecesarias
- READMEs actualizados sin emojis

Archivos modificados:
- app/dashboard/grados/page.tsx: Reescrito con vista dual (grados/secciones)
- src/components/layout/Sidebar.tsx: Eliminado menu "Secciones"
- src/components/layout/Navbar.tsx: Configuraciones con link directo
- routes/api.php: Reordenadas rutas de calificaciones
- 2026_01_19_164622_add_turno_to_secciones_table.php: Nuevo campo

Base de datos actual:
- Grados: 11 | Secciones: 54 | Docentes: 16
- Padres: 31 | Estudiantes: ~450 | Materias: 11
- Calificaciones: ~21000 | Asistencias: ~8000

---

### Version 1.2.0 (15 Enero 2026) - Optimizacion y Nuevas Funcionalidades

#### Nuevas Funcionalidades

1. Sistema de Tutores para Docentes
   - Los docentes pueden ser asignados como tutores de una seccion especifica
   - Vista especial para tutores con acceso a todas las calificaciones y asistencias
   - Validacion temporal con campo tutor_hasta
   - 3 nuevos endpoints: /api/docente/es-tutor, /api/docente/tutor-calificaciones, /api/docente/tutor-asistencias

2. Limite de Modificaciones en Calificaciones
   - Los docentes pueden modificar una calificacion maximo 3 veces
   - Tracking de modificaciones con campos modificaciones_count y ultima_modificacion
   - Admin y auxiliar sin limite de modificaciones
   - Validacion automatica antes de actualizar notas

3. Optimizaciones de Performance
   - DashboardController: Raw SQL con DB::table() y selectRaw() (~80% mas rapido)
   - DocentePortalController: Limites de 500 calificaciones y 1000 asistencias con filtros por defecto
   - EstudiantePortalController: Limite de 500 registros + filtro de 90 dias en asistencias
   - PadrePortalController: Limite de 500 registros + filtro de 90 dias, carga selectiva de columnas

---

### Version 1.1.0 (14 Enero 2026)

- Sistema de Asistencias Mejorado: 3 estados (presente/tarde/ausente)
- Campo Observaciones: Agregado en asistencias (500 caracteres)
- Validaciones Mejoradas: Control de fechas antigüedad y futuras
- Logica de Roles Corregida: Todos los roles funcionan correctamente
- Rutas API Reorganizadas: Endpoints especificos antes de apiResource
- Reportes Actualizados: Estadisticas con tardanzas diferenciadas

---

Ultima actualizacion: 19 Enero 2026 | Version: 1.3.0
Proyecto para I.E. N 51006 "TUPAC AMARU" - Cusco, Peru

Laravel: 12.0 | PHP: 8.2+ | MySQL: 8.0
