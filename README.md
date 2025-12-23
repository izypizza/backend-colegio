# Sistema de Gestión Escolar - Backend API

API REST desarrollada con Laravel 12 para la gestión integral de instituciones educativas del sistema peruano.

## 🚀 Características Principales

- ✅ API REST completa con autenticación Laravel Sanctum
- ✅ Sistema de roles y permisos (5 roles: Admin, Auxiliar, Docente, Padre, Estudiante)
- ✅ Gestión completa de estudiantes, docentes y padres de familia
- ✅ Sistema de calificaciones, asistencias y horarios
- ✅ Períodos académicos configurables
- ✅ Middleware de control de acceso por roles
- ✅ Base de datos estructurada con 23 tablas relacionadas
- ✅ Comandos artisan personalizados para verificación
- ✅ Exportación de datos a Excel/PDF
- ✅ CORS configurado para desarrollo local y red

## 📋 Requisitos Técnicos

- **PHP** >= 8.2
- **Composer** 2.x
- **MySQL** 8.0+ o MariaDB 10.3+
- **XAMPP** (recomendado para desarrollo local)
- **Node.js** (opcional, para compilar assets)

## 🛠️ Instalación y Configuración

### Paso 1: Clonar e Instalar Dependencias

```bash
# Clonar repositorio
git clone https://github.com/usuario/backend-colegio.git
cd backend-colegio

# Instalar dependencias PHP
composer install
```

### Paso 2: Configuración de Entorno

```bash
# Copiar archivo de configuración
cp .env.example .env

# Generar clave de aplicación
php artisan key:generate
```

### Paso 3: Configurar Base de Datos

Editar `.env` con tus credenciales:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=tupac_amaru_db
DB_USERNAME=root
DB_PASSWORD=

# Configuración CORS y Sanctum
SANCTUM_STATEFUL_DOMAINS=localhost:3000,127.0.0.1:3000,192.168.1.5:3000
FRONTEND_URL=http://localhost:3000
```

### Paso 4: Ejecutar Migraciones y Seeders

```bash
# Crear tablas y poblar con datos de prueba
php artisan migrate --seed

# O si necesitas resetear la base de datos
php artisan migrate:fresh --seed

# Crear usuarios auxiliares adicionales
php artisan db:seed --class=AuxiliarSeeder
```

### Paso 5: Verificar Instalación

```bash
# Ver usuarios creados
php artisan users:verify

# Ver diagrama de base de datos
php artisan db:diagram

# Verificar integridad de datos
php artisan db:check
```

### Paso 6: Iniciar Servidor

```bash
# Iniciar servidor de desarrollo
php artisan serve

# El servidor estará disponible en:
# http://localhost:8000
```

## 🔐 Sistema de Autenticación y Roles

### Roles y Permisos

El sistema implementa **5 roles** con diferentes niveles de acceso:

| Rol | Email de Prueba | Permisos y Funciones |
|-----|----------------|----------------------|
| 👨‍💼 **Admin** | `admin@colegio.pe` | **Acceso total al sistema**<br>• Gestión de usuarios y roles<br>• Configuración del sistema<br>• Gestión de grados, materias y periodos<br>• Acceso a todos los reportes |
| 🧑‍💼 **Auxiliar** | `auxiliar@colegio.pe` | **Personal administrativo**<br>• Gestión de estudiantes<br>• Registro de asistencias y calificaciones<br>• Generación de reportes académicos<br>• Gestión de horarios |
| 👨‍🏫 **Docente** | `docente1@colegio.pe` | **Gestión académica**<br>• Ver cursos asignados<br>• Registrar asistencias de sus alumnos<br>• Ingresar calificaciones<br>• Consultar horarios |
| 👨‍👩‍👧 **Padre** | `padre1@colegio.pe` | **Seguimiento de hijos**<br>• Ver calificaciones de hijos<br>• Revisar asistencias<br>• Consultar horarios<br>• Descargar boletas |
| 👨‍🎓 **Estudiante** | `estudiante@colegio.pe` | **Información personal**<br>• Ver calificaciones propias<br>• Revisar asistencia<br>• Consultar horario<br>• Descargar boleta |

**Contraseña para todos**: `password`

### Implementación de Middleware

El middleware `RoleMiddleware` protege las rutas según el rol:

**Archivo**: `app/Http/Middleware/RoleMiddleware.php`

```php
// Protección por rol único
Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::apiResource('grados', GradoController::class);
    Route::apiResource('materias', MateriaController::class);
});

// Protección por múltiples roles
Route::middleware(['auth:sanctum', 'role:admin,auxiliar'])->group(function () {
    Route::apiResource('estudiantes', EstudianteController::class);
    Route::post('asistencias', [AsistenciaController::class, 'store']);
});

// Ejemplo avanzado
Route::middleware(['auth:sanctum', 'role:admin,auxiliar,docente'])->group(function () {
    Route::get('reportes/academicos', [ReporteController::class, 'index']);
});
```

### Helpers Disponibles en el Modelo User

```php
// Verificar rol específico
$user->isAdmin();       // true si es admin
$user->isAuxiliar();    // true si es auxiliar
$user->isDocente();     // true si es docente
$user->isPadre();       // true si es padre
$user->isEstudiante();  // true si es estudiante

// Verificar múltiples roles
$user->hasRole(['admin', 'auxiliar']);  // true si tiene alguno de los roles

// Verificar acceso administrativo
$user->hasAdminAccess();  // true si es admin o auxiliar
```

### Ejemplo de Uso en Controladores

```php
public function index(Request $request)
{
    $user = $request->user();

    // Admin y auxiliar ven todo
    if ($user->hasAdminAccess()) {
        return Estudiante::with('seccion')->paginate(50);
    }

    // Docente solo ve estudiantes de sus cursos
    if ($user->isDocente()) {
        return Estudiante::whereHas('seccion.asignaciones', function ($query) use ($user) {
            $query->where('docente_id', $user->docente->id);
        })->paginate(50);
    }

    // Padre solo ve a sus hijos
    if ($user->isPadre()) {
        return $user->padre->estudiantes()->with('seccion')->get();
    }

    return response()->json(['error' => 'No autorizado'], 403);
}
```

## 🗄️ Estructura de Base de Datos

### Arquitectura en 4 Capas

```
[CAPA 1: AUTENTICACIÓN]
    users → Tabla central de autenticación y roles

[CAPA 2: PERFILES]
    docentes, padres, estudiantes → Relación 1:1 con users

[CAPA 3: ESTRUCTURA ACADÉMICA]
    grados, secciones, materias, periodos_academicos

[CAPA 4: OPERACIONES]
    asignaciones, asistencias, calificaciones, horarios
```

### Comando para Ver Diagrama Completo

```bash
php artisan db:diagram
```

Muestra toda la arquitectura con:
- 📊 Diagramas visuales en ASCII
- 🔗 Relaciones entre tablas (Foreign Keys)
- 📋 Detalles de campos y tipos
- 📈 Estadísticas de registros

### Comandos Útiles de Base de Datos

```bash
# Verificar integridad de datos
php artisan db:check

# Ver todos los usuarios y sus roles
php artisan users:verify

# Sincronizar usuarios con perfiles
php artisan users:sync
```

### Tablas Principales

| Tabla | Descripción | Registros de Prueba |
|-------|-------------|---------------------|
| `users` | Usuarios y autenticación | ~187 |
| `docentes` | Profesores | 15 |
| `padres` | Padres de familia | 50 |
| `estudiantes` | Alumnos | 111 |
| `grados` | 1° Primaria a 5° Secundaria | 11 |
| `secciones` | A, B, C por grado | 33 |
| `materias` | Currículo peruano | 11 |
| `periodos_academicos` | Bimestres 2025 | 4 |
| `asignacion_docente_materia` | Asignaciones | 200 |
| `asistencias` | Registro diario | 300 |
| `calificaciones` | Notas por periodo | 190 |
| `horarios` | Programación | 50 |

## 📡 API Endpoints

### Autenticación

| Método | Endpoint | Descripción | Requiere Auth |
|--------|----------|-------------|---------------|
| POST | `/api/auth/login` | Iniciar sesión | No |
| POST | `/api/auth/register` | Registrar usuario | No |
| GET | `/api/auth/me` | Usuario autenticado | Sí |
| POST | `/api/auth/logout` | Cerrar sesión | Sí |

**Ejemplo de Login:**
```json
POST /api/auth/login
Content-Type: application/json

{
  "email": "admin@colegio.pe",
  "password": "password"
}

Response (200):
{
  "success": true,
  "data": {
    "token": "1|xxxxx...",
    "user": {
      "id": 1,
      "name": "Administrador",
      "email": "admin@colegio.pe",
      "role": "admin"
    }
  }
}
```

### Recursos CRUD (Requieren Autenticación)

Todos los endpoints siguen el patrón RESTful estándar:

| Recurso | GET (listar) | POST (crear) | GET (ver) | PUT (editar) | DELETE |
|---------|-------------|-------------|-----------|-------------|--------|
| Estudiantes | `/api/estudiantes` | `/api/estudiantes` | `/api/estudiantes/{id}` | `/api/estudiantes/{id}` | `/api/estudiantes/{id}` |
| Docentes | `/api/docentes` | `/api/docentes` | `/api/docentes/{id}` | `/api/docentes/{id}` | `/api/docentes/{id}` |
| Padres | `/api/padres` | `/api/padres` | `/api/padres/{id}` | `/api/padres/{id}` | `/api/padres/{id}` |
| Grados | `/api/grados` | `/api/grados` | `/api/grados/{id}` | `/api/grados/{id}` | `/api/grados/{id}` |
| Secciones | `/api/secciones` | `/api/secciones` | `/api/secciones/{id}` | `/api/secciones/{id}` | `/api/secciones/{id}` |
| Materias | `/api/materias` | `/api/materias` | `/api/materias/{id}` | `/api/materias/{id}` | `/api/materias/{id}` |
| Periodos | `/api/periodos` | `/api/periodos` | `/api/periodos/{id}` | `/api/periodos/{id}` | `/api/periodos/{id}` |
| Horarios | `/api/horarios` | `/api/horarios` | `/api/horarios/{id}` | `/api/horarios/{id}` | `/api/horarios/{id}` |
| Asistencias | `/api/asistencias` | `/api/asistencias` | `/api/asistencias/{id}` | `/api/asistencias/{id}` | `/api/asistencias/{id}` |
| Calificaciones | `/api/calificaciones` | `/api/calificaciones` | `/api/calificaciones/{id}` | `/api/calificaciones/{id}` | `/api/calificaciones/{id}` |
| Asignaciones | `/api/asignaciones` | `/api/asignaciones` | `/api/asignaciones/{id}` | `/api/asignaciones/{id}` | `/api/asignaciones/{id}` |

**Headers requeridos:**
```
Authorization: Bearer {token}
Content-Type: application/json
Accept: application/json
```

## 🛠️ Comandos Artisan Personalizados

### Verificar Usuarios
```bash
php artisan users:verify
```
Verifica y actualiza los usuarios de prueba con roles y contraseñas correctas.

### Ver Estado de Migraciones
```bash
php artisan migrate:status
```

### Refrescar Base de Datos
```bash
php artisan migrate:fresh --seed
```
⚠️ **Advertencia:** Esto eliminará todos los datos existentes.

## 📊 Datos de Prueba (Seeders)

Al ejecutar `php artisan db:seed`, se crean:

- ✅ **11 Grados** - Primaria y Secundaria (sistema peruano)
- ✅ **33 Secciones** - A, B, C por cada grado
- ✅ **15 Docentes** - Con usuarios y credenciales
- ✅ **50 Padres** - Algunos con acceso al sistema
- ✅ **~100 Estudiantes** - Distribuidos en secciones
- ✅ **11 Materias** - Currículo Nacional Peruano
- ✅ **4 Períodos** - Bimestres 2025
- ✅ **Asignaciones** - Docentes asignados a materias
- ✅ **Horarios** - Clases programadas
- ✅ **Asistencias** - Registros de últimos 30 días
- ✅ **Calificaciones** - Notas de estudiantes

## 🔧 Configuración CORS

El backend está configurado para aceptar peticiones desde:

```php
// config/cors.php
'allowed_origins' => [
    'http://localhost:3000',
    'http://127.0.0.1:3000',
],
```

Para agregar más orígenes, editar `config/cors.php`.

## 📦 Tecnologías y Paquetes

### Framework y Core
- **Laravel 12.x** - Framework PHP
- **PHP 8.2+** - Lenguaje de programación

### Autenticación y Seguridad
- **Laravel Sanctum 4.x** - Autenticación API con tokens
- **Spatie Laravel Permission 6.x** - Roles y permisos

### Base de Datos
- **MySQL 8.0+** - Sistema de gestión de base de datos
- **Laravel Migrations** - Control de versiones de BD

### Exportación y Reportes
- **Maatwebsite Laravel Excel 3.x** - Exportación a Excel
- **Barryvdh DomPDF 3.x** - Generación de PDFs

### Procesamiento de Datos
- **Intervention Image** - Procesamiento de imágenes
- **Carbon** - Manejo de fechas (incluido en Laravel)

### Desarrollo
- **Laravel Debugbar** (dev) - Depuración
- **Laravel IDE Helper** (dev) - Autocompletado IDE
- **Laravel Pint** (dev) - Code styling
- **PHPUnit** (dev) - Testing

## 🌐 Configuración CORS

El backend está configurado para aceptar peticiones desde:
- `http://localhost:3000` (desarrollo local)
- `http://127.0.0.1:3000` (desarrollo local)
- `http://192.168.1.5:3000` (IP de red local)
- Cualquier IP en el rango `192.168.x.x:3000`

### Agregar más IPs permitidas

Edita `config/cors.php`:
```php
'allowed_origins' => [
    'http://localhost:3000',
    'http://127.0.0.1:3000',
    'http://TU_IP:3000', // Agregar aquí
],
```

Después de editar, reinicia el servidor:
```bash
php artisan serve
```

## 📚 Comandos Útiles del Proyecto

### Verificación y Diagnóstico
```bash
# Ver diagrama visual de la base de datos
php artisan db:diagram

# Verificar estado completo de la BD
php artisan db:check

# Verificar usuarios de prueba
php artisan users:verify

# Sincronizar relaciones users
php artisan users:sync --fresh
```

## 🧪 Testing

```bash
# Ejecutar todos los tests
php artisan test

# Ejecutar tests con cobertura
php artisan test --coverage

# Ejecutar un test específico
php artisan test --filter=NombreDelTest
```

## 🚀 Despliegue a Producción

### Preparación
```bash
# 1. Optimizar autoload
composer install --optimize-autoloader --no-dev

# 2. Cachear configuración
php artisan config:cache

# 3. Cachear rutas
php artisan route:cache

# 4. Cachear vistas
php artisan view:cache

# 5. Optimizar
php artisan optimize
```

### Variables de Entorno Recomendadas
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tu-dominio.com

DB_CONNECTION=mysql
DB_HOST=tu-servidor-bd
DB_PORT=3306
DB_DATABASE=nombre_bd
DB_USERNAME=usuario
DB_PASSWORD=contraseña_segura

SANCTUM_STATEFUL_DOMAINS=tu-frontend.com
FRONTEND_URL=https://tu-frontend.com
```

## 🐛 Solución de Problemas

### Error: "Could not find driver"
```bash
# Habilitar extensión PDO MySQL en php.ini
extension=pdo_mysql
extension=mysqli
```

### Error: "Class HasApiTokens not found"
```bash
composer require laravel/sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
```

### Error: "Table 'users' doesn't exist"
```bash
php artisan migrate
```

### Limpiar caché
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

## 🔄 Comandos Útiles

### Verificación del Sistema
```bash
# Verificar estado de la base de datos
php artisan db:check

# Verificar/actualizar usuarios de prueba
php artisan users:verify

# Sincronizar relaciones users (crear usuarios para registros existentes)
php artisan users:sync --fresh

# Ver estado de migraciones
php artisan migrate:status

# Reiniciar base de datos (⚠️ elimina todos los datos)
php artisan migrate:fresh --seed
```

### Comandos de Desarrollo
```bash
# Limpiar caché
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Ver rutas disponibles
php artisan route:list

# Crear modelo con migración y factory
php artisan make:model NombreModelo -mf

# Crear controlador de recursos
php artisan make:controller NombreController --resource
```

## 🔄 Actualizaciones Recientes

### v2.0.0 (22 de diciembre de 2025)

#### ✅ Sistema de Relaciones Implementado
- ✅ 183 usuarios sincronizados con roles correctos
- ✅ 15 docentes con user_id vinculado
- ✅ 50 padres con user_id vinculado
- ✅ 118 estudiantes con user_id vinculado
- ✅ 0 registros huérfanos (100% sincronizado)

#### ✅ Migraciones Agregadas
- `add_user_id_to_docentes_table` - FK a users + email, telefono, dni, direccion
- `add_user_id_to_padres_table` - FK a users + email, dni, direccion, ocupacion
- `add_user_id_to_estudiantes_table` - FK a users + dni, telefono, direccion
- `add_role_to_users_table` - role (enum), is_active, avatar

#### ✅ Modelos Mejorados
- `User.php` - hasOne(Docente, Padre, Estudiante) + helpers (isAdmin, isDocente, etc)
- `Docente.php` - belongsTo(User) + campos adicionales
- `Padre.php` - belongsTo(User) + campos adicionales
- `Estudiante.php` - belongsTo(User) + campos adicionales

#### ✅ Comandos Artisan Personalizados
- `php artisan users:verify` - Verificar usuarios de prueba
- `php artisan users:sync --fresh` - Sincronizar relaciones
- `php artisan db:check` - Verificar estado completo de BD

#### ✅ Endpoints API
- `POST /api/auth/login` - Login con roles
- `GET /api/auth/me` - Usuario actual
- `POST /api/auth/logout` - Cerrar sesión
- CRUD completo: `/api/estudiantes`, `/api/docentes`, `/api/padres`

## 🤝 Contribución

### Flujo de Trabajo
1. Fork del repositorio
2. Crear rama para feature: `git checkout -b feature/nueva-funcionalidad`
3. Commit de cambios: `git commit -m 'Add: nueva funcionalidad'`
4. Push a la rama: `git push origin feature/nueva-funcionalidad`
5. Crear Pull Request

### Estándares de Código
- PSR-12 para PHP
- Laravel Best Practices
- Nombres descriptivos en español para el dominio
- Comentarios en español
- Tests para nuevas funcionalidades

## 📄 Licencia

Este proyecto está bajo la Licencia MIT.

## 👥 Autores

- **Equipo de Desarrollo** - Sistema Escolar Túpac Amaru

## 🙏 Agradecimientos

- Laravel Framework
- Comunidad de desarrolladores PHP
- Spatie por sus excelentes paquetes

---

**Última actualización:** 16 de diciembre de 2025  
**Versión:** 2.0.0  
**Estado:** ✅ Producción Ready
