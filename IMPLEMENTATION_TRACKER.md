# IMPLEMENTATION_TRACKER.md

## Propósito

Este archivo es la fuente de verdad operativa del proyecto.

Debe usarse junto con:

- `AGENTS.md`
- `CONTEXTO.md`
- `docs/api.md`
- `docs/database.md`
- `docs/migration-plan.md`

Su objetivo es evitar que el trabajo asistido por IA se desvíe del alcance, reabra decisiones ya cerradas o introduzca inconsistencias entre backend, tests y documentación.

---

## Estado actual del proyecto

### Resumen ejecutivo
El backend del MVP está muy avanzado y el núcleo ya está implementado.

### Estado por bloques
- [x] T1 Base API
- [x] T2 RBAC base
- [x] T3 Base de datos de dominio
- [x] T4 Modelos y relaciones Eloquent
- [x] T5 Seeders tempranos
- [x] T6 Auth v1
- [x] T7 Clients API
- [x] T8 Projects API
- [x] T9 Samples API + bitácora
- [x] T10 Dashboard API
- [x] T11 Settings mínimos
- [x] T12 Cierre final

### Estado general
- Backend núcleo: cerrado
- Dashboard: cerrado
- Samples: cerrado
- Settings: cerrado
- Contrato API: cerrado
- Tests backend: completos (70 tests, 319 assertions — verde)
- Frontend: pendiente de integración
- Documentación final: cerrada

---

## Reglas cerradas del proyecto

Estas decisiones ya están tomadas y no deben reabrirse sin una razón técnica fuerte y una actualización explícita de documentación.

### Arquitectura
- Laravel API-first
- Frontend desacoplado en React
- JWT como mecanismo principal de auth
- Spatie Permission para RBAC
- API versionada bajo `/api/v1`

### Contrato API
Todas las respuestas deben usar envelope uniforme.

#### Éxito
```json
{
  "ok": true,
  "data": {},
  "message": "..."
}
```

#### Error
```json
{
  "ok": false,
  "error": {
    "code": "...",
    "message": "...",
    "details": {}
  }
}
```

### Dominio

- `Client -> Project -> Sample`
- `samples` no tiene `client_id`
- `urgent` es `priority`, no `status`

**`status` válidos:**
- `pending`
- `in_progress`
- `completed`
- `cancelled`

**`priority` válidos:**
- `standard`
- `urgent`

### Dashboard
- No se persisten métricas derivadas
- `pending_analysis` = `pending` + `in_progress`
- `recent-activity` sale de `sample_events`
- `recent-samples` usa orden fijo `created_at desc`

Endpoints cortos de dashboard usan:
- `data.items`
- `data.meta.count`
- No usar `total`, `limit`, `has_more` en dashboard

### Samples
- `sample_results` es 1:N
- En listados se expone solo resumen del último resultado
- En detalle se expone historial completo
- `sample_events` es obligatorio y no opcional

**Eventos cerrados:**
- `created`
- `updated`
- `analysis_started`
- `priority_changed`
- `status_changed`
- `result_added`
- `completed`
- `deleted`
- `restored`

**Soft deletes:**
- `index` excluye soft-deleted
- `show` excluye soft-deleted
- `restore` usa `withTrashed()` explícitamente
- `recent-activity` excluye eventos ligados a samples soft-deleted vía `whereHas('sample')`
- Este comportamiento está documentado y no debe cambiarse silenciosamente

### Alcance congelado

No agregar en este MVP:
- Filament
- 2FA real
- login history avanzada
- import/export complejo
- `auth_sessions`
- `login_attempts`
- multitenancy
- microservicios
- features extra fuera del reto

### Convenciones obligatorias

**Idioma:**
- Código en inglés
- Documentación oficial en español

**Estilo técnico:**
- Controllers delgados
- Form Requests para validación
- Resources para serialización
- Policies/Permisos consistentes
- Sin queries dentro de Resources
- Eager loading donde aplique
- Tests reales, no placeholders

**Higiene:**
- No dejar archivos temporales
- No dejar código muerto
- No dejar tests basura
- No dejar estructuras duplicadas
- No reabrir decisiones cerradas por "comodidad"

---

## TRACKER POR TICKETS

### T1 — Base API

**Estado:** [x] Cerrado

**Alcance:**
- `/api/v1`
- guard JWT
- envelope uniforme
- manejo centralizado de errores
- rutas API protegidas

**Definition of Done:**
- [ ] Existe `/api/v1`
- [ ] JWT funciona en rutas protegidas
- [ ] Errores de auth/validación/autorización responden con envelope uniforme
- [ ] No hay respuestas arbitrarias fuera del contrato

---

### T2 — RBAC base

**Estado:** [x] Cerrado

**Alcance:**
- Integración de Spatie
- Roles base `admin` y `analyst`

**Definition of Done:**
- [ ] Spatie instalado y migrado
- [ ] User integra RBAC
- [ ] Roles base sembrados
- [ ] Autorización usable desde backend

---

### T3 — Base de datos de dominio

**Estado:** [x] Cerrado

**Alcance:**
- `clients`
- `projects`
- `samples`
- `sample_results`
- `sample_events`
- `user_preferences`

**Definition of Done:**
- [ ] Migraciones limpias
- [ ] FKs e índices correctos
- [ ] Soft deletes donde aplica
- [ ] Auditoría mínima con `created_by` / `updated_by`
- [ ] Dominio alineado con reglas cerradas

---

### T4 — Modelos y relaciones Eloquent

**Estado:** [x] Cerrado

**Alcance:**
- Modelos
- Relaciones
- Casts
- Scopes

**Definition of Done:**
- [ ] Relaciones funcionando
- [ ] `latestResult()` en `Sample`
- [ ] Scopes útiles para filtros
- [ ] Enums / constantes alineados con dominio

---

### T5 — Seeders tempranos

**Estado:** [x] Cerrado

**Alcance:**
- Roles
- Usuarios base
- Datos demo mínimos

**Definition of Done:**
- [ ] `migrate:fresh --seed` deja entorno usable
- [ ] Admin y analyst disponibles
- [ ] Datos demo útiles para pruebas

---

### T6 — Auth v1

**Estado:** [x] Cerrado

**Alcance:**
- `login`
- `me`
- `logout`
- `refresh`

**Definition of Done:**
- [ ] Login devuelve token usable
- [ ] `/me` devuelve usuario autenticado
- [ ] Logout y refresh resueltos
- [ ] Tests de auth en verde

---

### T7 — Clients API

**Estado:** [x] Cerrado

**Alcance:**
- CRUD básico
- Policies
- Requests
- Resources

**Definition of Done:**
- [ ] CRUD funcional
- [ ] Permisos aplicados
- [ ] Envelope uniforme
- [ ] Tests en verde

---

### T8 — Projects API

**Estado:** [x] Cerrado

**Alcance:**
- CRUD básico
- relación con client
- filtros básicos

**Definition of Done:**
- [ ] CRUD funcional
- [ ] Relación con client resuelta
- [ ] Envelope uniforme
- [ ] Tests en verde

---

### T9 — Samples API + bitácora

**Estado:** [x] Cerrado

**Alcance:**
- CRUD
- filtros
- cambios de status y priority
- resultados
- bitácora `sample_events`

**Definition of Done:**
- [ ] CRUD funcional
- [ ] Filtros por status, priority, client, project y fechas
- [ ] `SampleListResource` y `SampleDetailResource`
- [ ] `sample_events` se registra obligatoriamente
- [ ] Soft delete y restore correctos
- [ ] Envelope uniforme
- [ ] Tests rigurosos en verde

---

### T10 — Dashboard API

**Estado:** [x] Cerrado

**Alcance:**
- `GET /api/v1/dashboard/metrics`
- `GET /api/v1/dashboard/recent-samples`
- `GET /api/v1/dashboard/recent-activity`

**Definition of Done:**
- [ ] `dashboard.view` sembrado y aplicado
- [ ] `metrics` calcula:
  - `total_samples`
  - `urgent_samples`
  - `pending_analysis`
  - `completion_rate`
- [ ] `recent-samples` usa datos reales
- [ ] `recent-samples` excluye soft-deleted
- [ ] `recent-samples` orden fijo `created_at desc`
- [ ] `recent-activity` sale de `sample_events`
- [ ] `recent-activity` excluye eventos de samples soft-deleted
- [ ] Shape cerrado con `data.items` + `data.meta.count`
- [ ] Tests de dashboard en verde
- [ ] `docs/api.md` actualizado

---

### T11 — Settings mínimos

**Estado:** [x] Cerrado

**Alcance cerrado:**

Implementar solo estos endpoints:
- `GET /api/v1/settings/profile`
- `PATCH /api/v1/settings/profile`
- `GET /api/v1/settings/preferences`
- `PATCH /api/v1/settings/preferences`
- `POST /api/v1/settings/change-password`

**Reglas cerradas:**
- `/me` = identidad/autenticación actual
- `/settings/profile` = perfil editable
- `user_preferences` debe resolverse con `firstOrCreate` o equivalente al leer
- `user_preferences` debe actualizarse con `updateOrCreate` o equivalente robusto
- `change-password` debe validar contraseña actual de forma real
- cambiar contraseña no invalida automáticamente el JWT actual en este MVP
- no agregar 2FA
- no agregar login history
- no agregar import/export

**Archivos esperados:**
- `app/Http/Controllers/Api/V1/SettingsController.php`
- `app/Http/Requests/Api/V1/UpdateProfileRequest.php`
- `app/Http/Requests/Api/V1/UpdateUserPreferencesRequest.php`
- `app/Http/Requests/Api/V1/ChangePasswordRequest.php`
- `app/Http/Resources/SettingsProfileResource.php`
- `app/Http/Resources/UserPreferencesResource.php`
- `tests/Feature/SettingsApiTest.php`

**Definition of Done:**
- [ ] Existen las 5 rutas bajo `/api/v1/settings/...`
- [ ] Todas protegidas con `auth:api`
- [ ] `GET /settings/profile` devuelve: `id`, `name`, `email`, `roles`
- [ ] `PATCH /settings/profile` actualiza solo: `name`, `email`
- [ ] `email` valida unicidad ignorando usuario actual
- [ ] `GET /settings/preferences` funciona incluso si no existe fila previa
- [ ] `PATCH /settings/preferences` actualiza solo los 4 boolean esperados
- [ ] `POST /settings/change-password` valida: `current_password`, `password`, `password_confirmation`
- [ ] nueva contraseña se persiste hasheada
- [ ] token actual sigue funcionando tras cambio de contraseña
- [ ] envelope uniforme en los 5 endpoints
- [ ] tests de settings en verde
- [ ] documentación de Settings actualizada

---

### T12 — Cierre final

**Estado:** [x] Cerrado

**Alcance cerrado:**
- limpieza de warnings de PHPUnit por `@test` deprecated
- actualización final de documentación
- suite completa
- checklist final de entrega

#### T12.1 Limpieza PHPUnit

**Definition of Done:**
- [ ] tests migrados a: nombres `test_*` o atributos `#[Test]`
- [ ] sin warnings por metadata de doc-comments deprecada
- [ ] tests siguen en verde

#### T12.2 Documentación final

**Archivos mínimos a revisar:**
- `docs/api.md`
- `docs/frontend-integration.md`
- `README.md` o checklist final si aplica

**Definition of Done:**
- [ ] endpoints de Settings documentados
- [ ] endpoints finales de Dashboard documentados
- [ ] contrato uniforme visible
- [ ] permisos relevantes documentados
- [ ] se documenta que `change-password` no invalida el JWT actual en este MVP
- [ ] integración frontend actualizada si aplica

#### T12.3 Verificación final

**Definition of Done:**
- [ ] `php artisan test` en verde
- [ ] salida real de tests registrada
- [ ] sin regresiones en auth
- [ ] sin regresiones en clients
- [ ] sin regresiones en projects
- [ ] sin regresiones en samples
- [ ] sin regresiones en dashboard
- [ ] settings en verde
- [ ] checklist final completado

---

## Checklist final de entrega

Este checklist debe quedar marcado solo al terminar T12.

**Backend funcional:**
- [x] Auth
- [x] Clients
- [x] Projects
- [x] Samples
- [x] Dashboard
- [x] Settings

**Contrato y seguridad:**
- [x] Envelope uniforme en todos los endpoints
- [x] JWT consistente
- [x] RBAC consistente
- [x] Policies/permisos coherentes

**Calidad:**
- [x] Tests completos en verde (70 tests, 319 assertions)
- [x] Sin warnings relevantes de PHPUnit
- [x] Sin cambios de alcance MVP
- [x] Sin deuda técnica obvia introducida al cierre

**Documentación:**
- [x] `docs/api.md` al día
- [x] `docs/frontend-integration.md` al día
- [x] checklist final documentado
- [x] decisiones cerradas reflejadas

---

## Instrucción para asistentes de IA

Antes de implementar cualquier cambio, leer:
- `AGENTS.md`
- `CONTEXTO.md`
- `IMPLEMENTATION_TRACKER.md`

Después:
1. identificar el siguiente bloque pendiente
2. respetar todas las decisiones cerradas
3. implementar solo ese bloque
4. no reabrir alcance
5. entregar:
   - archivos creados
   - archivos modificados
   - código completo
   - tests
   - salida real de pruebas

No proponer features extra.  
No mover decisiones ya congeladas.  
No romper contrato API existente.
