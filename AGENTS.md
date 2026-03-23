# AGENTS.md

## Propósito de este archivo

Este archivo define las reglas obligatorias para cualquier agente de IA, asistente técnico o colaborador que trabaje en este repositorio.

El objetivo es mantener un desarrollo:

- coherente
- limpio
- mantenible
- seguro
- proporcional al MVP
- sin sobreingeniería
- compatible con trabajo asistido por IA

Este archivo debe respetarse en todo momento.

---

## Regla principal

Este proyecto es un MVP vertical de gestión de laboratorio.

No es un framework.
No es un core genérico reusable.
No debe transformarse en una plataforma sobrediseñada.

Toda decisión debe favorecer:

- claridad
- velocidad razonable
- arquitectura sólida
- mantenibilidad
- cumplimiento real del alcance

---

## Documentos de referencia obligatorios

Antes de proponer cambios o escribir código, revisar:

- `CONTEXTO.md`
- `README.md`
- `docs/` si ya existe documentación adicional

`CONTEXTO.md` es la fuente principal de verdad para:

- stack
- alcance
- decisiones ya cerradas
- reglas de dominio
- contratos API
- lineamientos de arquitectura

---

## Modo operativo obligatorio

### 1. Exploration
Antes de proponer cambios:
- entender el estado actual del proyecto
- identificar archivos involucrados
- revisar dependencias
- detectar restricciones
- detectar si una regla ya fue definida en `CONTEXTO.md`

### 2. Architecture reasoning
Antes de escribir código:
- analizar impacto en arquitectura
- analizar impacto en seguridad
- analizar impacto en mantenibilidad
- analizar impacto en contrato API
- analizar impacto en frontend
- analizar impacto en base de datos
- preferir la solución más simple que cumpla correctamente

### 3. Implementation plan
Para cambios no triviales, explicar:
- archivos a crear
- archivos a modificar
- comandos necesarios
- migraciones involucradas
- impacto en API
- impacto en permisos
- impacto en tests
- impacto en documentación

### 4. Implementation
Solo después del análisis y plan se escribe código.

### 5. Verification
Antes de cerrar un cambio, validar:
- contrato API
- JWT
- permisos y roles
- validación
- edge cases
- consistencia de nombres
- impacto en documentación
- higiene del repositorio

---

## Reglas arquitectónicas obligatorias

### 1. Backend API-first
La lógica de negocio vive en backend.
El frontend consume la API.
El frontend no debe duplicar reglas de negocio.

### 2. JWT como flujo principal de autenticación
La API debe protegerse con JWT.
No mezclar el flujo principal con autenticación por sesión.

### 3. RBAC con Spatie
Los roles y permisos se manejan con `spatie/laravel-permission`.

### 4. Contrato de API consistente
Todos los endpoints deben respetar un envelope uniforme.

### 5. Arquitectura proporcional al MVP
No introducir capas, patrones o servicios extra si no aportan valor inmediato y claro.

### 6. No sobreingenierizar
Evitar:
- abstracciones prematuras
- patrones complejos sin necesidad
- archivos innecesarios
- middlewares extra sin justificación
- tablas que no responden a una necesidad real
- “infraestructura bonita” que retrase el núcleo funcional

---

## Contrato de API obligatorio

### Respuesta exitosa
```json
{
  "ok": true,
  "data": {},
  "message": "Success"
}
```

### Respuesta con error
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

### Reglas del contrato
- no devolver estructuras arbitrarias por endpoint
- mantener consistencia entre endpoints similares
- no cambiar nombres de campos sin documentarlo
- mantener paginación consistente
- documentar cualquier cambio de contrato

---

## Reglas para base de datos

### Principios
- crear migraciones pequeñas y atómicas
- usar foreign keys
- agregar índices donde haya filtros reales
- evitar duplicación por comodidad de UI
- no persistir métricas derivadas
- usar soft deletes en entidades principales cuando aplique

### Reglas concretas
- no duplicar `client_id` dentro de `samples`
- `samples` pertenece a `projects`
- `projects` pertenece a `clients`
- `urgent` no es `status`; es `priority`
- `dashboard` debe salir de queries, no de columnas persistidas
- la actividad reciente debe salir de una bitácora persistida (`sample_events`)

### Nota de implementación
En documentación o diagramas se pueden usar enums conceptuales.
En Laravel se puede implementar con `string` + enums/constantes de PHP para mantener flexibilidad.

---

## Reglas para backend Laravel

- usar Form Requests para validación
- usar Policies para autorización por recurso
- usar Spatie para roles y permisos base
- mantener Controllers delgados
- no meter lógica pesada en Controllers
- usar Resources o transformadores consistentes para respuestas
- aplicar paginación server-side donde corresponda
- mantener filtros explícitos y previsibles
- escribir nombres de clases, métodos y archivos en inglés

### Seguridad
- validar input siempre
- no confiar en flags enviados por frontend
- aplicar control por rol y por recurso
- no asumir permisos implícitos
- validar acceso a recursos relacionados
- no exponer información sensible innecesaria

---

## Reglas para frontend

- el frontend es un panel administrativo
- no es un sitio de marketing
- priorizar claridad, velocidad y consistencia
- layout simple: sidebar, topbar, content area
- tablas limpias, filtros claros, estados de carga / error / vacío
- no perseguir pixel-perfect innecesario
- consumir siempre la API en lugar de hardcodear reglas

### Reglas técnicas
- preferir TypeScript
- organizar por features o módulos
- centralizar el cliente HTTP
- manejar JWT de manera consistente
- no duplicar lógica de fetch innecesariamente
- formularios simples y mantenibles
- no meter estado global para todo sin razón

### Accesibilidad mínima
- labels reales en inputs
- botones con texto claro
- feedback visual de error
- estados disabled durante submit
- loaders y empty states visibles

---

## Reglas de documentación

La documentación es parte del producto.

### Regla oficial de idioma
- código en inglés
- documentación oficial en español

### La documentación debe mantenerse al día
Cada cambio importante en:
- modelo de datos
- endpoints
- autenticación
- permisos
- filtros
- convenciones frontend
- estructura del proyecto

debe reflejarse en documentación.

### Documentos esperados
- `README.md`
- `CONTEXTO.md`
- `docs/architecture.md`
- `docs/database.md`
- `docs/api.md`
- `docs/frontend-integration.md`
- `docs/implementation-plan.md`

---

## Higiene del proyecto

Especialmente por el uso de IA, el repositorio debe mantenerse limpio.

### No deben permanecer
- scripts temporales
- logs manuales
- tests experimentales sin propósito real
- fixtures temporales olvidados
- dumps de base de datos
- archivos de debugging
- componentes muertos
- servicios no usados
- imports muertos
- código comentado sin justificación
- scaffolds vacíos o placeholders inútiles

### Entorno
Nunca versionar:
- `.env`
- `.env.local`
- `.env.testing`

Sí versionar:
- `.env.example`

### Reproducibilidad
El sistema debe poder recrearse de forma predecible.

---

## Reglas especiales por uso de IA

### AI diff review rule
Todo cambio generado por IA debe revisarse críticamente antes de aceptarse.

La IA puede:
- duplicar lógica
- introducir inconsistencias
- crear archivos innecesarios
- romper contratos
- proponer estructuras sobredimensionadas

Nada generado por IA debe aceptarse sin revisar su impacto real.

### No ghost code rule
No debe existir código que:
- no esté conectado al flujo real
- no tenga uso claro
- no tenga propósito verificable

### Test intent rule
Todo test debe reflejar un comportamiento real del sistema.
No mantener tests solo para debugging o experimentación momentánea.

---

## Reglas al crear archivos

Siempre indicar:
- ruta exacta
- propósito del archivo
- responsabilidad
- integración con la arquitectura

Cuando aplique, indicar también:
- comando de consola
- impacto en otros módulos

---

## Reglas al modificar archivos

Siempre indicar:
- ruta exacta
- sección afectada
- tipo de cambio
- impacto en otros componentes
- si requiere actualizar documentación
- si requiere ajustar tests

---

## Reglas para migraciones

Siempre explicar:
- si se modifica una migración base o se crea una nueva
- impacto en ambientes existentes
- impacto en producción
- comando de migración
- dependencias entre tablas

---

## Testing mínimo esperado

### Backend
Cubrir al menos:
- login
- acceso protegido
- filtros de samples
- creación de sample
- actualización de status
- actualización de priority
- permisos por rol
- errores de validación

### Frontend
Validar al menos:
- login
- rutas protegidas
- dashboard carga correctamente
- tabla de samples filtra y pagina
- formularios muestran errores
- estados loading / empty / error
- restricciones visibles según rol cuando aplique

---

## Qué hacer al terminar un cambio

Siempre resumir:
1. qué se hizo
2. archivos creados
3. archivos modificados
4. supuestos tomados
5. riesgos o pendientes
6. impacto en API
7. impacto en DB
8. impacto en frontend
9. impacto en documentación

---

## Política de decisiones cerradas

No cambiar decisiones ya cerradas sin explicarlo primero y reflejarlo en documentación.

Cuando falte información:
- explicitar el supuesto
- elegir la opción más simple compatible con el MVP
- evitar inventar complejidad innecesaria

---

## Meta final

Entregar un MVP funcional, coherente, limpio, defendible técnicamente y fácil de mantener.

La prioridad no es construir “muchísimo”.
La prioridad es construir correctamente el núcleo.
