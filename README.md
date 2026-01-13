# Sistema de Gestión Escolar - Backend API

API REST desarrollada con **Laravel 12** para la gestión integral de la I.E. N° 51006 "TÚPAC AMARU". Sistema completo con autenticación JWT, roles, gestión académica, biblioteca digital y sistema electoral.

## 🚀 Tecnologías

-   **Framework**: Laravel 12.0
-   **PHP**: 8.2+
-   **Base de Datos**: MySQL 8.0
-   **Autenticación**: Laravel Sanctum (JWT)
-   **Autorización**: Spatie Laravel Permission
-   **Exportación**: Laravel Excel, DomPDF
-   **Imágenes**: Intervention Image

## 📋 Requisitos Previos

-   PHP 8.2 o superior
-   Composer
-   MySQL 8.0 o superior
-   XAMPP/WAMP o servidor web local

## ⚡ Instalación

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

## 🔐 Credenciales de Prueba

| Rol               | Email                    | Contraseña     |
| ----------------- | ------------------------ | -------------- |
| **Admin**         | admin@colegio.pe         | admin123       |
| **Auxiliar**      | auxiliar@colegio.pe      | auxiliar123    |
| **Bibliotecario** | bibliotecario@colegio.pe | biblioteca2025 |
| **Docente**       | docente@colegio.pe       | docente123     |
| **Padre**         | padre@colegio.pe         | padre123       |
| **Estudiante**    | estudiante@colegio.pe    | estudiante123  |

### Usuarios Adicionales

-   **Docentes**: `docente{número}@colegio.pe` con contraseña `docente{número}`
-   **Padres**: `padre{número}@colegio.pe` con contraseña `padre{número}`
-   **Estudiantes**: `estudiante{número}@colegio.pe` con contraseña `estudiante{número}`

> ⚠️ **Producción**: Cambiar todas las credenciales antes de desplegar

## 📊 Datos Generados por el Seeder

Después de ejecutar `php artisan migrate:fresh --seed`:

### Estructura Académica

-   **Grados**: Primaria (1-6) + Secundaria (1-5)
-   **Secciones**: Distribuidas según estructura real del colegio
    -   Primaria inicial: Secciones A-E
    -   Primaria avanzada: Secciones A-F
    -   Secundaria: Secciones A-D
-   **Materias**: Currículo Nacional Peruano completo
-   **Períodos**: Bimestres del año escolar actual

### Personal y Usuarios

-   **Docentes**: Todos con usuario y perfil completo
-   **Padres**: Algunos con acceso al sistema
-   **Estudiantes**: Todos con usuario para acceso al portal

### Datos Académicos

-   **Asignaciones**: Docente-Materia por Sección
-   **Horarios**: Clases distribuidas en la semana
-   **Asistencias**: Últimos días con asistencia realista
-   **Calificaciones**: Todos los estudiantes en todos los períodos
    -   Distribución realista según rendimiento
    -   Incluye estudiantes destacados y con dificultades

### Biblioteca y Elecciones

-   **Libros**: Catálogo con ISBN, editorial, año y sistema de stock
-   **Elecciones**: Sistema electoral con candidatos

> ✅ **Calidad de Datos**: Todas las relaciones validadas sin valores NULL, integridad referencial completa

## 📡 Endpoints API

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
GET    /asistencias              # Listar con filtros
POST   /asistencias              # Registrar
PUT    /asistencias/{id}         # Actualizar
DELETE /asistencias/{id}         # Eliminar

# Portales
GET    /estudiante/mis-asistencias       # Asistencias del estudiante
GET    /padre/asistencias-hijo/{hijo_id} # Asistencias de un hijo
```

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
-   `estudiante`: Portal estudiante

## 📁 Estructura del Proyecto

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

# Depuración
php artisan route:list               # Listar todas las rutas
php artisan tinker                   # REPL de Laravel
php artisan optimize:clear           # Limpiar cache

# Tests
php artisan test                     # Ejecutar tests
```

## 🐛 Resolución de Problemas

### Error 500 al hacer login

-   **Causa**: Base de datos no migrada o credenciales incorrectas
-   **Solución**: `php artisan migrate:fresh --seed`

### CORS error en frontend

-   **Causa**: URL frontend no en SANCTUM_STATEFUL_DOMAINS
-   **Solución**: Agregar en `.env` y reiniciar servidor

### Préstamo no se crea

-   **Causas**:
    1. Estudiante sin `user_id`
    2. Stock agotado
    3. Límite de 3 alcanzado
-   **Solución**: Verificar seeders, validaciones en consola

### Padre no ve hijos

-   **Causa**: Usuario padre sin relación en tabla `padres` o sin hijos en `estudiante_padre`
-   **Solución**: Verificar que el padre tenga `user_id` y relaciones en BD

### Calificaciones vacías para docente

-   **Causa**: No tiene asignaciones en `asignacion_docente_materia`
-   **Solución**: Crear asignaciones desde el admin

## 📝 Próximas Funcionalidades

-   [ ] Notificaciones por email/SMS
-   [ ] API de reportes (PDF, Excel)
-   [ ] Sistema de mensajería
-   [ ] Calendario de eventos
-   [ ] Control de pagos/pensiones
-   [ ] Sistema de tareas y deberes
-   [ ] API para mobile app
-   [ ] WebSockets para notificaciones en tiempo real
-   [ ] Logs de auditoría

## 🧪 Testing

```bash
# Ejecutar todos los tests
php artisan test

# Test con cobertura
php artisan test --coverage

# Test específico
php artisan test --filter=AuthTest
```

## 📄 Licencia

Proyecto privado para I.E. N° 51006 "TÚPAC AMARU" - Cusco, Perú

---

**Última actualización**: Enero 2026 | **Versión**: 1.0.0
**Laravel**: 12.0 | **PHP**: 8.2+ | **MySQL**: 8.0
