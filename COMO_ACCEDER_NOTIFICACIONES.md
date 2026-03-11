# 🔔 Cómo Acceder a las Notificaciones

## 📍 Ubicación del Enlace

El enlace a **Notificaciones** está ubicado en el **sidebar izquierdo** de la aplicación, entre "Calendario" y "Configuración".

## 🎯 Pasos para Acceder

### Opción 1: Desde el Sidebar (Recomendado)
1. Inicia sesión en la aplicación
2. Mira el menú lateral izquierdo
3. Haz click en **"Notificaciones"** (icono de campana 🔔)

### Opción 2: Desde la Campana del Header
1. Haz click en el icono de campana en la esquina superior derecha
2. En la parte inferior del dropdown, haz click en **"Ver todas"**

### Opción 3: URL Directa
```
http://localhost:5173/notifications
```

## 📋 Qué Verás en la Página de Notificaciones

### Parte Superior
- **Título**: "Notificaciones"
- **Contador**: Badge rojo con número de notificaciones no leídas
- **Botón Actualizar**: Para recargar las notificaciones

### Filtros y Búsqueda
- **Buscador**: Escribe para filtrar por título o mensaje
- **Filtro por tipo**:
  - Todas
  - No leídas
  - Leídas
  - Cambios (solicitudes de cambio)
  - Aceptadas

### Lista de Notificaciones (Acordeón)
Cada notificación muestra:
- **Icono** con color según el tipo
- **Título** de la notificación
- **Tag** con el tipo
- **Tiempo** transcurrido (ej: "hace 2h")
- **Indicador** de no leído (punto azul)

### Al Expandir (Click en la notificación)
- **Mensaje completo**
- **Fecha** detallada
- **Estado** (leída/no leída)
- **Detalles adicionales** para solicitudes de cambio:
  - Solicitante
  - Fecha de guardia
  - Turno
  - Estado
- **Botones de acción** (para swap_request):
  - ✅ Aceptar Cambio
  - ❌ Rechazar Cambio

## ✅ Aceptar un Cambio de Guardia

1. Haz click en una notificación de tipo "Solicitud de Cambio" para expandirla
2. Revisa los detalles del cambio
3. Haz click en el botón verde **"Aceptar Cambio"**
4. Espera la confirmación
5. La notificación se marcará como leída automáticamente

## ❌ Rechazar un Cambio de Guardia

1. Haz click en una notificación de tipo "Solicitud de Cambio" para expandirla
2. Haz click en el botón rojo **"Rechazar Cambio"**
3. (Opcional) Escribe el motivo del rechazo
4. Haz click en "Rechazar"
5. La notificación se marcará como leída automáticamente

## 📊 Paginación

Si hay más de 10 notificaciones:
- Usa los **números** para navegar a páginas específicas
- Usa las **flechas** (← →) para ir a la página anterior/siguiente
- El botón de la página actual está resaltado en color primario

## 🔄 Actualización Automática

- El contador de no leídas se actualiza automáticamente cada 10 segundos
- Puedes forzar la actualización con el botón de refresh en la parte superior

## 🎨 Tipos de Notificaciones

| Icono | Tipo | Color | Descripción |
|-------|------|-------|-------------|
| 🔄 | Solicitud de Cambio | Naranja | Alguien solicita cambiar tu guardia |
| ✓ | Cambio Aceptado | Verde | Tu solicitud fue aceptada |
| ✕ | Cambio Rechazado | Rojo | Tu solicitud fue rechazada |
| 📅 | Nueva Asignación | Azul | Te asignaron una nueva guardia |
| ✏️ | Asignación Actualizada | Morado | Tu guardia fue modificada |

---

**URL de Acceso**: http://localhost:5173/notifications
