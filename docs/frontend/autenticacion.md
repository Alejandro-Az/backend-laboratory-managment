# Autenticación — JWT

---

## Flujo completo

```
1. Usuario envía email + password → POST /auth/login
2. Backend responde con { token, token_type, expires_in, user }
3. Almacenar token
4. Incluir token en cada petición protegida: Authorization: Bearer <token>
5. Cuando el token esté por expirar → POST /auth/refresh
6. Al cerrar sesión → POST /auth/logout + limpiar token local
```

---

## Endpoint de login

```
POST /api/v1/auth/login
```

**Body:**
```json
{
  "email": "admin@laboratory.local",
  "password": "password"
}
```

**Respuesta exitosa (200):**
```json
{
  "ok": true,
  "data": {
    "token": "eyJ0eXAiOiJKV1Qi...",
    "token_type": "bearer",
    "expires_in": 3600,
    "user": {
      "id": 1,
      "name": "Admin User",
      "email": "admin@laboratory.local",
      "roles": ["admin"],
      "permissions": ["clients.view", "clients.create", "..."]
    }
  },
  "message": "Authenticated successfully."
}
```

**Credenciales incorrectas (401):**
```json
{
  "ok": false,
  "error": {
    "code": "INVALID_CREDENTIALS",
    "message": "Invalid credentials.",
    "details": {}
  }
}
```

---

## Almacenamiento del token

Recomendación: `localStorage` es suficiente para este MVP.

```typescript
// Guardar tras login
localStorage.setItem('token', data.token)
localStorage.setItem('user', JSON.stringify(data.user))

// Leer
const token = localStorage.getItem('token')

// Limpiar tras logout
localStorage.removeItem('token')
localStorage.removeItem('user')
```

---

## Cliente HTTP centralizado

Configurar un interceptor que añada el header de Authorization automáticamente:

```typescript
// api/client.ts
import axios from 'axios'

const client = axios.create({
  baseURL: 'http://localhost:8000/api/v1',
  headers: { 'Content-Type': 'application/json' },
})

client.interceptors.request.use((config) => {
  const token = localStorage.getItem('token')
  if (token) config.headers.Authorization = `Bearer ${token}`
  return config
})

client.interceptors.response.use(
  (res) => res,
  (err) => {
    if (err.response?.status === 401) {
      // Token inválido o expirado → redirigir a login
      localStorage.removeItem('token')
      localStorage.removeItem('user')
      window.location.href = '/login'
    }
    return Promise.reject(err)
  }
)

export default client
```

---

## Endpoint GET /me

Útil para verificar que el token sigue siendo válido al recargar la app:

```
GET /api/v1/me
Authorization: Bearer <token>
```

**Respuesta (200):**
```json
{
  "ok": true,
  "data": {
    "id": 1,
    "name": "Admin User",
    "email": "admin@laboratory.local",
    "roles": ["admin"],
    "permissions": ["clients.view", "clients.create", "..."]
  },
  "message": "Success"
}
```

**Flujo recomendado al iniciar la app:**
1. Leer token de `localStorage`
2. Si existe, llamar `GET /me`
3. Si responde 200 → usuario autenticado, redirigir a dashboard
4. Si responde 401 → limpiar storage, mostrar pantalla de login

---

## Renovar token

```
POST /api/v1/auth/refresh
Authorization: Bearer <token>
```

**Respuesta (200):**
```json
{
  "ok": true,
  "data": {
    "token": "eyJ0eXAiOiJKV1Qi...",
    "token_type": "bearer",
    "expires_in": 3600
  },
  "message": "Token refreshed successfully."
}
```

`expires_in` viene en segundos (normalmente 3600 = 1 hora). Implementar refresh automático ~5 minutos antes de expirar o al recibir un 401.

---

## Cerrar sesión

```
POST /api/v1/auth/logout
Authorization: Bearer <token>
```

**Respuesta (200):**
```json
{
  "ok": true,
  "data": {},
  "message": "Logged out successfully."
}
```

Siempre limpiar `localStorage` después, independientemente de si la llamada falla.

---

## Protección de rutas

Implementar un componente `ProtectedRoute` que:
1. Comprueba si hay token en localStorage
2. Si no hay → redirige a `/login`
3. Si hay → verifica con `GET /me` (opcional, solo al montar)
4. Pasa `user` por contexto a los hijos

---

## Roles y permisos en el frontend

El objeto `user` del login incluye `roles` y `permissions`.

- `roles`: array de strings. Valores posibles: `["admin"]` o `["analyst"]`
- `permissions`: array con todos los permisos del rol

**Uso recomendado:** almacenar el user en un contexto React y comprobar el rol donde sea necesario.

```typescript
const isAdmin = user.roles.includes('admin')
const isAnalyst = user.roles.includes('analyst')
```

**Permisos del rol `admin`:** todos (ver, crear, editar, eliminar, restaurar, cambiar status, cambiar priority, agregar resultados, ver eventos, dashboard)

**Permisos del rol `analyst`:**
- Ver clientes y proyectos (solo lectura)
- Ver, cambiar status, agregar resultados y ver eventos de muestras
- Ver dashboard

Tabla completa en `pantallas.md`.
