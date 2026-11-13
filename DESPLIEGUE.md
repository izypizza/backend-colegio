# Despliegue en producción

Guía para publicar el sistema. El orden importa: primero el backend, porque su
dominio es lo que el frontend necesita conocer al compilar.

## 1. Backend (Laravel)

### Variables obligatorias

| Variable | Valor |
|---|---|
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_KEY` | generar con `php artisan key:generate --show` |
| `APP_URL` | el dominio público del backend |
| `FRONTEND_URL` | el dominio público del frontend (se usa en CORS) |
| `APP_TIMEZONE` | `America/Lima` |
| `DB_CONNECTION` | `mysql` |
| `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | datos del motor gestionado |
| `SANCTUM_EXPIRATION` | `480` (una jornada escolar) |

`FRONTEND_URL` debe apuntar al dominio exacto del frontend, sin barra final.
Si no coincide, el navegador bloqueará las peticiones por CORS.

### Arranque

El archivo `Procfile` define el comando de arranque:

```
web: php artisan serve --host=0.0.0.0 --port=$PORT
```

`artisan serve` es suficiente para la carga de un colegio. Si se requiere mayor
rendimiento, cambiar el Procfile por Apache o Nginx con PHP-FPM apuntando a
`public/`.

### Primer despliegue

Ejecutar una sola vez desde la consola del servicio:

```
php artisan migrate --force
php artisan db:seed --force
```

En despliegues posteriores solo hace falta `php artisan migrate --force`.

Si se actualiza el código de una base que ya tiene datos, **no** ejecutar
`db:seed`, porque duplicaría información.

### Verificación

```
curl https://<dominio-backend>/api/ping
```

Debe responder `{"ok":true,"database":true,...}`. Si devuelve error 500, el
problema suele ser `APP_KEY` vacío o credenciales de base incorrectas.

## 2. Frontend (Next.js)

### Variable obligatoria

| Variable | Valor |
|---|---|
| `NEXT_PUBLIC_API_URL` | `https://<dominio-backend>/api` |

`NEXT_PUBLIC_*` se incrusta en el bundle **en tiempo de compilación**, no de
ejecución. Si se cambia después del build, hay que volver a compilar y
redeployar.

### Comandos

```
npm ci
npm run build
npm start
```

## 3. Orden recomendado

1. Desplegar el backend y anotar su dominio
2. Verificar `/api/ping`
3. Configurar `FRONTEND_URL` con el dominio del frontend, aunque todavía no exista
4. Desplegar el frontend con `NEXT_PUBLIC_API_URL` apuntando al backend
5. Abrir el frontend y comprobar el inicio de sesión
6. Si el navegador reporta CORS, revisar que `FRONTEND_URL` sea idéntico al dominio
   del frontend, incluido el esquema `https`

## 4. Comprobaciones después de publicar

- Iniciar sesión con cada rol: admin, auxiliar, docente, padre, estudiante, bibliotecario
- Abrir Matrículas: debe listar el año escolar activo
- Llamar lista como docente y confirmar que se guarda
- Registrar una calificación y verificar que el estudiante la ve
