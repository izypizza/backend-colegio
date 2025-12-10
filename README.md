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

## 📡 Endpoints API

### Autenticación
- `POST /api/register` - Registro de usuario
- `POST /api/login` - Inicio de sesión
- `POST /api/logout` - Cerrar sesión
- `GET /api/user` - Usuario autenticado

### Recursos
- `/api/estudiantes` - Gestión de estudiantes
- `/api/docentes` - Gestión de docentes
- `/api/materias` - Gestión de materias
- `/api/secciones` - Gestión de secciones
- `/api/calificaciones` - Gestión de calificaciones
- `/api/asistencias` - Gestión de asistencias
- `/api/horarios` - Gestión de horarios
- `/api/periodos` - Períodos académicos

## 🔒 Seguridad

Todas las rutas API están protegidas con `auth:sanctum` middleware.

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
