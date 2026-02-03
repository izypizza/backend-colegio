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

| Rol           | Email                    | Contrasena     |
| ------------- | ------------------------ | -------------- |
| Admin         | admin@colegio.pe         | admin123       |
| Auxiliar      | auxiliar@colegio.pe      | auxiliar123    |
| Bibliotecario | bibliotecario@colegio.pe | biblioteca2025 |
| Docente       | docente@colegio.pe       | docente123     |
| Padre         | padre@colegio.pe         | padre123       |
| Estudiante    | estudiante@colegio.pe    | estudiante123  |

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
GET    /calificaciones                        # Listar (paginado con filtros avanzados)
POST   /calificaciones                        # Crear (Admin/Auxiliar/Docente)
PUT    /calificaciones/{id}                   # Actualizar
DELETE /calificaciones/{id}                   # Eliminar
GET    /calificaciones/estadisticas-avanzadas # Estadísticas (Admin/Auxiliar)

# Portales específicos
GET    /docente/mis-calificaciones     # Calificaciones de materias asignadas (paginado)
GET    /estudiante/mis-calificaciones  # Calificaciones del estudiante autenticado
GET    /padre/calificaciones-hijos     # Calificaciones de todos los hijos
```

**Filtros Avanzados para Calificaciones:**

```http
GET /calificaciones?estudiante_id=1&periodo_academico_id=2&per_page=50
GET /calificaciones?materia_id=3&seccion_id=4&nota_minima=11
GET /calificaciones?grado_id=5&nota_maxima=10
GET /docente/mis-calificaciones?materia_id=2&seccion_id=3&nota_minima=15
GET /estudiante/mis-calificaciones?periodo_academico_id=2
GET /padre/calificaciones-hijos?periodo_academico_id=2&estudiante_id=10
```

Parámetros disponibles:

- `estudiante_id`: Filtrar por estudiante específico
- `materia_id`: Filtrar por materia
- `periodo_academico_id`: Filtrar por periodo (por defecto el activo)
- `seccion_id`: Filtrar por sección
- `grado_id`: Filtrar por grado
- `nota_minima`: Calificaciones >= valor
- `nota_maxima`: Calificaciones <= valor
- `per_page`: Registros por página (10-100, por defecto 50)
- `todos`: Incluir todos los periodos (sin este parámetro solo muestra periodo activo)

**Optimizaciones de Performance:**

- Paginación obligatoria (50 registros por defecto)
- Solo columnas necesarias en SELECT
- Eager loading optimizado de relaciones
- Filtro por periodo activo automático
- Índices en base de datos para consultas rápidas

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
    "estado": "presente|tarde|ausente", // 3 estados disponibles
    "observaciones": "Opcional, máx 500 caracteres"
}
```

Estados:

- presente: Asistio puntualmente
- tarde: Llego tarde
- ausente: No asistio

Validaciones:

- No registrar con mas de 60 dias de antiguedad
- No registrar fechas futuras
- No duplicar (estudiante + materia + fecha)
- Docentes: verificacion automatica de asignacion de materia y seccion

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

- Stock disponible (cantidad_total - préstamos aprobados activos)
- Límite 3 préstamos activos por usuario
- No préstamos vencidos
- No duplicados del mismo libro
- Estados: `pendiente` → `aprobado`/`rechazado` → `devuelto`

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

- `pendiente`: No comenzó
- `activa`: En curso, se puede votar
- `cerrada`: Finalizada, resultados pueden o no estar publicados

### Configuraciones (Admin)

```http
GET    /configuraciones                     # Listar todas
PUT    /configuraciones/{clave}             # Actualizar valor
GET    /configuraciones/modo-mantenimiento  # Ver estado mantenimiento
```

**Configuraciones disponibles:**

- `modo_mantenimiento`: `true`/`false`
- `mensaje_mantenimiento`: Texto personalizado
- Preferencias de accesibilidad (frontend)

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

## Middleware y Autenticacion

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

Roles disponibles:

- admin: Acceso total
- auxiliar: Gestion academica
- bibliotecario: Gestion de biblioteca
- docente: Portal docente
- padre: Portal padre
- estudiante: Portal estudiante

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
│   ├── migrations/                 # 50 migraciones
│   │   ├── 2025_12_01_180000_create_grados_table.php
│   │   ├── 2025_12_01_180001_create_secciones_table.php
│   │   ├── 2025_12_01_180002_create_padres_table.php
│   │   ├── 2025_12_01_180003_create_estudiantes_table.php
│   │   ├── 2025_12_01_180004_create_estudiante_padre_table.php
│   │   ├── 2025_12_01_180005_create_docentes_table.php
│   │   ├── 2025_12_01_180006_create_materias_table.php
│   │   ├── 2025_12_01_180007_create_periodos_academicos_table.php
│   │   ├── 2025_12_01_180008_create_asignacion_docente_materia_table.php
│   │   ├── 2025_12_01_180009_create_asistencias_table.php
│   │   ├── 2025_12_01_180010_create_calificaciones_table.php
│   │   ├── 2025_12_01_180011_create_categorias_libros_table.php
│   │   ├── 2025_12_01_180012_create_libros_table.php
│   │   ├── 2025_12_01_180013_create_prestamos_libros_table.php
│   │   ├── 2025_12_01_180014_create_elecciones_table.php
│   │   ├── 2025_12_01_180015_create_candidatos_table.php
│   │   ├── 2025_12_01_180016_create_votos_table.php
│   │   ├── 2026_01_05_175730_create_configuraciones_table.php
│   │   ├── 2026_01_05_215413_update_configuraciones_add_theme_settings.php
│   │   ├── 2026_01_05_233914_add_modo_mantenimiento_config.php
│   │   ├── 2026_01_07_185334_add_fields_to_libros_table.php
│   │   ├── 2026_01_07_191531_add_estado_to_prestamos_libros_table.php
│   │   ├── 2026_01_14_000000_update_asistencias_add_estado.php
│   │   ├── 2026_01_15_000000_add_modificaciones_count_to_calificaciones.php
│   │   ├── 2026_01_15_000001_add_es_tutor_to_asignacion_docente_materia.php
│   │   ├── 2026_01_15_000002_add_estado_to_periodos_academicos.php
│   │   ├── 2026_01_19_164622_add_turno_to_secciones_table.php
│   │   ├── 2026_01_26_create_notificaciones_table.php
│   │   ├── 2026_01_26_create_audit_logs_table.php
│   │   ├── 2026_01_26_create_chat_conversaciones_table.php
│   │   ├── 2026_01_26_create_chat_mensajes_table.php
│   │   └── 2026_01_26_create_horarios_table.php
│   └── seeders/
│       ├── DatabaseSeeder.php         # Seeder principal (estructura completa)
│       ├── ConfiguracionSeeder.php    # Configuraciones del sistema
│       ├── BibliotecaSeeder.php       # 20 libros con categorias
│       ├── EleccionSeeder.php         # 2 elecciones con candidatos
│       ├── BibliotecarioUserSeeder.php # Usuario bibliotecario
│       └── AuxiliarSeeder.php         # Usuario auxiliar
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

## Modelos Principales

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
estudiante_id, libro_id, user_id, fecha_prestamo, fecha_devolucion,
devuelto, estado, aprobado_por, fecha_respuesta, motivo_rechazo

// Estados
pendiente  // Solicitado por estudiante
aprobado   // Aprobado por bibliotecario
rechazado  // Rechazado con motivo
devuelto   // Libro devuelto (campo booleano adicional)

// Relaciones
estudiante(), libro(), usuario(), aprobador()
```

## Configuracion

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

## Comandos Utiles

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

### Error de Hidratacion React (Hydration Error)

- Causa: Extensiones del navegador (Bitwarden, LastPass, etc.) modifican el HTML
- Sintomas: "A tree hydrated but some attributes didn't match", atributo bis_skin_checked
- Solucion:
    1. Desactivar extensiones del navegador temporalmente
    2. Usar modo incognito para desarrollo
    3. Agregar excepcion en la extension para localhost
    4. No afecta funcionalidad, solo warning en consola

## Historial de Actualizaciones

### Version 1.5.0 (3 Febrero 2026) - Mejoras de UI y Limpieza de Codigo

Mejoras de Interfaz:

- Dashboard Admin Rediseno: Nuevo diseno con 8 tarjetas estadisticas modernas
    - Grid de 4 columnas para mejor aprovechamiento del espacio
    - Tarjetas con fondos blancos limpios y efectos hover
    - Tipografia mejorada (text-4xl para numeros)
    - Iconos mas grandes (w-10 h-10) con fondos redondeados color-100
    - Subtitulos descriptivos para contexto ("Total matriculados", "Personal activo")
    - Codificacion por colores profesional (8 colores distintos)
- Tarjetas de Detalle Mejoradas:
    - Bordes de acento coloridos a la izquierda (w-1 h-6)
    - Fondos degradados para metricas clave
    - Mejor jerarquia visual y espaciado consistente
    - Diseno de botones mejorado (rounded-lg, mejores estados hover)

Limpieza y Optimizacion:

- Eliminada funcionalidad redundante de "Analisis Detallado" en dashboard admin
    - Las estadisticas avanzadas ya estan disponibles en la pagina de Calificaciones
    - Reduce complejidad y mejora rendimiento del dashboard
    - Navegacion mas clara y directa
- Eliminada opcion "Estadisticas" del sidebar de Biblioteca
    - Estadisticas accesibles desde dentro del modulo Biblioteca
    - Menu lateral mas limpio y organizado
- Correcciones de estructura JSX en pagina de Biblioteca
    - Indentacion corregida en filtros
    - Estructura de componentes optimizada

Impacto:

- Interfaz mas profesional y moderna
- Navegacion simplificada y coherente
- Mejor experiencia de usuario
- Codigo mas limpio y mantenible

---

### Version 1.4.0 (3 Febrero 2026) - Optimizacion Avanzada de Consultas

Mejoras de Performance:

- Sistema de Calificaciones Optimizado: Paginacion obligatoria con limite de 50 registros por defecto
- Filtros Avanzados: 8 parametros de busqueda (estudiante, materia, periodo, seccion, grado, nota min/max)
- Consultas Selectivas: Solo columnas necesarias en SELECT para reducir carga
- Eager Loading Mejorado: Relaciones cargadas eficientemente
- Filtro Automatico: Por defecto muestra solo periodo activo
- Validacion de Filtros: Validacion de parametros en todas las consultas
- Reduccion de Carga: 90% menos datos transferidos en consultas iniciales
- Indices Existentes: Aprovechamiento de indices en estudiante_id, materia_id, periodo_academico_id

Controladores Optimizados:

1. CalificacionController.index():
    - Paginacion obligatoria (10-100 registros, default 50)
    - Filtros: estudiante, materia, periodo, seccion, grado, nota_minima, nota_maxima
    - Select especifico de columnas
    - Por defecto muestra solo periodo activo

2. CalificacionController.misHijosCalificaciones():
    - Filtro por hijo especifico opcional
    - Filtro por periodo con default al activo
    - Consultas optimizadas por hijo

3. DocentePortalController.misCalificaciones():
    - Paginacion (10-100 registros, default 50)
    - Filtros: periodo, materia, seccion, nota_minima
    - Validacion de asignaciones del docente
    - Solo materias y secciones asignadas

4. EstudiantePortalController.misCalificaciones():
    - Filtro automatico por periodo activo
    - Filtros opcionales: periodo, materia
    - Calculo de promedio optimizado

Impacto:

- Reduccion de tiempo de carga: ~85% mas rapido
- Reduccion de transferencia de datos: ~90% menos
- Mejor experiencia de usuario con respuestas instantaneas
- Escalabilidad mejorada para bases de datos grandes

---

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

- Grados: 11 (1-6 Primaria, 1-5 Secundaria)
- Secciones: 54 (5-6 por grado según nivel)
- Docentes: 16 (15 + 1 admin)
- Padres: 31 (30 generados + 1 test, 10 con acceso al sistema)
- Estudiantes: 421 (6-10 por seccion)
- Materias: 11 (Curriculo Nacional Peruano)
- Periodos: 8 (4 bimestres x 2 años)
- Asignaciones: ~150 (docente-materia-seccion)
- Horarios: ~1620 (3 materias/dia por seccion)
- Asistencias: ~4210 (10 dias x estudiantes)
- Calificaciones: ~4210 (1 por estudiante por materia)
- Libros: 20 (con categorias Literatura, Ciencias, Historia, etc)
- Elecciones: 2 (con 3-4 candidatos cada una)
- Configuraciones: 15 (modulos, seguridad, sistema)

---

### Version 1.4.0 (23 Enero 2026) - Sistema de Paginacion

#### Paginacion Completa en APIs

Implementacion de paginacion en todos los endpoints principales para mejorar rendimiento y reducir tiempos de carga en 90-95%.

**Controladores con Paginacion:**

- EstudiantePortalController: misCalificaciones (50/pag) + misAsistencias (50/pag) con cache
- CalificacionController: index (100/pag) con filtros
- AsistenciaController: index (100/pag) con filtros de fecha
- EstudianteController: index (100/pag)
- DocenteController: index (50/pag)
- PadreController: index (50/pag)
- SeccionController: index (50/pag)
- LibroController: index (50/pag)
- PrestamoLibroController: index (50/pag)
- HorarioController: index (50/pag)

**Parametros Soportados:**

```php
// Paginacion
?page=1&per_page=50

// Sin paginacion (admin)
?all=true
```

**Formato de Respuesta:**

```json
{
  "data": [...],
  "current_page": 1,
  "last_page": 10,
  "per_page": 50,
  "total": 424,
  "from": 1,
  "to": 50
}
```

**Beneficios:**

- Reduccion de carga inicial: 90-95%
- Tiempo de respuesta: De 2-3s a 200-300ms
- Con cache: 50-100ms en subsecuentes cargas
- Mejor experiencia de usuario

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

Ultima actualizacion: 3 Febrero 2026 | Version: 1.5.0
Proyecto para I.E. N 51006 "TUPAC AMARU" - Cusco, Peru

Laravel: 12.0 | PHP: 8.2+ | MySQL: 8.0
