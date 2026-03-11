# 📬 Sistema de Notificaciones - Vista de Detalle

## ✅ Funcionalidades Implementadas

### 1. Vista de Notificaciones (`/notifications`)
- **Lista de notificaciones en formato acordeón**
- **Buscador** con debounce (300ms) para filtrar por título o mensaje
- **Filtros por tipo**:
  - Todas
  - No leídas
  - Leídas
  - Solicitudes de cambio
  - Aceptadas
- **Paginación** con navegación inteligente (10 notificaciones por página)
- **Indicador visual** de notificaciones no leídas (punto animado)
- **Contador** de notificaciones no leídas en el sidebar

### 2. Detalle de Notificación
Cada notificación expandible muestra:
- **Icono y color** según el tipo
- **Título y mensaje** completo
- **Fecha formateada** (hace X minutos/horas/días)
- **Estado** (leída/no leída)
- **Detalles adicionales** para solicitudes de cambio:
  - Solicitante
  - Fecha de guardia
  - Turno
  - Estado
- **Acciones** para `swap_request`:
  - ✅ Aceptar cambio
  - ❌ Rechazar cambio (con motivo opcional)

### 3. Componentes Creados

#### `NotificationDetail.vue`
- Muestra el detalle completo de una notificación
- Botones de aceptar/rechazar para solicitudes de cambio
- Diálogo de confirmación para rechazo con campo de motivo
- Emite eventos: `accepted`, `rejected`, `updated`

#### `NotificationList.vue`
- Lista de notificaciones en formato acordeón (PrimeVue Accordion)
- Buscador con debounce
- Filtros por tipo
- Paginación con navegación inteligente
- Botón "Marcar todas como leídas"

#### `NotificationsView.vue`
- Vista principal de notificaciones
- Encabezado con título y contador
- Botón de actualizar
- Integra `NotificationList`

### 4. Backend - Nuevos Endpoints

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| `POST` | `/api/notifications/{id}/accept-swap` | Aceptar solicitud de cambio |
| `POST` | `/api/notifications/{id}/reject-swap` | Rechazar solicitud de cambio |

### 5. Store - `notification.store.js`

**Estado añadido:**
- `currentPage`: Página actual
- `totalPages`: Total de páginas
- `totalItems`: Total de items
- `itemsPerPage`: Items por página
- `searchQuery`: Búsqueda actual
- `filterType`: Filtro aplicado

**Getters:**
- `filteredNotifications`: Notificaciones filtradas por búsqueda y tipo

**Acciones:**
- `fetchNotifications(options)`: Cargar notificaciones con paginación y filtros
- `rejectSwap(notificationId, reason)`: Rechazar cambio
- `setSearchQuery(query)`: Establecer búsqueda
- `setFilterType(type)`: Establecer filtro
- `setPage(page)`: Establecer página

### 6. Navegación

#### Sidebar
- Nuevo enlace "Notificaciones" con icono de campana
- Badge rojo con contador de no leídas (solo visible si hay no leídas)

#### NotificationsBell
- Botón "Ver Detalles" navega a `/notifications`
- Botón "Ver todas" navega a `/notifications`
- Contador sincronizado con `authStore`

## 🎨 UI/UX

### Acordeón
- Cada notificación es un panel expandible
- Muestra resumen cuando está cerrado:
  - Icono con color según tipo
  - Título
  - Tag con tipo de notificación
  - Tiempo transcurrido
  - Primera línea del mensaje
  - Indicador de no leído (punto animado)

### Colores por Tipo
| Tipo | Icono | Color | Fondo |
|------|-------|-------|-------|
| `swap_request` | 🔄 exchange | Naranja | Amarillo claro |
| `swap_accepted` | ✓ check-circle | Verde | Verde claro |
| `swap_rejected` | ✕ times-circle | Rojo | Rojo claro |
| `assignment_created` | 📅 calendar-plus | Azul | Azul claro |
| `assignment_updated` | ✏️ calendar-edit | Morado | Morado claro |

### Paginación
- Muestra páginas relevantes según página actual
- Botones anterior/siguiente
- Deshabilita botones en extremos
- Navegación directa a página específica

## 📁 Archivos Modificados/Creados

### Backend
- ✅ `src/Controller/Api/NotificationController.php`
  - Añadido método `acceptSwap()`
  - Añadido método `rejectSwap()`

### Frontend
- ✅ `src/stores/notification.store.js` (modificado)
- ✅ `src/stores/auth.store.js` (modificado)
- ✅ `src/components/NotificationsBell.vue` (modificado)
- ✅ `src/layouts/DashboardLayout.vue` (modificado)
- ✅ `src/router/index.js` (modificado)
- ✅ `src/components/NotificationDetail.vue` (nuevo)
- ✅ `src/components/NotificationList.vue` (nuevo)
- ✅ `src/views/NotificationsView.vue` (nuevo)

## 🚀 Cómo Usar

1. **Acceder a notificaciones:**
   - Click en campana del header → "Ver todas"
   - Click en "Notificaciones" del sidebar
   - Navegar directamente a `/notifications`

2. **Ver detalle:**
   - Click en cualquier notificación para expandirla

3. **Buscar:**
   - Escribir en el campo de búsqueda
   - Filtra automáticamente mientras escribes

4. **Filtrar:**
   - Seleccionar tipo en el dropdown
   - Opciones: Todas, No leídas, Leídas, Cambios, Aceptadas

5. **Aceptar/Rechazar cambio:**
   - Expandir notificación de tipo "Solicitud de Cambio"
   - Click en "Aceptar Cambio" o "Rechazar Cambio"
   - Para rechazo, opcionalmente escribir motivo

6. **Paginación:**
   - Click en números para navegar
   - Flechas para anterior/siguiente

## 🔄 Flujo de Aceptación/Rechazo

### Aceptar
1. Usuario expande notificación
2. Click en "Aceptar Cambio"
3. Backend actualiza asignación con nuevo usuario
4. Backend crea notificación de confirmación para solicitante
5. Frontend muestra mensaje de éxito
6. Lista se actualiza automáticamente

### Rechazar
1. Usuario expande notificación
2. Click en "Rechazar Cambio"
3. Se abre diálogo para escribir motivo (opcional)
4. Click en "Rechazar"
5. Backend crea notificación de rechazo para solicitante
6. Frontend muestra mensaje de éxito
7. Lista se actualiza automáticamente

## 📊 Contador de No Leídas

- Se actualiza automáticamente cada 10 segundos (polling)
- Visible en:
  - Badge del icono de campana en header
  - Badge del enlace en sidebar
  - Encabezado de la vista de notificaciones
- Se actualiza al:
  - Aceptar/rechazar cambio
  - Marcar como leída
  - Marcar todas como leídas

## 🎯 URLs

| Servicio | URL |
|----------|-----|
| Frontend | http://localhost:5173 |
| Notificaciones | http://localhost:5173/notifications |
| Backend API | http://localhost:8000 |

---

*Implementación completada - 2026-03-11*
