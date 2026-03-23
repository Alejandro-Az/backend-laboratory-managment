# Migraciones del dominio

Orden sugerido:

1. 2026_03_17_000001_create_clients_table.php
2. 2026_03_17_000002_create_projects_table.php
3. 2026_03_17_000003_create_samples_table.php
4. 2026_03_17_000004_create_sample_results_table.php
5. 2026_03_17_000005_create_sample_events_table.php
6. 2026_03_17_000006_create_user_preferences_table.php

## Reglas de dominio congeladas

- `samples` no tiene `client_id`.
- `urgent` es `priority`, no `status`.
- Las métricas del dashboard son derivadas.
- `sample_events` alimenta Recent Activity.
- `clients`, `projects` y `samples` usan soft deletes.
- `created_by` y `updated_by` viven en entidades principales.

## Estados esperados

### project status
- active
- completed
- on_hold
- archived

### sample status
- pending
- in_progress
- completed
- cancelled

### sample priority
- standard
- urgent
