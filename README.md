# 🎓 Sistema de Gestión Escolar - Backend API

API REST desarrollada con **Laravel 12** para la gestión integral de instituciones educativas. Sistema completo con autenticación, roles, calificaciones, asistencias, biblioteca digital y más.

## ✨ Características Principales

### 🔐 Autenticación y Seguridad
- Autenticación con **Laravel Sanctum**
- Sistema de roles multinivel (5 roles)
- Middleware de autorización por endpoints
- Protección CORS configurada
- Tokens de sesión seguros

### 👥 Gestión de Usuarios
- **5 Roles**: Admin, Auxiliar, Docente, Padre, Estudiante
- Perfiles completos por rol
- Relaciones padres-hijos
- Asignación docente-materia-sección

### 📚 Gestión Académica
- Grados y secciones con capacidad y turno
- Materias y asignaciones
- Períodos académicos configurables
- Horarios por sección y día
- Calificaciones con períodos
- Registro de asistencias

### 🏫 Portales Personalizados
- **Portal Docente**: Mis clases, estudiantes, calificaciones, asistencias
- **Portal Estudiante**: Mis calificaciones, asistencias, perfil, boletín
- **Portal Padre**: Información de hijos, calificaciones, asistencias

### 📖 Sistema de Biblioteca
- Catálogo de libros con categorías
- Préstamos y devoluciones
- Historial de préstamos por usuario
- Control de disponibilidad

### 🗳️ Sistema de Elecciones Escolares
- Creación de elecciones
- Votación estudiantil
- Resultados en tiempo real
- Prevención de voto duplicado

## 🗄️ Base de Datos

### Estructura (27 Tablas)

#### Usuarios y Roles
- `users` - Usuarios del sistema
- `roles` - Roles disponibles
- `role_user` - Asignación de roles

#### Académico
- `grados` - Niveles educativos (1° Primaria, 5° Secundaria, etc.)
- `secciones` - Secciones por grado (A, B, C) con capacidad y turno
- `materias` - Asignaturas del currículo
- `periodos_academicos` - Bimestres, trimestres, etc.

#### Personas
- `estudiantes` - Estudiantes con sección asignada
- `docentes` - Docentes con especialidad
- `padres` - Padres de familia
- `padre_estudiante` - Relación padres-hijos

#### Gestión Educativa
- `asignacion_docente_materia` - Docente + Materia + Sección + Período
- `horarios` - Horario por sección, materia, día y hora
- `calificaciones` - Notas por estudiante, materia y período
- `asistencias` - Registro diario de asistencia

#### Biblioteca
- `categorias_libros` - Categorías del catálogo
- `libros` - Libros con ISBN, autor, stock
- `prestamos_libros` - Préstamos con fechas

#### Elecciones
- `elecciones` - Elecciones escolares
- `candidatos` - Candidatos por elección
- `votos` - Votos emitidos

## 🚀 Instalación

### Requisitos
- PHP >= 8.2
- Composer >= 2.0
- MySQL >= 8.0 o MariaDB >= 10.3
- XAMPP (recomendado) o servidor local

### Paso 1: Clonar e Instalar
```bash
# Clonar repositorio
git clone https://github.com/tu-usuario/backend-colegio.git
cd backend-colegio

# Instalar dependencias
composer install
```

### Paso 2: Configurar Entorno
```bash
# Copiar configuración
cp .env.example .env

# Generar clave de aplicación
php artisan key:generate
```

### Paso 3: Configurar Base de Datos
Editar `.env`:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=colegio_db
DB_USERNAME=root
DB_PASSWORD=
```

### Paso 4: Migrar y Poblar
```bash
# Crear tablas
php artisan migrate

# Poblar datos de prueba (IMPORTANTE para testing)
php artisan db:seed
```

### Paso 5: Iniciar Servidor
```bash
# Servidor de desarrollo
php artisan serve

# O con host personalizado
php artisan serve --host=0.0.0.0 --port=8000
```

## 📡 API Endpoints

### Autenticación
```http
POST   /api/auth/login       # Iniciar sesión
POST   /api/auth/register    # Registrar usuario
POST   /api/auth/logout      # Cerrar sesión
GET    /api/auth/me          # Usuario actual
```

### Dashboard
```http
GET    /api/dashboard/stats  # Estadísticas por rol
```

### Grados, Materias, Períodos (Admin)
```http
GET    /api/grados           # Listar grados (Todos)
POST   /api/grados           # Crear grado (Admin)
PUT    /api/grados/{id}      # Actualizar grado (Admin)
DELETE /api/grados/{id}      # Eliminar grado (Admin)

GET    /api/materias         # Listar materias (Todos)
POST   /api/materias         # Crear materia (Admin)

GET    /api/periodos         # Listar períodos (Todos)
POST   /api/periodos         # Crear período (Admin)
```

### Secciones (Admin/Auxiliar crear, Todos leer)
```http
GET    /api/secciones        # Listar secciones (Todos)
POST   /api/secciones        # Crear sección (Admin/Auxiliar)
PUT    /api/secciones/{id}   # Actualizar (Admin/Auxiliar)
DELETE /api/secciones/{id}   # Eliminar (Admin/Auxiliar)
```

### Estudiantes (Admin/Auxiliar)
```http
GET    /api/estudiantes      # Listar estudiantes
POST   /api/estudiantes      # Crear estudiante
PUT    /api/estudiantes/{id} # Actualizar
DELETE /api/estudiantes/{id} # Eliminar
```

### Docentes (Admin/Auxiliar/Docente)
```http
GET    /api/docentes         # Listar docentes
POST   /api/docentes         # Crear docente
PUT    /api/docentes/{id}    # Actualizar
DELETE /api/docentes/{id}    # Eliminar
```

### Asignaciones (Admin/Auxiliar/Docente)
```http
GET    /api/asignaciones     # Listar asignaciones docente-materia
POST   /api/asignaciones     # Crear asignación
PUT    /api/asignaciones/{id}# Actualizar
DELETE /api/asignaciones/{id}# Eliminar
```

### Horarios (Admin/Auxiliar crear, Todos leer)
```http
GET    /api/horarios         # Listar horarios (Todos)
POST   /api/horarios         # Crear horario (Admin/Auxiliar)
PUT    /api/horarios/{id}    # Actualizar (Admin/Auxiliar)
DELETE /api/horarios/{id}    # Eliminar (Admin/Auxiliar)
```

### Calificaciones (Admin/Auxiliar)
```http
GET    /api/calificaciones   # Listar todas
POST   /api/calificaciones   # Crear
PUT    /api/calificaciones/{id}
DELETE /api/calificaciones/{id}
GET    /api/calificaciones/boletin/{estudiante_id}/{periodo_id}
```

### Asistencias (Admin/Auxiliar)
```http
GET    /api/asistencias      # Listar todas
POST   /api/asistencias      # Registrar
PUT    /api/asistencias/{id}
DELETE /api/asistencias/{id}
GET    /api/asistencias/reporte/estudiante/{id}
GET    /api/asistencias/reporte/seccion/{id}
```

### Portal Docente (Rol: Docente)
```http
GET    /api/docente/mis-asignaciones      # Mis materias asignadas
GET    /api/docente/mis-estudiantes       # Estudiantes de mis secciones
GET    /api/docente/mis-calificaciones    # Calificaciones que he registrado
GET    /api/docente/mis-asistencias       # Asistencias de mis estudiantes
POST   /api/docente/registrar-calificacion
POST   /api/docente/registrar-asistencia
```

### Portal Estudiante (Rol: Estudiante)
```http
GET    /api/estudiante/mi-perfil          # Mi información
GET    /api/estudiante/mis-calificaciones # Mis notas
GET    /api/estudiante/mis-asistencias    # Mi asistencia
GET    /api/estudiante/mi-boletin/{periodo_id}
```

### Portal Padre (Rol: Padre)
```http
GET    /api/padre/mis-hijos               # Lista de hijos
GET    /api/padre/calificaciones-hijos    # Notas de todos mis hijos
GET    /api/padre/asistencias-hijo/{hijo_id}
GET    /api/padre/boletin-hijo/{hijo_id}/{periodo_id}
```

### Biblioteca (Admin/Auxiliar)
```http
GET    /api/categorias-libros
POST   /api/categorias-libros
GET    /api/libros
POST   /api/libros
GET    /api/prestamos
POST   /api/prestamos
POST   /api/prestamos/{id}/devolver
GET    /api/mis-prestamos                 # Mis préstamos (Todos)
```

### Elecciones (Admin crear, Estudiante votar)
```http
GET    /api/elecciones                    # Listar elecciones
POST   /api/elecciones                    # Crear (Admin)
POST   /api/votos                         # Votar (Estudiante)
GET    /api/mis-votos                     # Mis votos (Estudiante)
GET    /api/elecciones/{id}/resultados    # Ver resultados
GET    /api/elecciones/{id}/ya-vote       # Verificar si voté
```

## 👥 Usuarios de Prueba

Después de ejecutar `php artisan db:seed`:

| Email | Password | Rol | Descripción |
|-------|----------|-----|-------------|
| admin@colegio.pe | password | Admin | Control total del sistema |
| auxiliar@colegio.pe | password | Auxiliar | Personal administrativo |
| docente@colegio.pe | password | Docente | Profesor con 4 asignaciones |
| padre@colegio.pe | password | Padre | Padre con 2 hijos |
| estudiante@colegio.pe | password | Estudiante | Estudiante matriculado |

## 🔧 Comandos Útiles

```bash
# Limpiar caché
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# Ver rutas
php artisan route:list

# Rehacer base de datos
php artisan migrate:fresh --seed

# Ejecutar pruebas
php artisan test
```

## 📁 Estructura del Proyecto

```
backend/
├── app/
│   ├── Http/
│   │   ├── Controllers/          # Controladores API
│   │   │   ├── AuthController.php
│   │   │   ├── DocentePortalController.php
│   │   │   ├── EstudiantePortalController.php
│   │   │   ├── PadrePortalController.php
│   │   │   └── ... (21 controladores más)
│   │   ├── Middleware/           # Middleware personalizado
│   │   │   └── RoleMiddleware.php
│   │   └── Requests/
│   ├── Models/                   # Modelos Eloquent (27 modelos)
│   ├── Services/                 # Lógica de negocio
│   └── Repositories/             # Capa de datos
├── database/
│   ├── migrations/               # Migraciones de BD
│   └── seeders/                  # Datos de prueba
├── routes/
│   └── api.php                   # Rutas API (171 líneas)
├── config/
│   ├── cors.php                  # Configuración CORS
│   └── sanctum.php               # Configuración autenticación
└── .env                          # Variables de entorno
```

## 🛡️ Seguridad

### Middleware de Roles
Protección de endpoints por rol:
```php
Route::middleware(['role:admin'])->group(function () {
    // Solo admins
});

Route::middleware(['role:admin,auxiliar,docente'])->group(function () {
    // Múltiples roles
});
```

### Tokens Sanctum
- Tokens de sesión con expiración
- Logout revoca tokens
- Middleware `auth:sanctum` en todas las rutas protegidas

### CORS
Configurado para desarrollo local y red:
```php
'allowed_origins' => ['http://localhost:3000', 'http://192.168.*.*:3000']
```

## 📦 Dependencias Principales

```json
{
  "php": "^8.2",
  "laravel/framework": "^12.0",
  "laravel/sanctum": "^4.0",
  "spatie/laravel-permission": "^6.0",
  "maatwebsite/excel": "^3.1",
  "barryvdh/laravel-dompdf": "^3.0"
}
```

## 🐛 Troubleshooting

### Error: "Access denied for user"
```bash
# Verificar credenciales en .env
DB_USERNAME=root
DB_PASSWORD=
```

### Error: "Class not found"
```bash
composer dump-autoload
php artisan optimize:clear
```

### Error: CORS
```bash
# Verificar config/cors.php
php artisan config:clear
```

## 📄 Licencia

Este proyecto es de código abierto bajo licencia MIT.

## 👨‍💻 Contribuir

1. Fork el proyecto
2. Crea una rama (`git checkout -b feature/nueva-funcionalidad`)
3. Commit cambios (`git commit -m 'Agregar funcionalidad'`)
4. Push a la rama (`git push origin feature/nueva-funcionalidad`)
5. Abre un Pull Request

## 📞 Contacto

Para soporte o consultas, contactar al equipo de desarrollo.

---

**Desarrollado con ❤️ para instituciones educativas**
