# CONTEXTO.md

## Descripción general del proyecto

Este proyecto implementa un MVP de panel administrativo para gestión de laboratorio.

Se trata de una aplicación orientada a operaciones internas, no de un sitio público de marketing.

El sistema debe permitir gestionar:

- clientes
- proyectos
- muestras
- métricas operativas
- actividad reciente
- configuración básica de usuario

El objetivo es construir un MVP profesional, funcional y defendible técnicamente, con una arquitectura limpia pero proporcional al alcance.

---

## Objetivo técnico

Construir una aplicación con:

- backend API-first
- frontend administrativo desacoplado
- autenticación JWT
- RBAC
- base de datos relacional limpia
- documentación clara
- mantenimiento razonable
- entrega rápida sin sobreingeniería

---

## Stack tecnológico

### Backend
- Laravel 12
- PHP 8.2+
- MySQL
- `tymon/jwt-auth`
- `spatie/laravel-permission`

### Frontend
- React
- Vite
- TypeScript recomendado
- Tailwind recomendado

### Arquitectura
- API versionada bajo `/api/v1`
- panel administrativo React consumiendo la API
- JWT Bearer Token como flujo principal de autenticación

---

## Estado actual del proyecto

Ya se ejecutó lo siguiente:

### Creación del proyecto
```bash
composer create-project laravel/laravel laboratory-managment
```

### Generación de llave
```bash
php artisan key:generate
```

### Migraciones base
```bash
php artisan migrate
```

### JWT
```bash
composer require tymon/jwt-auth
php artisan vendor:publish --provider="Tymon\JWTAuth\Providers\LaravelServiceProvider"
php artisan jwt:secret
```

Esto significa que el proyecto ya tiene la base Laravel levantada y JWT instalado.

---

## Decisiones cerradas del proyecto

Estas decisiones ya están tomadas y no deben replantearse sin una razón fuerte.

### 1. No usar Filament
Filament queda completamente fuera del proyecto.

### 2. Backend API-first
Laravel se usará como API.
La lógica de negocio vive en backend.

### 3. Frontend en React
La interfaz administrativa se construirá en React y consumirá la API.

### 4. Auth principal con JWT
La API usará JWT como mecanismo principal de autenticación.

### 5. RBAC con Spatie
Se usará `spatie/laravel-permission` para roles y permisos.

### 6. API versionada
Las rutas deben organizarse bajo `/api/v1`.

### 7. Envelope estándar
Todos los endpoints deben responder con envelope consistente.

### 8. Código en inglés, documentación en español
Esta regla es obligatoria.

### 9. El proyecto debe mantenerse limpio
Especialmente por el uso de IA y vibe coding.

---

## Contrato de API

### Respuesta exitosa
```json
{
  "ok": true,
  "data": {},
  "message": "Success"
}
```

### Respuesta de error
```json
{
  "ok": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "The given data was invalid.",
    "details": {}
  }
}
```

### Reglas
- el envelope es obligatorio
- no devolver estructuras arbitrarias
- mantener nombres consistentes
- documentar cualquier cambio de contrato

---

## Modelo funcional observado en UI

Las pantallas analizadas hasta ahora son:

- Dashboard
- Sample Management
- Projects
- Clients
- Settings

Estas pantallas sirven como guía visual y funcional del MVP.

---

## Dashboard

El dashboard muestra:

### Métricas
- Total Samples
- Urgent Samples
- Pending Analysis
- Completion Rate

### Recent Activity
Actividad reciente de usuarios sobre muestras.

### Recent Samples
Tabla corta con muestras recientes.

### Regla importante
Las métricas del dashboard son derivadas y deben calcularse con queries.
No deben persistirse como columnas ni como tabla de estadísticas.

---

## Sample Management

La pantalla de muestras muestra:

- filtros por status
- filtros por rango de fechas
- exportaciones visuales a Excel y PDF
- tabla de muestras
- acciones: ver, editar, eliminar
- botón “New Sample”

### Campos visibles en tabla
- ID
- Code
- Client
- Project
- Received Date
- Status
- Actions

### Modal de alta de muestra
- Client
- Project
- Sample Code
- Priority
- Received Date

### Regla de dominio crítica
Aunque la UI muestre “Urgent” como badge de tabla, eso no debe modelarse como `status`.

`urgent` pertenece a `priority`, no a `status`.

#### Estados esperados de `status`
- `pending`
- `in_progress`
- `completed`
- `cancelled`

#### Valores esperados de `priority`
- `standard`
- `urgent`

### Relación real
Una muestra pertenece a un proyecto.
El cliente de una muestra se obtiene por medio del proyecto.

No duplicar `client_id` en `samples`.

---

## Projects

La pantalla de proyectos muestra tarjetas con:

- nombre
- cliente
- fecha de inicio
- status del proyecto
- total de muestras

### Regla
`total_samples` de proyecto es una métrica derivada, no columna persistida.

### Estados iniciales recomendados para proyecto
- `active`
- `completed`
- `on_hold`
- `archived`

---

## Clients

La pantalla de clientes muestra:

- nombre
- datos de contacto
- ubicación
- active projects
- total samples

### Regla
- `active_projects` es métrica derivada
- `total_samples` es métrica derivada

La ubicación puede mantenerse como string simple para MVP.

---

## Settings

La pantalla de configuración muestra:

### User Profile
- full name
- email
- role

### Notifications
Preferencias de notificación:
- urgent sample alerts
- sample completion notifications
- daily activity digest
- project updates

### Security
Acciones visibles:
- change password
- two-factor authentication
- view login history

### Data Management
Acciones visibles:
- export all data
- import data
- clear all samples

### Regla
No todo lo que aparece en Settings requiere una tabla nueva.

#### Sí justifica estructura persistida
- preferencias de usuario (`user_preferences`)

#### No obliga todavía a estructura compleja
- 2FA real
- login history detallado
- jobs avanzados de export/import

Eso puede dejarse para una fase posterior si no es imprescindible.

---

## Modelo de datos base decidido

Las tablas núcleo del MVP son:

- `users`
- tablas de Spatie Permission
- `clients`
- `projects`
- `samples`
- `sample_results`
- `sample_events`
- `user_preferences`

### Relaciones
- un client tiene muchos projects
- un project tiene muchas samples
- una sample pertenece a un project
- una sample puede tener results
- una sample puede tener muchos events
- un user puede crear o actualizar entidades
- un user tiene una preferencia de usuario

---

## Reglas de dominio obligatorias

### Jerarquía
- Client -> Project -> Sample

### No duplicación
- no duplicar `client_id` dentro de `samples`

### Prioridad
- `urgent` es `priority`

### Dashboard
- métricas derivadas por query

### Actividad reciente
- proviene de `sample_events`

### Auditoría mínima
Entidades principales deben registrar:
- `created_by`
- `updated_by`
- timestamps
- soft deletes cuando aplique

---

## Base de datos prevista

### `clients`
Campos base:
- id
- name
- contact_email
- contact_phone
- location
- created_by
- updated_by
- timestamps
- soft deletes

### `projects`
Campos base:
- id
- client_id
- name
- status
- started_at
- ended_at
- description
- created_by
- updated_by
- timestamps
- soft deletes

### `samples`
Campos base:
- id
- project_id
- code
- status
- priority
- received_at
- analysis_started_at
- completed_at
- notes
- created_by
- updated_by
- timestamps
- soft deletes

### `sample_results`
Campos base:
- id
- sample_id
- analyst_id
- result_summary
- result_data
- timestamps

### `sample_events`
Campos base:
- id
- sample_id
- user_id
- event_type
- description
- old_status
- new_status
- old_priority
- new_priority
- metadata
- created_at

### `user_preferences`
Campos base:
- id
- user_id
- notify_urgent_sample_alerts
- notify_sample_completion
- notify_daily_activity_digest
- notify_project_updates
- timestamps

---

## Roles iniciales

Roles base esperados:

- `admin`
- `analyst`

### Admin
Puede administrar el sistema y sus entidades principales.

### Analyst
Puede consultar muestras y registrar trabajo operativo según reglas definidas.

Los permisos específicos pueden refinarse, pero el MVP parte de esos dos roles.

---

## Reglas de implementación

### Backend
- usar Form Requests
- usar Policies
- usar Spatie Permission
- controllers delgados
- paginación server-side
- filtros explícitos
- responses consistentes

### Frontend
- panel administrativo
- rutas protegidas
- consumo de API con JWT
- filtros y tablas conectados a backend
- estados loading / empty / error
- formularios mantenibles

---

## Testing mínimo esperado

### Backend
- login
- acceso protegido
- creación de sample
- listado de samples con filtros
- permisos por rol
- validaciones principales
- actualización de status y priority

### Frontend
- login
- rutas protegidas
- dashboard
- samples table
- filtros
- submit de formularios
- estados de error y carga

---

## Documentación esperada

La documentación oficial del proyecto debe estar en español.

Archivos recomendados:
- `README.md`
- `docs/architecture.md`
- `docs/database.md`
- `docs/api.md`
- `docs/frontend-integration.md`
- `docs/implementation-plan.md`

---

## Higiene del proyecto

Reglas obligatorias:

- no dejar scripts temporales
- no dejar logs manuales
- no dejar scaffolds inútiles
- no dejar imports muertos
- no dejar código comentado innecesario
- no dejar archivos generados sin uso
- no versionar archivos de entorno reales
- mantener el repo reproducible y entendible

Especialmente importante por el uso de IA.

---

## Enfoque de trabajo

Este proyecto se desarrollará con apoyo de IA, pero no se aceptará código “copiar y pegar sin criterio”.

Todo cambio debe:
- entenderse
- justificarse
- revisarse
- integrarse con la arquitectura real
- evitar deuda técnica innecesaria

La prioridad es construir bien el núcleo del MVP.

