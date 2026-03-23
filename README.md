# Laboratory Management API

REST API para gestión de laboratorio. Administra clientes, proyectos y muestras con control de acceso basado en roles, trazabilidad completa y métricas operativas en tiempo real.

## Stack

- **PHP 8.2** / **Laravel 12**
- **MySQL** (producción) / **SQLite** (desarrollo local)
- **JWT** — `tymon/jwt-auth`
- **RBAC** — `spatie/laravel-permission`
- **Docker** + **Nginx** + **PHP-FPM**

---

## Instalación local

```bash
git clone <url-del-repo>
cd laboratory-managment

composer install
cp .env.example .env
php artisan key:generate
php artisan jwt:secret
php artisan migrate:fresh --seed
php artisan serve
```

La API queda disponible en `http://localhost:8000/api/v1`.

**Credenciales de desarrollo:**

| Email | Password | Rol |
|-------|----------|-----|
| admin@laboratory.local | password | admin |
| analyst@laboratory.local | password | analyst |

---

## Instalación con Docker

```bash
cp .env.example .env
# Configurar DB_CONNECTION=mysql y credenciales en .env

docker-compose up -d --build
docker-compose exec app composer install
docker-compose exec app php artisan key:generate
docker-compose exec app php artisan jwt:secret
docker-compose exec app php artisan migrate:fresh --seed
```

La API queda disponible en `http://localhost:8000`.

---

## Tests

```bash
php artisan test
```

70 tests — 319 assertions — todos en verde.

---

## Diagrama de base de datos

Ver archivo `docs/database.md` para el esquema completo con relaciones.

Estructura general:

```
users
clients         (soft delete, audit: created_by / updated_by)
  └── projects  (soft delete, audit)
        └── samples  (soft delete, audit, rejection_count)
              ├── sample_results
              └── sample_events  (audit trail inmutable)
user_preferences
```

**Jerarquía de dominio:** Cliente → Proyecto → Muestra

**Regla clave:** `client_id` nunca se persiste en `samples`. Se obtiene siempre a través del proyecto.

**`rejection_count`:** contador que se incrementa automáticamente cada vez que una muestra regresa de `in_progress` a `pending`, trazando el ciclo de rechazos/correcciones.

---

## Endpoints

### Auth

| Método | Ruta | Descripción |
|--------|------|-------------|
| POST | `/api/v1/auth/login` | Login — retorna JWT |
| GET | `/api/v1/me` | Usuario autenticado |
| POST | `/api/v1/auth/refresh` | Renovar token |
| POST | `/api/v1/auth/logout` | Cerrar sesión |

### Clientes

| Método | Ruta | Rol mínimo |
|--------|------|-----------|
| GET | `/api/v1/clients` | analyst |
| POST | `/api/v1/clients` | admin |
| GET | `/api/v1/clients/{id}` | analyst |
| PUT | `/api/v1/clients/{id}` | admin |
| DELETE | `/api/v1/clients/{id}` | admin |

Filtros: `?search=texto&per_page=15`

### Proyectos

| Método | Ruta | Rol mínimo |
|--------|------|-----------|
| GET | `/api/v1/projects` | analyst |
| POST | `/api/v1/projects` | admin |
| GET | `/api/v1/projects/{id}` | analyst |
| PUT | `/api/v1/projects/{id}` | admin |
| DELETE | `/api/v1/projects/{id}` | admin |

Filtros: `?status=active&client_id=1&per_page=15`

### Muestras

| Método | Ruta | Rol mínimo |
|--------|------|-----------|
| GET | `/api/v1/samples` | analyst |
| POST | `/api/v1/samples` | admin |
| GET | `/api/v1/samples/{id}` | analyst |
| PUT | `/api/v1/samples/{id}` | admin |
| DELETE | `/api/v1/samples/{id}` | admin |
| POST | `/api/v1/samples/{id}/restore` | admin |
| PATCH | `/api/v1/samples/{id}/status` | analyst |
| PATCH | `/api/v1/samples/{id}/priority` | admin |
| POST | `/api/v1/samples/{id}/results` | analyst |
| GET | `/api/v1/samples/{id}/events` | analyst |

Filtros: `?status=pending&priority=urgent&client_id=1&project_id=1&received_from=2026-01-01&received_to=2026-03-31&per_page=15`

### Dashboard

| Método | Ruta | Descripción |
|--------|------|-------------|
| GET | `/api/v1/dashboard/metrics` | Métricas en tiempo real |
| GET | `/api/v1/dashboard/recent-samples` | Últimas muestras |
| GET | `/api/v1/dashboard/recent-activity` | Actividad reciente |

Métricas: `total_samples`, `urgent_samples`, `pending_analysis`, `completion_rate`, `rejection_rate`

### Settings

| Método | Ruta | Descripción |
|--------|------|-------------|
| GET | `/api/v1/settings/profile` | Perfil del usuario |
| PATCH | `/api/v1/settings/profile` | Actualizar nombre y email |
| GET | `/api/v1/settings/preferences` | Preferencias de notificación |
| PATCH | `/api/v1/settings/preferences` | Actualizar preferencias |
| POST | `/api/v1/settings/change-password` | Cambiar contraseña |

---

## Envelope de respuesta

```json
{ "ok": true,  "data": {},    "message": "..." }
{ "ok": false, "error": { "code": "VALIDATION_ERROR", "message": "...", "details": {} } }
```

---

## Roles y permisos

| Acción | admin | analyst |
|--------|:-----:|:-------:|
| CRUD clientes y proyectos | ✓ | — |
| Crear / eliminar / restaurar muestras | ✓ | — |
| Cambiar status de muestra | ✓ | ✓ |
| Cambiar priority | ✓ | — |
| Agregar resultado de análisis | ✓ | ✓ |
| Ver dashboard, eventos | ✓ | ✓ |
| Gestionar perfil propio | ✓ | ✓ |
