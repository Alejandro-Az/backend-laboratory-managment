# Plan de Implementación de Migraciones

## Objetivo

Este documento define el orden y la estrategia para implementar las migraciones del MVP de gestión de laboratorio en Laravel.

El objetivo es evitar improvisación, conflictos de foreign keys y cambios innecesarios durante el arranque del backend.

---

## Estado actual

El proyecto ya cuenta con:

- proyecto Laravel creado
- llave de aplicación generada
- migraciones base ejecutadas
- JWT instalado y configurado

También se definió que:

- la API será versionada bajo `/api/v1`
- el frontend será React
- JWT será el flujo principal de autenticación
- RBAC se manejará con Spatie Permission
- Filament queda completamente fuera del proyecto

---

## Paso previo obligatorio

Antes de crear las migraciones del dominio, instalar y publicar Spatie Permission.

### Comandos

```bash
composer require spatie/laravel-permission
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
php artisan migrate
```

### Razón

Esto deja listas las tablas de roles y permisos desde el inicio y evita reordenar migraciones del dominio después.

---

## Migraciones del dominio a crear

### Comandos recomendados

```bash
php artisan make:migration create_clients_table
php artisan make:migration create_projects_table
php artisan make:migration create_samples_table
php artisan make:migration create_sample_results_table
php artisan make:migration create_sample_events_table
php artisan make:migration create_user_preferences_table
```

---

## Orden lógico de implementación

### 1. `clients`
Debe existir antes que `projects`.

### 2. `projects`
Depende de `clients`.

### 3. `samples`
Depende de `projects`.

### 4. `sample_results`
Depende de `samples` y `users`.

### 5. `sample_events`
Depende de `samples` y `users`.

### 6. `user_preferences`
Depende de `users`.

---

## Migración 1: `clients`

### Objetivo
Guardar organizaciones cliente del sistema.

### Campos esperados
- id
- name
- contact_email
- contact_phone
- location
- created_by
- updated_by
- timestamps
- soft deletes

### Notas
- `name` puede ser unique para MVP si no se esperan nombres repetidos
- `created_by` y `updated_by` referencian `users.id` con `set null`

---

## Migración 2: `projects`

### Objetivo
Guardar proyectos asociados a clientes.

### Campos esperados
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

### Notas
- `client_id` referencia `clients.id`
- usar índices en `client_id`, `status`, `started_at`
- `status` se recomienda como string controlado por enum/constante PHP

---

## Migración 3: `samples`

### Objetivo
Guardar muestras del laboratorio.

### Campos esperados
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

### Reglas obligatorias
- no incluir `client_id`
- `urgent` pertenece a `priority`, no a `status`
- `code` debe ser único

### Índices recomendados
- `project_id`
- `status`
- `priority`
- `received_at`
- compuesto `project_id + status`
- compuesto `project_id + priority`

---

## Migración 4: `sample_results`

### Objetivo
Guardar resultados asociados a muestras.

### Campos esperados
- id
- sample_id
- analyst_id
- result_summary
- result_data
- timestamps

### Notas
- `sample_id` referencia `samples.id`
- `analyst_id` referencia `users.id`
- `result_data` puede ser JSON nullable

---

## Migración 5: `sample_events`

### Objetivo
Guardar la bitácora de eventos de las muestras.

### Campos esperados
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

### Notas
- usar `created_at` sin `updated_at` si se quiere tratar como evento inmutable
- `metadata` puede ser JSON nullable
- esta tabla alimenta el bloque de actividad reciente

### Eventos iniciales recomendados
- created
- updated
- analysis_started
- priority_changed
- status_changed
- result_added
- completed
- deleted
- restored

---

## Migración 6: `user_preferences`

### Objetivo
Guardar preferencias de notificación por usuario.

### Campos esperados
- id
- user_id
- notify_urgent_sample_alerts
- notify_sample_completion
- notify_daily_activity_digest
- notify_project_updates
- timestamps

### Notas
- `user_id` debe ser unique
- relación 1:1 con `users`

---

## Consideraciones de implementación

### 1. Strings controlados en lugar de enum rígido de base de datos
Se recomienda usar columnas string para `status`, `priority` y `event_type`.

### Razones
- más fácil evolucionar estados
- menos fricción en MySQL
- mejor mantenimiento del MVP

### 2. Soft deletes
Usar soft deletes en:
- clients
- projects
- samples

### 3. Foreign keys de auditoría
Campos como `created_by` y `updated_by` deben usar:
- `nullable()`
- foreign key a `users.id`
- `nullOnDelete()`

### 4. Eventos inmutables
`sample_events` debe tratarse como bitácora, no como tabla editable.

---

## Estrategia recomendada de trabajo

### Fase 1
Crear todas las migraciones del dominio.

### Fase 2
Ejecutar migraciones limpias:

```bash
php artisan migrate:fresh
```

### Fase 3
Crear modelos y relaciones Eloquent.

### Fase 4
Crear seeders base:
- admin user
- analyst user
- clients demo
- projects demo
- samples demo

### Fase 5
Construir endpoints y filtros sobre el modelo real.

---

## Validaciones conceptuales antes de migrar

Antes de escribir código final, confirmar estas decisiones ya cerradas:

- Client -> Project -> Sample es la jerarquía oficial
- `samples` no lleva `client_id`
- `urgent` es priority
- dashboard usa métricas derivadas
- `sample_events` alimenta activity feed
- soft delete aplica en entidades principales

---

## Siguiente paso recomendado

Después de cerrar este plan, el siguiente movimiento correcto es escribir las migraciones Laravel reales una por una, listas para pegar en el proyecto.
