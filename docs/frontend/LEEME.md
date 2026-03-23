# Guía de inicio para el agente frontend

Este directorio contiene toda la documentación que necesitas para implementar el frontend del panel administrativo de gestión de laboratorio.

**Lee este archivo primero. Luego lee los demás en el orden indicado.**

---

## Qué es este proyecto

Panel administrativo para gestión de laboratorio. Permite gestionar clientes, proyectos y muestras. Tiene dos roles: administrador y analista. No es un sitio público — es una herramienta interna de operaciones.

---

## Stack decidido (no negociable)

- **React** con **TypeScript**
- **Vite** como bundler
- **Tailwind CSS** para estilos
- Cliente HTTP centralizado (axios o fetch wrapper)
- Manejo de JWT manual (no librerías de auth externas)

No hay decisiones pendientes sobre stack. Todo está cerrado.

---

## Archivos que debes leer (en orden)

1. **`api-referencia.md`** — shapes exactos de cada endpoint. Es la fuente de verdad para todo lo que devuelve el backend. Léelo completo antes de escribir un solo fetch.

2. **`autenticacion.md`** — cómo manejar JWT, dónde almacenar el token, cómo proteger rutas, cómo renovar y cerrar sesión.

3. **`pantallas.md`** — qué debe mostrar cada pantalla, qué endpoints llama, qué restricciones por rol aplican.

---

## Reglas que el frontend debe respetar

### No duplicar lógica de negocio
El backend es la fuente de verdad. El frontend **no valida reglas de negocio** — las muestra. Ejemplo: no calcular `pending_analysis` en el cliente; ese dato viene del endpoint `/dashboard/metrics`.

### Leer `ok` siempre
Todas las respuestas tienen `{ ok: true/false, data/error, message }`. Siempre leer `ok` antes de usar `data`.

### Los errores de validación están en `error.details`
Cuando el backend responde 422 con `error.code === 'VALIDATION_ERROR'`, los errores por campo están en `error.details`. Ejemplo: `error.details.email[0]` → mensaje de error para el campo `email`.

### Jerarquía de dominio
```
Cliente → Proyecto → Muestra
```
Una muestra nunca tiene `client_id` directo. El cliente se obtiene a través del proyecto. El frontend debe respetar esta jerarquía al construir formularios (primero seleccionar cliente para filtrar proyectos, luego seleccionar proyecto para crear muestra).

### `urgent` es priority, no status
En la tabla de muestras existe una columna de `priority`. Los valores son `standard` y `urgent`. El valor `urgent` **no es un estado** — es una prioridad. No confundirlos en badges ni filtros.

---

## Credenciales de desarrollo

Tras ejecutar `php artisan migrate:fresh --seed` en el backend:

| email | password | rol |
|-------|----------|-----|
| `admin@laboratory.local` | `password` | admin |
| `analyst@laboratory.local` | `password` | analyst |

---

## URL base de la API

```
http://localhost:8000/api/v1
```

(Puerto por defecto de `php artisan serve`. Ajustar según entorno.)

---

## Arquitectura sugerida del frontend

```
src/
  api/           # cliente HTTP y funciones por módulo
  components/    # componentes reutilizables (tabla, modal, badge, etc.)
  features/      # módulos: auth, clients, projects, samples, dashboard, settings
  hooks/         # hooks de datos (useClients, useSamples, etc.)
  layouts/       # layout principal (sidebar + topbar + content)
  pages/         # páginas que componen las rutas
  types/         # tipos TypeScript derivados de los shapes de la API
  utils/         # helpers (formatFecha, formatStatus, etc.)
```

---

## Estados de UI que siempre debes implementar

Para cualquier pantalla que cargue datos remotos:

- **Loading** — spinner o skeleton mientras carga
- **Error** — mensaje claro si la llamada falla
- **Empty** — mensaje si no hay datos
- **Disabled durante submit** — botones deshabilitados mientras se envía un formulario

Sin estos cuatro estados la pantalla no está terminada.
