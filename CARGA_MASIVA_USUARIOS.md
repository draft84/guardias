# 📤 Carga Masiva de Usuarios - Guía de Uso

## 📋 Descripción

El sistema permite importar usuarios de forma masiva mediante archivos Excel (.xlsx o .xls). Esta funcionalidad es útil para cargar múltiples usuarios simultáneamente sin tener que crearlos uno por uno.

---

## 🔐 Permisos Requeridos

| Rol | Puede Importar | Puede Exportar |
|-----|---------------|----------------|
| **ADMIN** | ✅ Sí | ✅ Sí |
| **MANAGER** | ✅ Sí (solo su departamento) | ✅ Sí |
| **USER** | ❌ No | ❌ No |

---

## 📥 Descargar Plantilla

### Desde la Interfaz

1. Ir a **Usuarios** en el menú lateral
2. Hacer clic en el botón **"Importar"**
3. En el dialog que se abre, hacer clic en **"Descargar Plantilla"**

### Endpoint API

```http
GET /api/users/export-template
Authorization: Bearer <token>
```

**Respuesta:** Archivo Excel `plantilla_usuarios.xlsx`

---

## 📄 Estructura del Archivo Excel

### Columnas Requeridas

| Columna | Nombre | Descripción | Ejemplo |
|---------|--------|-------------|---------|
| **A** | Email * | Correo electrónico único | `juan.perez@example.com` |
| **B** | Password * | Contraseña (mínimo 6 caracteres) | `miPassword123` |
| **C** | FirstName * | Nombre del usuario | `Juan` |
| **D** | LastName * | Apellido del usuario | `Pérez` |
| **E** | Department Code * | Código del departamento | `OPS` |

### Columnas Opcionales

| Columna | Nombre | Descripción | Ejemplo |
|---------|--------|-------------|---------|
| **F** | Phone | Número de teléfono | `04121234567` |
| **G** | Guard Level | Nombre del nivel | `Junior` |
| **H** | Roles | Roles separados por coma | `ROLE_USER` |
| **I** | Active | 1=Activo, 0=Inactivo | `1` |

---

## 📝 Códigos de Departamentos Disponibles

| Código | Departamento |
|--------|-------------|
| `OPS` | Operaciones |
| `TECH` | Tecnología |
| `HR` | Recursos Humanos |

---

## 🎯 Niveles de Guardia Disponibles

| Nivel | Descripción |
|-------|-------------|
| `Junior` | Nivel inicial |
| `Senior` | Nivel avanzado |
| `Residente` | Nivel residente |

---

## 📋 Ejemplo de Datos

| Email | Password | FirstName | LastName | Department Code | Phone | Guard Level | Roles | Active |
|-------|----------|-----------|----------|-----------------|-------|-------------|-------|--------|
| `usuario1@example.com` | `password123` | `Juan` | `Pérez` | `OPS` | `04121234567` | `Junior` | `ROLE_USER` | `1` |
| `usuario2@example.com` | `password123` | `María` | `García` | `TECH` | `04147654321` | `Senior` | `ROLE_USER` | `1` |
| `usuario3@example.com` | `password123` | `Pedro` | `López` | `HR` | `04169876543` | `Junior` | `ROLE_USER,ROLE_MANAGER` | `1` |

---

## 📤 Proceso de Importación

### Paso 1: Preparar el Archivo

1. Descargar la plantilla oficial
2. Completar los datos de los usuarios
3. Guardar el archivo en formato Excel (.xlsx o .xls)

### Paso 2: Subir el Archivo

1. Ir a **Usuarios** en el menú lateral
2. Hacer clic en el botón **"Importar"**
3. Hacer clic en **"Seleccionar archivo"**
4. Elegir el archivo Excel preparado
5. Hacer clic en **"Importar"**

### Paso 3: Verificar Resultados

El sistema mostrará un resumen:

```
✅ 8 de 10 usuarios importados

Errores encontrados:
  - Fila 3: El email juan.perez@example.com ya está en uso
  - Fila 7: Departamento no encontrado: MKT
```

---

## ⚠️ Validaciones

### Campos Requeridos

- **Email**: No puede estar vacío, debe ser único en el sistema
- **Password**: No puede estar vacío, mínimo 6 caracteres
- **FirstName**: No puede estar vacío
- **LastName**: No puede estar vacío
- **Department Code**: No puede estar vacío, debe existir

### Validaciones de Formato

- **Email**: Debe tener formato válido (ej: `usuario@dominio.com`)
- **Department Code**: Debe ser uno de los códigos existentes (OPS, TECH, HR)
- **Guard Level**: Debe ser un nivel existente (Junior, Senior, Residente)
- **Roles**: Deben ser roles válidos (ROLE_USER, ROLE_MANAGER, ROLE_ADMIN)
- **Active**: Debe ser 0 o 1

---

## 🔍 Mensajes de Error Comunes

| Error | Causa | Solución |
|-------|-------|----------|
| `Campos requeridos vacíos` | Faltan datos en columnas A, B, C, D o E | Completar todos los campos requeridos |
| `El email X ya está en uso` | El email ya existe en la base de datos | Usar un email diferente o eliminar el usuario existente |
| `Departamento no encontrado: X` | El código de departamento no existe | Verificar el código (OPS, TECH, HR) |
| `El archivo debe ser un Excel` | El formato del archivo no es válido | Guardar como .xlsx o .xls |
| `No se encontró el archivo` | No se seleccionó ningún archivo | Seleccionar un archivo antes de importar |

---

## 📊 Exportar Usuarios

### Desde la Interfaz

1. Ir a **Usuarios** en el menú lateral
2. Hacer clic en el botón **"Exportar"**
3. El archivo `usuarios_export_YYYY-MM-DD.xlsx` se descargará automáticamente

### Endpoint API

```http
GET /api/users/export
Authorization: Bearer <token>
```

**Respuesta:** Archivo Excel con todos los usuarios

### Notas de Exportación

- **Password**: Se exporta como `[PROTECTED]` por seguridad
- **Department Code**: Se exporta el código del departamento
- **Roles**: Se exportan separados por coma
- **Active**: Se exporta como 1 (activo) o 0 (inactivo)

---

## 🧪 Ejemplo de Archivo Completo

```
Email                      | Password    | FirstName | LastName | Department Code | Phone       | Guard Level | Roles                | Active
---------------------------|-------------|-----------|----------|-----------------|-------------|-------------|----------------------|-------
admin@example.com          | admin123    | Admin     | Sistema  | TECH            | 04120000001 | Senior      | ROLE_ADMIN           | 1
manager@example.com        | manager123  | Manager   | Operaciones | OPS          | 04120000002 | Senior      | ROLE_MANAGER         | 1
juan.perez@example.com     | user123     | Juan      | Pérez    | OPS             | 04121234567 | Junior      | ROLE_USER            | 1
maria.garcia@example.com   | user123     | María     | García   | TECH            | 04147654321 | Senior      | ROLE_USER            | 1
pedro.lopez@example.com    | user123     | Pedro     | López    | HR              | 04169876543 | Junior      | ROLE_USER            | 1
```

---

## 🛡️ Consideraciones de Seguridad

1. **Contraseñas**: 
   - Se hash automáticamente al importar
   - No se exportan por seguridad
   - Usar contraseñas temporales que el usuario deba cambiar

2. **Permisos**:
   - Solo ADMIN y MANAGER pueden importar/exportar
   - Los usuarios importados tienen por defecto ROLE_USER

3. **Validación**:
   - Todos los datos se validan antes de importar
   - Los errores no detienen la importación completa
   - Se reporta fila por fila los errores encontrados

---

## 📞 Soporte

Si experimenta problemas con la carga masiva:

1. Verificar que el archivo sea .xlsx o .xls
2. Descargar la plantilla oficial y usarla como base
3. Verificar que todos los campos requeridos estén completos
4. Revisar los códigos de departamento y niveles

---

*Documento generado para el Sistema de Guardias*
*Última actualización: 2026-03-10*
