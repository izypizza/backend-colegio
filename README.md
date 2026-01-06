# Sistema de Gestión Escolar - Backend API

API REST desarrollada con **Laravel 12** para la gestión integral de la I.E. N° 51006 "TÚPAC AMARU". Sistema completo con autenticación, roles, calificaciones, asistencias, biblioteca digital y más.

---

## Credenciales de Prueba

### Usuarios Principales

| Rol               | Email                    | Contraseña    | Total Usuarios |
| ----------------- | ------------------------ | ------------- | -------------- |
| **Admin**         | admin@colegio.pe         | admin123      | 1              |
| **Auxiliar**      | auxiliar@colegio.pe      | auxiliar123   | 1              |
| **Bibliotecario** | bibliotecario@colegio.pe | biblio123     | 1              |
| **Docente**       | docente@colegio.pe       | docente123    | 16             |
| **Padre**         | padre@colegio.pe         | padre123      | 16             |
| **Estudiante**    | estudiante@colegio.pe    | estudiante123 | 1              |

### Docentes Adicionales (docente1-15)

Todos con contraseña: `docente123`

```
docente1@colegio.pe   - Luis Ramírez Ortiz
docente2@colegio.pe   - Carmen Rodríguez Morales
docente3@colegio.pe   - Fernando García Silva
...
docente15@colegio.pe  - Claudia López Ríos
```

### Padres Adicionales (padre1-15)

Todos con contraseña: `padre123`

```
padre1@colegio.pe     - Isabel Martínez Vargas
padre2@colegio.pe     - Isabel Rodríguez Mendoza
padre3@colegio.pe     - Elena Torres Vega
...
padre15@colegio.pe    - Juan Sánchez Mendoza
```

> **IMPORTANTE:** Estas credenciales son para desarrollo. Cambiar en producción.

---

## Características Recientes (Enero 2026)

### 🆕 Nuevas Funcionalidades Implementadas (5 de Enero 2026)

#### Sistema de Configuraciones y Accesibilidad

-   **Panel de Configuraciones Dinámico**

    -   Configuraciones del sistema solo accesibles para admin
    -   Preferencias de accesibilidad disponibles para todos los roles
    -   Gestión centralizada de módulos del sistema
    -   Almacenamiento en base de datos con cache

-   **Preferencias de Accesibilidad** (Todos los roles)

    -   **Tamaño de fuente**: Pequeño, Normal, Grande
    -   **Optimización para lector de pantalla**: Mejora compatibilidad con tecnologías asistivas
    -   Configuraciones guardadas por navegador (localStorage)
    -   Aplicación en tiempo real sin recargar

-   **Modo de Mantenimiento** (Solo Admin)
    -   Activación/desactivación con un solo toggle
    -   Mensaje personalizable para usuarios
    -   Acceso privilegiado para administradores durante mantenimiento
    -   Bloqueo automático para usuarios no-admin
    -   Página de mantenimiento profesional con diseño institucional
    -   Middleware global con exclusión de rutas de autenticación

#### Sistema de Configuraciones Backend

-   **Tabla `configuraciones`** con categorías:

    -   **sistema**: Configuraciones del sistema y mantenimiento
    -   **modulos**: Activación/desactivación de funcionalidades
    -   **seguridad**: Configuraciones de seguridad
    -   **accesibilidad**: Preferencias de accesibilidad (2 configs)

-   **Modelo `Configuracion`**:

    -   Método `obtener()` con cache automático
    -   Método `establecer()` con conversión de tipos
    -   Soporte para tipos: boolean, integer, string, json
    -   Cache de 1 hora por configuración

-   **Middleware `CheckMaintenanceMode`**:
    -   Verificación global en todas las rutas API
    -   Exclusión de rutas de autenticación (login, register)
    -   Respuesta 503 con información de mantenimiento
    -   Acceso privilegiado para role=admin

#### Mejoras en el Frontend

-   **Página de Configuraciones** (`/dashboard/configuraciones`)

    -   Vista adaptativa según rol del usuario
    -   Admin: Configuraciones completas + Modo mantenimiento + Accesibilidad
    -   Otros roles: Solo preferencias de accesibilidad
    -   Diseño con cards diferenciados por categoría
    -   Indicadores visuales de cambios pendientes
    -   Botón de limpiar cache del sistema

-   **Página de Mantenimiento** (`/maintenance`)

    -   Diseño profesional con animaciones
    -   Mensaje personalizado configurable
    -   Información institucional
    -   Botón de reintento
    -   Redirección automática desde API

-   **Interceptor API mejorado**:
    -   Detección automática de modo mantenimiento (503)
    -   Redirección a `/maintenance` para no-admin
    -   Preservación de acceso para administradores

### Mejoras Implementadas (Anteriores)

#### Sistema de Datos Realistas

-   **Nombres refactorizados** a 3 campos (nombres, apellido_paterno, apellido_materno)
-   **32 nombres peruanos** diversos para estudiantes
-   **26 nombres profesionales** para docentes
-   **Direcciones reales** de Lima (distritos y calles)
-   **Ocupaciones contextualizadas** para padres
-   **DNIs únicos** generados automáticamente

#### Seeders Completos

-   **50 secciones** según estructura real del colegio (10-36 por nivel)
-   **~400-600 estudiantes** distribuidos realísticamente (8-12 por sección)
-   **Tutores asignados** a cada sección desde docentes disponibles
-   **Asistencias** (últimos 20 días) con distribución realista (90% presente)
-   **Calificaciones** para todos los períodos con distribución normal
-   **15 libros** en biblioteca con categorías
-   **2 elecciones** estudiantiles activas
-   **Relaciones completas** padres-hijos

#### Mejoras Técnicas

-   **Sistema de 6 roles** completamente funcional
-   **Validaciones robustas** en todos los controladores
-   **Middleware de roles** funcionando correctamente
-   **Manejo de errores** mejorado con mensajes claros
-   **CORS configurado** para desarrollo local
-   **Recuperación de contraseña** mediante administrador

## Características Principales

### Autenticación y Seguridad

-   Autenticación con **Laravel Sanctum**
-   Sistema de roles multinivel (5 roles)
-   Middleware de autorización por endpoints
-   Protección CORS configurada
-   Tokens de sesión seguros

### 👥 Gestión de Usuarios

-   **6 Roles**: Admin, Auxiliar, Bibliotecario, Docente, Padre, Estudiante
-   Perfiles completos por rol con datos de contacto
-   Relaciones padres-hijos configurables
-   Asignación docente-materia-sección por período
-   Reseteo de contraseñas por administrador

### 📚 Gestión Académica

-   Grados y secciones con capacidad y turno
-   Materias del Currículo Nacional Peruano
-   Períodos académicos configurables (bimestres/trimestres)
-   Horarios por sección, día y hora
-   Calificaciones con períodos y observaciones
-   Registro de asistencias con estados (presente/ausente/tardanza/justificado)

### 🏫 Portales Personalizados con Visualizaciones

-   **Portal Docente**:
    -   Dashboard con KPIs y estadísticas
    -   Mis clases con tarjetas visuales
    -   Estudiantes y asignaciones
    -   Registro de calificaciones y asistencias
    -   Acciones rápidas contextuales
-   **Portal Estudiante**:
    -   Calificaciones con 4 tipos de gráficas interactivas
    -   Asistencias con análisis visual por materia
    -   Evolución de promedio por período
    -   Distribución de rendimiento
    -   Filtros y búsquedas avanzadas
-   **Portal Padre**:
    -   Vista multi-hijo con gráficas comparativas
    -   Seguimiento de calificaciones por período
    -   Análisis visual de rendimiento
    -   Información de asistencias
    -   Enlaces a perfiles y boletines

### 📖 Sistema de Biblioteca

-   Catálogo de libros con categorías
-   Préstamos y devoluciones
-   Historial de préstamos por usuario
-   Control de disponibilidad y stock

### 🗳️ Sistema de Elecciones Escolares

-   Creación de elecciones estudiantiles
-   Votación con interfaz intuitiva
-   Resultados en tiempo real
-   Prevención de voto duplicado

### 📊 Visualización y Análisis

-   **Gráficas interactivas con Recharts**
-   Múltiples tipos: barras, líneas, radar, dona
-   Tooltips informativos y responsivos
-   Códigos de color pedagógicos
-   Exportación de datos (próximamente)
-   Dashboard personalizado por rol

## 🗄️ Base de Datos

### Estructura (27 Tablas)

#### Usuarios y Roles

-   `users` - Usuarios del sistema
-   `roles` - Roles disponibles
-   `role_user` - Asignación de roles

#### Académico

-   `grados` - Niveles educativos (1° Primaria, 5° Secundaria, etc.)
-   `secciones` - Secciones por grado (A, B, C) con capacidad y turno
-   `materias` - Asignaturas del currículo
-   `periodos_academicos` - Bimestres, trimestres, etc.

#### Personas

-   `estudiantes` - Estudiantes con sección asignada
-   `docentes` - Docentes con especialidad
-   `padres` - Padres de familia
-   `padre_estudiante` - Relación padres-hijos

#### Gestión Educativa

-   `asignacion_docente_materia` - Docente + Materia + Sección + Período
-   `horarios` - Horario por sección, materia, día y hora
-   `calificaciones` - Notas por estudiante, materia y período
-   `asistencias` - Registro diario de asistencia

#### Biblioteca

-   `categorias_libros` - Categorías del catálogo
-   `libros` - Libros con ISBN, autor, stock
-   `prestamos_libros` - Préstamos con fechas

#### Elecciones

-   `elecciones` - Elecciones escolares
-   `candidatos` - Candidatos por elección
-   `votos` - Votos emitidos

## 🚀 Instalación

### Requisitos

-   PHP >= 8.2
-   Composer >= 2.0
-   MySQL >= 8.0 o MariaDB >= 10.3
-   XAMPP (recomendado) o servidor local

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
DB_DATABASE=tupac_amaru_db
DB_USERNAME=root
DB_PASSWORD=
```

### Paso 4: Migrar y Poblar

```bash
# Crear tablas
php artisan migrate

# Poblar datos de prueba (IMPORTANTE para testing)
# Genera datos completos con:
# - 11 grados (Primaria 1-6 | Secundaria 1-5)
# - 50 secciones (estructura real del colegio)
#   * Primaria 1-2: A, B, C, D, E (5 secciones)
#   * Primaria 3-6: A, B, C, D, E, F (6 secciones)
#   * Secundaria 1-5: A, B, C, D (4 secciones)
# - 15 docentes con usuarios
# - 30 padres (10 con usuarios)
# - ~400-600 estudiantes (8-12 por sección)
# - Cada sección tiene un docente tutor asignado
# - 11 materias del Currículo Nacional
# - 4 períodos académicos (I, II, III, IV Bimestre 2025)
# - Asignaciones docente-materia por sección
# - 50 horarios completos
# - Asistencias de últimos 20 días
# - Calificaciones para todos los períodos
# - 15 libros en biblioteca
# - 2 elecciones estudiantiles
php artisan db:seed

# Los datos generados incluyen:
# ✅ Relaciones padres-hijos
# ✅ Asistencias realistas (90% presente, 10% ausente)
# ✅ Calificaciones con distribución normal (11-18 puntos)
# ✅ Datos completos de contacto
# ✅ 6 roles con permisos específicos
```

### Paso 5: Iniciar Servidor

```bash
# Servidor de desarrollo
php artisan serve

# O con host personalizado
php artisan serve --host=0.0.0.0 --port=8000
```

## 📡 API Endpoints

### 📊 Resumen de APIs

**Total de Endpoints Activos**: **122 rutas API**

#### Distribución por Categoría:

-   🔐 **Autenticación**: 4 endpoints
-   📊 **Dashboard**: 1 endpoint
-   🎓 **Gestión Académica**: 45 endpoints
-   👥 **Gestión de Personas**: 25 endpoints
-   📖 **Biblioteca**: 12 endpoints
-   🗳️ **Elecciones**: 11 endpoints
-   🔑 **Permisos Auxiliares**: 4 endpoints
-   🎯 **Portales por Rol**: 20 endpoints

---

### 🔐 Autenticación (4 endpoints)

```http
POST   /api/auth/login       # Iniciar sesión
POST   /api/auth/register    # Registrar usuario
POST   /api/auth/logout      # Cerrar sesión (Auth)
GET    /api/auth/me          # Usuario autenticado (Auth)
```

---

### 📊 Dashboard con Estadísticas Reales (1 endpoint)

```http
GET    /api/dashboard/stats  # Estadísticas dinámicas según rol (Auth)
```

#### Respuestas por Rol:

**🔹 Admin/Auxiliar/Bibliotecario:**
Estadísticas completas del sistema con datos en tiempo real.

```json
{
    "estudiantes": 350,
    "docentes": 45,
    "padres": 280,
    "materias": 12,
    "secciones": 33,
    "grados": 11,
    "asistencias_hoy": {
        "total": 340,
        "presentes": 325,
        "ausentes": 15,
        "porcentaje_asistencia": 95.6
    },
    "calificaciones": {
        "promedio_general": 14.2,
        "total_calificaciones": 4200,
        "aprobados": 3780,
        "desaprobados": 420
    },
    "biblioteca": {
        "prestamos_activos": 45,
        "prestamos_vencidos": 3,
        "total_prestamos_mes": 120
    },
    "elecciones": {
        "activas": 1,
        "proximas": 2
    },
    "distribucion_grados": [
        { "grado": "1° Primaria", "secciones": 3, "estudiantes": 90 },
        { "grado": "2° Primaria", "secciones": 3, "estudiantes": 88 }
    ],
    "actividad_reciente": [
        {
            "tipo": "calificacion",
            "titulo": "Nueva calificación registrada",
            "descripcion": "Matemática - Juan Pérez López",
            "fecha": "hace 5 minutos",
            "icono": "clipboard",
            "color": "blue"
        }
    ]
}
```

**🔹 Docente:**
Vista personalizada con mis clases, estudiantes y tareas pendientes.

```json
{
    "mis_clases": 8,
    "mis_estudiantes": 240,
    "mis_materias": 2,
    "mis_secciones": 8,
    "asistencias_hoy": 5,
    "calificaciones": {
        "registradas": 180,
        "pendientes": 60
    },
    "clases_detalle": [
        {
            "materia": "Matemática",
            "seccion": "A",
            "grado": "3° Primaria",
            "estudiantes": 30
        }
    ],
    "tareas_pendientes": [
        {
            "tipo": "Asistencias",
            "descripcion": "Registrar asistencias diarias",
            "prioridad": "alta"
        },
        {
            "tipo": "Calificaciones",
            "descripcion": "Registrar calificaciones del periodo",
            "prioridad": "normal"
        }
    ]
}
```

**🔹 Padre:**
Seguimiento completo del rendimiento de mis hijos con alertas.

```json
{
    "mis_hijos": 2,
    "hijos_detalle": [
        {
            "id": 1,
            "nombre": "Juan Pérez García",
            "seccion": "A",
            "grado": "3° Primaria",
            "asistencia": {
                "total": 20,
                "presentes": 18,
                "tardanzas": 1,
                "faltas": 1
            },
            "promedio": 14.5
        }
    ],
    "resumen": {
        "promedio_general": 14.2,
        "periodo_actual": "Primer Bimestre 2025"
    },
    "alertas": [
        {
            "tipo": "asistencia",
            "estudiante": "María Pérez",
            "mensaje": "Ha faltado 5 veces este mes",
            "severidad": "alta"
        }
    ]
}
```

**🔹 Estudiante:**
Mi rendimiento académico con promedio, asistencias y recordatorios motivacionales.

```json
{
    "info_personal": {
        "nombre_completo": "María López Sánchez",
        "seccion": "A",
        "grado": "5° Secundaria",
        "edad": 16
    },
    "asistencia_mes": {
        "total": 22,
        "presentes": 20,
        "tardanzas": 1,
        "faltas": 1,
        "porcentaje": 90.9
    },
    "calificaciones": {
        "promedio": 15.2,
        "total_cursos": 12,
        "aprobados": 12,
        "desaprobados": 0,
        "mejor_nota": 18,
        "curso_mejor": "Matemática"
    },
    "calificaciones_detalle": [
        { "materia": "Matemática", "nota": 18, "estado": "Aprobado" },
        { "materia": "Comunicación", "nota": 16, "estado": "Aprobado" }
    ],
    "recordatorios": [
        {
            "titulo": "Asistencia",
            "mensaje": "✅ Tu asistencia está bien. ¡Sigue así!",
            "tipo": "success"
        },
        {
            "titulo": "Promedio",
            "mensaje": "🌟 ¡Excelente promedio! Continúa con tu esfuerzo.",
            "tipo": "success"
        }
    ]
}
```

---

POST /api/auth/login # Iniciar sesión
POST /api/auth/register # Registrar usuario
POST /api/auth/logout # Cerrar sesión
GET /api/auth/me # Usuario actual
GET /api/user # Usuario autenticado

````

### 📊 Dashboard (1 endpoint)

```http
GET    /api/dashboard/stats  # Estadísticas personalizadas por rol
````

---

### 🎓 Gestión Académica (45 endpoints)

#### Grados (7 endpoints) - Admin crear/editar, Todos leer

```http
GET    /api/grados           # Listar todos los grados
POST   /api/grados           # Crear grado (Admin)
GET    /api/grados/{id}      # Ver grado específico
PUT    /api/grados/{id}      # Actualizar grado (Admin)
DELETE /api/grados/{id}      # Eliminar grado (Admin)
```

#### Secciones (7 endpoints) - Admin/Auxiliar crear, Todos leer

```http
GET    /api/secciones        # Listar secciones
POST   /api/secciones        # Crear sección (Admin/Auxiliar)
GET    /api/secciones/{id}   # Ver sección específica
PUT    /api/secciones/{id}   # Actualizar (Admin/Auxiliar)
DELETE /api/secciones/{id}   # Eliminar (Admin/Auxiliar)
```

#### Materias (7 endpoints) - Admin crear, Todos leer

```http
GET    /api/materias         # Listar materias
POST   /api/materias         # Crear materia (Admin)
GET    /api/materias/{id}    # Ver materia específica
PUT    /api/materias/{id}    # Actualizar (Admin)
DELETE /api/materias/{id}    # Eliminar (Admin)
```

#### Períodos Académicos (6 endpoints) - Admin crear, Todos leer

```http
GET    /api/periodos         # Listar períodos
POST   /api/periodos         # Crear período (Admin)
GET    /api/periodos/{id}    # Ver período específico
PUT    /api/periodos/{id}    # Actualizar (Admin)
DELETE /api/periodos/{id}    # Eliminar (Admin)
```

#### Horarios (7 endpoints) - Admin/Auxiliar crear, Todos leer

```http
GET    /api/horarios         # Listar horarios
POST   /api/horarios         # Crear horario (Admin/Auxiliar/Docente)
GET    /api/horarios/{id}    # Ver horario específico
PUT    /api/horarios/{id}    # Actualizar (Admin/Auxiliar/Docente)
DELETE /api/horarios/{id}    # Eliminar (Admin/Auxiliar/Docente)
```

#### Asignaciones Docente-Materia (5 endpoints)

```http
GET    /api/asignaciones     # Listar asignaciones (Admin/Auxiliar/Docente)
POST   /api/asignaciones     # Crear asignación (Admin/Auxiliar/Docente)
GET    /api/asignaciones/{id}# Ver asignación específica
PUT    /api/asignaciones/{id}# Actualizar (Admin/Auxiliar/Docente)
DELETE /api/asignaciones/{id}# Eliminar (Admin/Auxiliar/Docente)
```

---

### 📝 Calificaciones (11 endpoints)

```http
# CRUD Calificaciones (Admin/Auxiliar)
GET    /api/calificaciones   # Listar todas las calificaciones
POST   /api/calificaciones   # Crear calificación
GET    /api/calificaciones/{id}      # Ver calificación específica
PUT    /api/calificaciones/{id}      # Actualizar calificación
DELETE /api/calificaciones/{id}      # Eliminar calificación

# Docentes pueden crear/editar sus calificaciones
POST   /api/docente/calificaciones   # Docente registra calificación
PUT    /api/docente/calificaciones/{id}  # Docente actualiza calificación

# Reportes
GET    /api/calificaciones/boletin/{estudiante_id}/{periodo_id}  # Boletín
GET    /api/calificaciones/reporte/materia/{materia_id}          # Reporte por materia

# Padres
GET    /api/mis-hijos-calificaciones # Padre ve calificaciones de sus hijos
```

---

### 📋 Asistencias (11 endpoints)

```http
# CRUD Asistencias (Admin/Auxiliar)
GET    /api/asistencias      # Listar todas las asistencias
POST   /api/asistencias      # Registrar asistencia
GET    /api/asistencias/{id} # Ver asistencia específica
PUT    /api/asistencias/{id} # Actualizar asistencia
DELETE /api/asistencias/{id} # Eliminar asistencia

# Docentes pueden registrar asistencias
POST   /api/docente/asistencias      # Docente registra asistencia
PUT    /api/docente/asistencias/{id} # Docente actualiza asistencia

# Reportes
GET    /api/asistencias/reporte/estudiante/{id}  # Reporte por estudiante
GET    /api/asistencias/reporte/seccion/{id}     # Reporte por sección
```

---

### 👥 Gestión de Personas (25 endpoints)

#### Estudiantes (5 endpoints) - Admin/Auxiliar

```http
GET    /api/estudiantes      # Listar estudiantes
POST   /api/estudiantes      # Crear estudiante
GET    /api/estudiantes/{id} # Ver estudiante específico
PUT    /api/estudiantes/{id} # Actualizar estudiante
DELETE /api/estudiantes/{id} # Eliminar estudiante
```

#### Docentes (5 endpoints) - Admin/Auxiliar/Docente

```http
GET    /api/docentes         # Listar docentes
POST   /api/docentes         # Crear docente
GET    /api/docentes/{id}    # Ver docente específico
PUT    /api/docentes/{id}    # Actualizar docente
DELETE /api/docentes/{id}    # Eliminar docente
```

#### Padres (5 endpoints) - Admin/Auxiliar/Docente/Padre

```http
GET    /api/padres           # Listar padres
POST   /api/padres           # Crear padre
GET    /api/padres/{id}      # Ver padre específico
PUT    /api/padres/{id}      # Actualizar padre
DELETE /api/padres/{id}      # Eliminar padre
```

---

### 📖 Biblioteca Digital (12 endpoints)

#### Categorías de Libros (5 endpoints) - Admin/Bibliotecario

```http
GET    /api/categorias-libros     # Listar categorías
POST   /api/categorias-libros     # Crear categoría
GET    /api/categorias-libros/{id}# Ver categoría específica
PUT    /api/categorias-libros/{id}# Actualizar categoría
DELETE /api/categorias-libros/{id}# Eliminar categoría
```

#### Libros (6 endpoints) - Admin/Bibliotecario crear, Todos consultar

```http
GET    /api/libros           # Listar libros (Admin/Bibliotecario)
POST   /api/libros           # Crear libro
GET    /api/libros/{id}      # Ver libro específico
PUT    /api/libros/{id}      # Actualizar libro
DELETE /api/libros/{id}      # Eliminar libro
GET    /api/libros-disponibles    # Consultar libros disponibles (Todos)
```

#### Préstamos (4 endpoints)

```http
GET    /api/prestamos        # Listar préstamos (Admin/Bibliotecario)
POST   /api/prestamos        # Crear préstamo (Admin/Bibliotecario)
POST   /api/prestamos/{id}/devolver   # Devolver libro
GET    /api/mis-prestamos    # Ver mis préstamos (Todos)
GET    /api/biblioteca/reportes      # Reportes de biblioteca
```

---

### 🗳️ Elecciones Escolares (11 endpoints)

#### Gestión de Elecciones (8 endpoints)

```http
# CRUD Elecciones (Admin crear/editar, Todos leer)
GET    /api/elecciones       # Listar elecciones
POST   /api/elecciones       # Crear elección (Admin)
GET    /api/elecciones/{id}  # Ver elección específica
PUT    /api/elecciones/{id}  # Actualizar elección (Admin)
DELETE /api/elecciones/{id}  # Eliminar elección (Admin)

# Acciones de Elección (Admin)
POST   /api/elecciones/{id}/activar           # Activar votación
POST   /api/elecciones/{id}/cerrar            # Cerrar votación
POST   /api/elecciones/{id}/publicar-resultados # Publicar resultados
```

#### Votación (3 endpoints)

```http
POST   /api/votos            # Votar (Estudiante)
GET    /api/mis-votos        # Ver mis votos (Estudiante)
GET    /api/elecciones/{id}/ya-vote  # Verificar si ya voté
GET    /api/elecciones/{id}/resultados # Ver resultados (Todos si publicado)
```

---

### 🔑 Permisos Auxiliares (4 endpoints) - Sistema de permisos especiales

```http
GET    /api/auxiliar-permisos        # Listar permisos (Admin)
POST   /api/auxiliar-permisos        # Otorgar permiso (Admin)
GET    /api/auxiliar-permisos/{userId}   # Ver permisos de usuario (Admin)
DELETE /api/auxiliar-permisos/{userId}   # Revocar permisos (Admin)
GET    /api/mi-permiso-especial      # Ver mis permisos (Auxiliar)
```

---

### 🎯 Portales Personalizados por Rol (20 endpoints)

#### 👨‍🏫 Portal Docente (6 endpoints)

```http
GET    /api/docente/mis-asignaciones      # Mis materias y secciones asignadas
GET    /api/docente/mis-estudiantes       # Estudiantes de mis secciones
GET    /api/docente/mis-calificaciones    # Calificaciones que he registrado
GET    /api/docente/mis-asistencias       # Asistencias que he registrado
POST   /api/docente/registrar-calificacion   # Registrar nueva calificación
POST   /api/docente/registrar-asistencia     # Registrar nueva asistencia
```

#### 🎓 Portal Estudiante (4 endpoints)

```http
GET    /api/estudiante/mi-perfil          # Mi información personal
GET    /api/estudiante/mis-calificaciones # Mis calificaciones con gráficas
GET    /api/estudiante/mis-asistencias    # Mi historial de asistencia
GET    /api/estudiante/mi-boletin/{periodo_id}  # Mi boletín del período
```

#### 👨‍👩‍👧 Portal Padre (4 endpoints)

```http
GET    /api/padre/mis-hijos               # Lista de mis hijos
GET    /api/padre/calificaciones-hijos    # Calificaciones de todos mis hijos
GET    /api/padre/asistencias-hijo/{hijo_id}         # Asistencias de un hijo
GET    /api/padre/boletin-hijo/{hijo_id}/{periodo_id}# Boletín de un hijo
```

---

## 🔒 Control de Acceso por Rol

| Recurso                      | Admin   | Auxiliar   | Docente       | Padre        | Estudiante | Bibliotecario |
| ---------------------------- | ------- | ---------- | ------------- | ------------ | ---------- | ------------- |
| **Grados/Materias/Períodos** | ✏️ CRUD | 👁️ Ver     | 👁️ Ver        | 👁️ Ver       | 👁️ Ver     | -             |
| **Secciones/Horarios**       | ✏️ CRUD | ✏️ CRUD    | ✏️ CRUD       | 👁️ Ver       | 👁️ Ver     | -             |
| **Estudiantes**              | ✏️ CRUD | ✏️ CRUD    | 👁️ Ver        | -            | -          | -             |
| **Docentes**                 | ✏️ CRUD | ✏️ CRUD    | 👁️ Ver        | -            | -          | -             |
| **Padres**                   | ✏️ CRUD | ✏️ CRUD    | 👁️ Ver        | 👁️ Ver       | -          | -             |
| **Calificaciones**           | ✏️ CRUD | ✏️ CRUD    | ✏️ Sus clases | 👁️ Sus hijos | 👁️ Propias | -             |
| **Asistencias**              | ✏️ CRUD | ✏️ CRUD    | ✏️ Sus clases | 👁️ Sus hijos | 👁️ Propias | -             |
| **Biblioteca**               | ✏️ CRUD | -          | 👁️ Ver        | 👁️ Ver       | 👁️ Ver     | ✏️ CRUD       |
| **Elecciones**               | ✏️ CRUD | -          | 👁️ Ver        | 👁️ Ver       | 🗳️ Votar   | -             |
| **Permisos Auxiliares**      | ✏️ CRUD | 👁️ Propios | -             | -            | -          | -             |

**Leyenda**: ✏️ CRUD (Crear/Leer/Actualizar/Eliminar) | 👁️ Ver (Solo lectura) | 🗳️ Votar

---

## 🛠️ Helper de Validación de Año Académico

### AcademicYearHelper

Helper centralizado para validaciones basadas en el año académico actual:

```php
use App\Helpers\AcademicYearHelper;

// Obtener año académico actual
$anioActual = AcademicYearHelper::getCurrentAcademicYear();
// Retorna: 2025 (basado en el último período académico registrado)

// Validar edad para un grado
$validacion = AcademicYearHelper::validarEdadParaGrado(
    '2015-03-15',      // Fecha de nacimiento
    '4° Primaria'      // Nombre del grado
);

// Retorna:
// [
//     'valido' => true,
//     'mensaje' => 'La edad es apropiada para el grado',
//     'detalles' => [
//         'edad_actual' => 10,
//         'edad_esperada' => 9,
//         'edad_minima' => 7,
//         'edad_maxima' => 11,
//         'anio_academico' => 2025
//     ]
// ]

// Calcular edad en un año específico
$edad = AcademicYearHelper::calcularEdad('2015-03-15', 2025);
// Retorna: 10
```

### Uso en Controladores

El helper se usa automáticamente en **EstudianteController** para validar:

1. **Año académico actual**: Obtiene el año del período académico más reciente
2. **Edad esperada por grado**:
    - Primaria: 6 años (1°) a 11 años (6°)
    - Secundaria: 12 años (1°) a 16 años (5°)
3. **Tolerancia**: ±2 años en Primaria, ±3 años en Secundaria
4. **Mensajes descriptivos**: Indica edad esperada, rango válido y año de nacimiento esperado

**Ejemplo de validación**:

```
Para 1° Primaria en 2025:
- Edad esperada: 6 años
- Rango válido: 4-8 años
- Año de nacimiento esperado: 2019
```

---

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

| Email                 | Password | Rol        | Descripción                 |
| --------------------- | -------- | ---------- | --------------------------- |
| admin@colegio.pe      | password | Admin      | Control total del sistema   |
| auxiliar@colegio.pe   | password | Auxiliar   | Personal administrativo     |
| docente@colegio.pe    | password | Docente    | Profesor con 4 asignaciones |
| padre@colegio.pe      | password | Padre      | Padre con 2 hijos           |
| estudiante@colegio.pe | password | Estudiante | Estudiante matriculado      |

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

-   Tokens de sesión con expiración
-   Logout revoca tokens
-   Middleware `auth:sanctum` en todas las rutas protegidas

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

## � Refactorización de Nombres Completos

### Estructura de Nombres Separados

El sistema utiliza el **formato peruano estándar** con campos separados para nombres y apellidos:

**Tablas Afectadas**: `estudiantes`, `docentes`, `padres`

**Campos**:

-   `nombres` VARCHAR(255) NOT NULL
-   `apellido_paterno` VARCHAR(255) NOT NULL
-   `apellido_materno` VARCHAR(255) NOT NULL
-   `dni` VARCHAR(8) NOT NULL UNIQUE
-   `nombre_completo` (Accessor computed - no existe en BD)

**Formato de Nombre Completo**:

```
Apellido Paterno + Apellido Materno + , + Nombres
Ejemplo: Torres Ortiz, Luciana
```

### Accessor Eloquent

Todos los modelos incluyen el accessor:

```php
public function getNombreCompletoAttribute(): string
{
    return trim("{$this->apellido_paterno} {$this->apellido_materno}, {$this->nombres}");
}

protected $appends = ['nombre_completo'];
```

### Respuesta API

```json
{
    "id": 1,
    "nombres": "Luciana",
    "apellido_paterno": "Torres",
    "apellido_materno": "Ortiz",
    "dni": "30544040",
    "nombre_completo": "Torres Ortiz, Luciana",
    "fecha_nacimiento": "2010-05-15",
    "seccion_id": 5
}
```

### Beneficios

✅ Búsquedas precisas por apellido paterno (estándar peruano)  
✅ Ordenamiento alfabético correcto por apellidos  
✅ DNI único de 8 dígitos por persona  
✅ Compatibilidad con documentos oficiales del MINEDU

## 📊 Visualización de Datos y Gráficas Interactivas

### Frontend con Recharts

El sistema frontend utiliza **Recharts** para visualizaciones interactivas y dinámicas.

### Portal Estudiante

**Vista de Calificaciones** (`/dashboard/estudiante/mis-calificaciones`)

-   📊 **Gráfico de Barras**: Notas por materia con código de colores
-   🎯 **Gráfico de Radar**: Vista comparativa vs promedio general
-   🥧 **Gráfico de Dona**: Distribución de calificaciones (Excelente/Bueno/Aprobado/Reprobado)
-   📈 **Gráfico de Línea**: Evolución del promedio por periodo académico
-   📋 Tabla detallada con observaciones por materia
-   🎨 Códigos de color según rendimiento:
    -   Verde: 16-20 (Excelente)
    -   Azul: 13-15 (Bueno)
    -   Amarillo: 11-12 (Aprobado)
    -   Rojo: 0-10 (Reprobado)

**Vista de Asistencias** (`/dashboard/estudiante/mis-asistencias`)

-   📊 **Gráfico de Dona**: Distribución por estado (Presente/Ausente/Tardanza/Justificado)
-   📚 **Gráfico de Barras**: Asistencia por materia (barras apiladas)
-   📈 **Gráfico de Línea**: Tendencia semanal de porcentaje de asistencia
-   📋 Tarjetas de KPI con iconos (Total/Presentes/Ausentes/%)
-   🔍 Filtros por rango de fechas
-   📅 Tabla completa con observaciones

### Portal Docente

**Dashboard Principal** (`/dashboard/docente`)

-   📊 4 Tarjetas KPI con iconos animados:
    -   Total de asignaciones activas
    -   Total de estudiantes en clases
    -   Promedio general de estudiantes
    -   Porcentaje de asistencia promedio
-   ⚡ Acciones rápidas con enlaces directos
-   📚 Resumen visual de clases asignadas
-   📌 Recordatorios y notificaciones importantes
-   💡 Consejos y mejores prácticas

**Vista de Clases** (`/dashboard/docente/mis-clases`)

-   🎴 Tarjetas visuales por materia
-   📋 Información detallada de cada asignación
-   🔗 Enlaces rápidos a estudiantes, asistencia y calificaciones
-   🎨 Iconos y badges por nivel educativo

### Portal Padre

**Calificaciones de Hijos** (`/dashboard/padre/calificaciones`)

-   📊 **Gráfico de Barras**: Notas por materia de cada hijo
-   🎯 **Gráfico de Radar**: Vista comparativa de rendimiento
-   📈 Resumen con promedio general y estadísticas
-   👨‍👩‍👧 Vista multi-hijo con tarjetas separadas
-   📋 Tabla detallada por estudiante
-   🔗 Enlaces a perfil completo y asistencias

### Características de las Gráficas

✅ **Interactivas**: Tooltips informativos al pasar el mouse  
✅ **Responsivas**: Se adaptan a móvil, tablet y desktop  
✅ **Accesibles**: Colores según convenciones pedagógicas  
✅ **Profesionales**: Diseño limpio y moderno  
✅ **Performantes**: Renderizado optimizado con Recharts  
✅ **Informativas**: Múltiples vistas de los mismos datos

### Tecnologías de Visualización

```json
{
    "recharts": "^3.6.0",
    "react": "19.2.0",
    "next": "^16.0.10"
}
```

**Componentes Utilizados**:

-   `BarChart`: Gráficos de barras simples y apiladas
-   `LineChart`: Tendencias y evolución temporal
-   `PieChart`: Distribuciones y porcentajes
-   `RadarChart`: Comparativas multidimensionales
-   `ResponsiveContainer`: Adaptación automática al contenedor

## 🔧 Factories y Seeders Mejorados

### Factories Actualizados

Los factories ahora generan datos más completos y realistas:

**EstudianteFactory**:

-   32 nombres variados
-   15 apellidos paternos y maternos
-   Direcciones realistas de Lima
-   Teléfonos con formato peruano (9XXXXXXXX)
-   Emails opcionales (30% de estudiantes)
-   Fechas de nacimiento entre 6-17 años

**DocenteFactory**:

-   26 nombres de docentes
-   Títulos profesionales variados
-   Especialidades del Currículo Nacional
-   Fecha de ingreso histórica (1-10 años)
-   Estado activo/inactivo
-   Emails institucionales (@colegio.pe)

**PadreFactory**:

-   15 nombres variados
-   Teléfonos de trabajo opcionales (60%)
-   Lugar de trabajo (70%)
-   Estado civil variado
-   14 tipos de ocupación
-   Emails personales (@gmail.com)

### DatabaseSeeder Mejorado

Genera un sistema completo y realista:

**Secciones**: 50 secciones con estructura real del colegio  
**Estudiantes**: 400-600 estudiantes (8-12 por sección)  
**Tutores**: Cada sección tiene un docente tutor asignado  
**Asistencias**: Últimos 20 días laborables con:

-   Observaciones contextuales

**Calificaciones**: Todos los periodos con:

-   Distribución normal (11-18 puntos)
-   10% estudiantes destacados (17-20)
-   10% con dificultades (8-12)
-   Observaciones para casos especiales

**Relaciones**:

-   1-2 padres por estudiante
-   5-7 materias por sección
-   Horarios distribuidos en 5 días
-   Asignaciones docente-materia-sección

### Usuarios de Prueba

```
Admin:
  Email: admin@colegio.pe
  Password: password
  Rol: admin

Docente:
  Email: docente@colegio.pe
  Password: password
  Rol: docente

Padre:
  Email: padre@colegio.pe
  Password: password
  Rol: padre

Estudiante:
  Email: estudiante@colegio.pe
  Password: password
  Rol: estudiante

Bibliotecario:
  Email: bibliotecario@colegio.pe
  Password: password
  Rol: bibliotecario
```

## 🔐 Autenticación y Permisos

### Sistema de Autenticación

-   **Método**: Laravel Sanctum con tokens Bearer
-   **Middleware**: `auth:sanctum` para rutas protegidas
-   **Roles**: Sistema basado en campo `role` en tabla `users`

### Obtener Token de Autenticación

#### Opción 1: Con PowerShell

```powershell
$body = @{
    email = "admin@colegio.pe"
    password = "password"
} | ConvertTo-Json

$response = Invoke-WebRequest -Uri "http://localhost:8000/api/auth/login" `
    -Method POST `
    -ContentType "application/json" `
    -Body $body

$response.Content | ConvertFrom-Json
```

#### Opción 2: Con cURL (Git Bash)

```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@colegio.pe","password":"password"}'
```

#### Opción 3: Frontend JavaScript

```javascript
fetch("http://localhost:8000/api/auth/login", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
        email: "admin@colegio.pe",
        password: "password",
    }),
})
    .then((r) => r.json())
    .then((data) => {
        localStorage.setItem("auth_token", data.token);
        localStorage.setItem("user_data", JSON.stringify(data.user));
    });
```

### Usar Token en Peticiones

```powershell
# Ejemplo: Listar estudiantes
$token = "TU_TOKEN_AQUI"
Invoke-WebRequest -Uri "http://localhost:8000/api/estudiantes" `
    -Headers @{
        "Authorization" = "Bearer $token"
        "Accept" = "application/json"
    }
```

### Tabla de Permisos por Endpoint

| Endpoint                | Método                 | Requiere Rol                            | Descripción                     |
| ----------------------- | ---------------------- | --------------------------------------- | ------------------------------- |
| `/api/auth/login`       | POST                   | -                                       | Login público                   |
| `/api/auth/logout`      | POST                   | Autenticado                             | Cerrar sesión                   |
| `/api/auth/me`          | GET                    | Autenticado                             | Datos del usuario               |
| `/api/estudiantes`      | GET, POST, PUT, DELETE | `admin`, `auxiliar`                     | Gestión de estudiantes          |
| `/api/docentes`         | GET, POST, PUT, DELETE | `admin`, `auxiliar`, `docente`          | Gestión de docentes             |
| `/api/padres`           | GET, POST, PUT, DELETE | `admin`, `auxiliar`, `docente`, `padre` | Gestión de padres               |
| `/api/asistencias`      | POST, PUT              | `admin`, `auxiliar`, `docente`          | Registro de asistencias         |
| `/api/calificaciones`   | POST, PUT              | `admin`, `auxiliar`, `docente`          | Registro de calificaciones      |
| `/api/grados`           | GET                    | Todos                                   | Listar grados (solo lectura)    |
| `/api/grados`           | POST, PUT, DELETE      | `admin`                                 | Gestión de grados               |
| `/api/secciones`        | GET                    | Todos                                   | Listar secciones (solo lectura) |
| `/api/materias`         | GET                    | Todos                                   | Listar materias (solo lectura)  |
| `/api/periodos`         | GET                    | Todos                                   | Listar períodos (solo lectura)  |
| `/api/libros`           | GET, POST, PUT, DELETE | `admin`, `bibliotecario`                | Gestión de biblioteca           |
| `/api/prestamos-libros` | GET, POST, PUT         | `admin`, `bibliotecario`                | Gestión de préstamos            |

### Middleware de Roles

El sistema usa `RoleMiddleware` que verifica:

1. Usuario autenticado (token válido)
2. Rol del usuario coincide con los permitidos

```php
// routes/api.php
Route::middleware(['role:admin,auxiliar'])->group(function () {
    Route::apiResource('estudiantes', EstudianteController::class);
});
```

### Respuestas de Error

**401 Unauthorized**: No autenticado o token inválido

```json
{
    "error": "No autenticado",
    "message": "Debe iniciar sesión para acceder a este recurso"
}
```

**403 Forbidden**: Sin permisos suficientes

```json
{
    "error": "Acceso denegado",
    "message": "No tiene permisos para acceder a este recurso",
    "required_roles": ["admin", "auxiliar"],
    "user_role": "padre"
}
```

### Debugging de Autenticación

Si tienes errores al registrar datos:

1. **Verifica que estés autenticado**: El token debe estar en localStorage del navegador
2. **Verifica tu rol**: Usa `admin@colegio.pe` para acceso completo
3. **Revisa la consola del navegador**: DevTools (F12) → Console/Network
4. **Revisa logs de Laravel**: `storage/logs/laravel.log`

✅ Estructura normalizada para reportes y exportaciones

### Validaciones Backend

```php
// En Requests de Estudiante, Docente, Padre
'nombres' => 'required|string|max:255',
'apellido_paterno' => 'required|string|max:255',
'apellido_materno' => 'required|string|max:255',
'dni' => 'required|string|size:8|unique:estudiantes,dni'
```

## 🧪 Herramientas de Testing Recomendadas

### Backend (Laravel)

#### PHPUnit (Ya incluido)

```bash
# Ejecutar todos los tests
php artisan test

# Ejecutar con cobertura
php artisan test --coverage

# Test específico
php artisan test --filter=EstudianteTest
```

#### Pest PHP (Alternativa moderna)

```bash
composer require pestphp/pest --dev --with-all-dependencies
composer require pestphp/pest-plugin-laravel --dev

# Inicializar
./vendor/bin/pest --init

# Ejecutar tests
./vendor/bin/pest
```

Ejemplo de test con Pest:

```php
// tests/Feature/ConfiguracionTest.php
test('admin puede activar modo mantenimiento', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin, 'sanctum')
        ->postJson('/api/configuraciones', [
            'clave' => 'sistema_modo_mantenimiento',
            'valor' => 'true'
        ])
        ->assertStatus(200);

    expect(Configuracion::obtener('sistema_modo_mantenimiento'))->toBe('true');
});

test('usuario no admin no puede acceder en mantenimiento', function () {
    Configuracion::establecer('sistema_modo_mantenimiento', 'true');

    $user = User::factory()->create(['role' => 'padre']);

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/estudiantes')
        ->assertStatus(503);
});
```

#### Laravel Dusk (Tests E2E/Browser)

```bash
composer require --dev laravel/dusk
php artisan dusk:install

# Ejecutar tests de navegador
php artisan dusk
```

### Frontend (Next.js)

#### Vitest + React Testing Library (Recomendado)

```bash
npm install -D vitest @vitejs/plugin-react jsdom
npm install -D @testing-library/react @testing-library/jest-dom @testing-library/user-event
```

Configuración `vitest.config.ts`:

```typescript
import { defineConfig } from "vitest/config";
import react from "@vitejs/plugin-react";
import path from "path";

export default defineConfig({
    plugins: [react()],
    test: {
        environment: "jsdom",
        setupFiles: ["./src/test/setup.ts"],
    },
    resolve: {
        alias: {
            "@": path.resolve(__dirname, "./src"),
        },
    },
});
```

Ejemplo de test:

```typescript
// src/contexts/__tests__/ThemeContext.test.tsx
import { render, screen, fireEvent } from "@testing-library/react";
import { ThemeProvider, useTheme } from "../ThemeContext";

function TestComponent() {
    const { fontSize, setFontSize, screenReader } = useTheme();
    return (
        <div>
            <span data-testid="font-size">{fontSize}</span>
            <button onClick={() => setFontSize("large")}>Aumentar</button>
            <span data-testid="screen-reader">{screenReader.toString()}</span>
        </div>
    );
}

test("cambia tamaño de fuente correctamente", () => {
    render(
        <ThemeProvider>
            <TestComponent />
        </ThemeProvider>
    );

    expect(screen.getByTestId("font-size")).toHaveTextContent("normal");

    fireEvent.click(screen.getByText("Aumentar"));

    expect(screen.getByTestId("font-size")).toHaveTextContent("large");
});
```

#### Playwright (Tests E2E)

```bash
npm install -D @playwright/test
npx playwright install

# Ejecutar tests
npx playwright test
```

Ejemplo de test E2E:

```typescript
// tests/e2e/mantenimiento.spec.ts
import { test, expect } from "@playwright/test";

test("modo mantenimiento bloquea usuarios no admin", async ({ page }) => {
    // Activar mantenimiento como admin
    await page.goto("http://localhost:3000/login");
    await page.fill('input[name="email"]', "admin@colegio.pe");
    await page.fill('input[name="password"]', "password");
    await page.click('button[type="submit"]');

    await page.goto("http://localhost:3000/dashboard/configuraciones");
    await page.click('button:has-text("Activar Modo Mantenimiento")');

    // Intentar acceder como padre
    await page.context().clearCookies();
    await page.goto("http://localhost:3000/login");
    await page.fill('input[name="email"]', "padre@ejemplo.pe");
    await page.fill('input[name="password"]', "password");
    await page.click('button[type="submit"]');

    // Debe redirigir a página de mantenimiento
    await expect(page).toHaveURL("http://localhost:3000/maintenance");
    await expect(page.locator("h1")).toContainText("Mantenimiento");
});
```

### Herramientas de Calidad de Código

#### Backend

-   **PHP CS Fixer**: Formateo de código
-   **PHPStan/Larastan**: Análisis estático
-   **PHP Insights**: Métricas de calidad

```bash
composer require --dev friendsofphp/php-cs-fixer
composer require --dev phpstan/phpstan
composer require --dev nunomaduro/phpinsights
```

#### Frontend

-   **ESLint**: Ya configurado en el proyecto
-   **Prettier**: Formateo consistente
-   **TypeScript**: Chequeo de tipos (ya incluido)

```bash
npm install -D prettier eslint-config-prettier
npm install -D @typescript-eslint/eslint-plugin
```

### Cobertura de Tests

Para medir cobertura de código:

```bash
# Backend
php artisan test --coverage --min=80

# Frontend con Vitest
npx vitest run --coverage
```

### CI/CD Recomendado

GitHub Actions example (`.github/workflows/test.yml`):

```yaml
name: Tests

on: [push, pull_request]

jobs:
    backend:
        runs-on: ubuntu-latest
        steps:
            - uses: actions/checkout@v3
            - name: Setup PHP
              uses: shivammathur/setup-php@v2
              with:
                  php-version: "8.2"
            - run: composer install
            - run: php artisan test

    frontend:
        runs-on: ubuntu-latest
        steps:
            - uses: actions/checkout@v3
            - uses: actions/setup-node@v3
              with:
                  node-version: "18"
            - run: npm ci
            - run: npm run test
            - run: npm run build
```

## 📞 Contacto

Para soporte o consultas, contactar al equipo de desarrollo.

---

**Desarrollado con ❤️ para instituciones educativas**
