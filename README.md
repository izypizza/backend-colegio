# Backend Colegio API

API REST desarrollada con Laravel para la gestión de un sistema escolar.

## 🚀 Características

- API REST pura (sin vistas públicas)
- Autenticación con Laravel Sanctum
- Gestión de estudiantes, docentes, materias y secciones
- Sistema de calificaciones y asistencias
- Exportación de datos a Excel/PDF
- Control de acceso con roles y permisos (Spatie)

## 📋 Requisitos

- PHP >= 8.2
- Composer
- MySQL/PostgreSQL
- Node.js (para compilar assets)

## 🛠️ Instalación

```bash
# Clonar repositorio
git clone https://github.com/izypizza/backend-colegio.git
cd backend-colegio

# Instalar dependencias
composer install

# Configurar entorno
cp .env.example .env
php artisan key:generate

# Configurar base de datos en .env
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=colegio
# DB_USERNAME=root
# DB_PASSWORD=

# Ejecutar migraciones
php artisan migrate --seed

# Iniciar servidor
php artisan serve
```

El servidor estará disponible en: **http://localhost:8000**

## 📡 Endpoints API

### Información del servidor
- `GET http://localhost:8000/` - Información del backend

### Autenticación
- `POST http://localhost:8000/api/register` - Registro de usuario
- `POST http://localhost:8000/api/login` - Inicio de sesión
- `POST http://localhost:8000/api/logout` - Cerrar sesión
- `GET http://localhost:8000/api/user` - Usuario autenticado

### Recursos (CRUD Completo)

#### Secciones
- `GET http://localhost:8000/api/secciones` - Listar secciones
- `POST http://localhost:8000/api/secciones` - Crear sección
- `GET http://localhost:8000/api/secciones/{id}` - Ver sección
- `PUT http://localhost:8000/api/secciones/{id}` - Actualizar sección
- `DELETE http://localhost:8000/api/secciones/{id}` - Eliminar sección

#### Docentes
- `GET http://localhost:8000/api/docentes` - Listar docentes
- `POST http://localhost:8000/api/docentes` - Crear docente
- `GET http://localhost:8000/api/docentes/{id}` - Ver docente
- `PUT http://localhost:8000/api/docentes/{id}` - Actualizar docente
- `DELETE http://localhost:8000/api/docentes/{id}` - Eliminar docente

#### Estudiantes
- `GET http://localhost:8000/api/estudiantes` - Listar estudiantes
- `POST http://localhost:8000/api/estudiantes` - Crear estudiante
- `GET http://localhost:8000/api/estudiantes/{id}` - Ver estudiante
- `PUT http://localhost:8000/api/estudiantes/{id}` - Actualizar estudiante
- `DELETE http://localhost:8000/api/estudiantes/{id}` - Eliminar estudiante

#### Materias
- `GET http://localhost:8000/api/materias` - Listar materias
- `POST http://localhost:8000/api/materias` - Crear materia
- `GET http://localhost:8000/api/materias/{id}` - Ver materia
- `PUT http://localhost:8000/api/materias/{id}` - Actualizar materia
- `DELETE http://localhost:8000/api/materias/{id}` - Eliminar materia

#### Calificaciones
- `GET http://localhost:8000/api/calificaciones` - Listar calificaciones
- `POST http://localhost:8000/api/calificaciones` - Registrar calificación
- `GET http://localhost:8000/api/calificaciones/{id}` - Ver calificación
- `PUT http://localhost:8000/api/calificaciones/{id}` - Actualizar calificación
- `DELETE http://localhost:8000/api/calificaciones/{id}` - Eliminar calificación

#### Asistencias
- `GET http://localhost:8000/api/asistencias` - Listar asistencias
- `POST http://localhost:8000/api/asistencias` - Registrar asistencia
- `GET http://localhost:8000/api/asistencias/{id}` - Ver asistencia
- `PUT http://localhost:8000/api/asistencias/{id}` - Actualizar asistencia
- `DELETE http://localhost:8000/api/asistencias/{id}` - Eliminar asistencia

#### Horarios
- `GET http://localhost:8000/api/horarios` - Listar horarios
- `POST http://localhost:8000/api/horarios` - Crear horario
- `GET http://localhost:8000/api/horarios/{id}` - Ver horario
- `PUT http://localhost:8000/api/horarios/{id}` - Actualizar horario
- `DELETE http://localhost:8000/api/horarios/{id}` - Eliminar horario

#### Períodos Académicos
- `GET http://localhost:8000/api/periodos` - Listar períodos
- `POST http://localhost:8000/api/periodos` - Crear período
- `GET http://localhost:8000/api/periodos/{id}` - Ver período
- `PUT http://localhost:8000/api/periodos/{id}` - Actualizar período
- `DELETE http://localhost:8000/api/periodos/{id}` - Eliminar período

#### Asignaciones Docente-Materia
- `GET http://localhost:8000/api/asignaciones` - Listar asignaciones
- `POST http://localhost:8000/api/asignaciones` - Crear asignación
- `GET http://localhost:8000/api/asignaciones/{id}` - Ver asignación
- `PUT http://localhost:8000/api/asignaciones/{id}` - Actualizar asignación
- `DELETE http://localhost:8000/api/asignaciones/{id}` - Eliminar asignación

## 🔒 Seguridad

Todas las rutas API requieren autenticación con `auth:sanctum` middleware.

## 📦 Tecnologías

- Laravel 12
- Laravel Sanctum (autenticación API)
- Spatie Laravel Permission (roles y permisos)
- Laravel Excel (exportaciones)
- DomPDF (generación de PDFs)
- Intervention Image (procesamiento de imágenes)

## 🤝 Contribución

Este es un proyecto backend API. Para el frontend, consultar el repositorio correspondiente.

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
