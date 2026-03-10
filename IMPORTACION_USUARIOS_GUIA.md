# 📤 Carga Masiva de Usuarios - Guía Rápida

## ✅ Funcionalidades Disponibles

### 1. Descargar Plantilla Oficial
- **Desde el frontend:** Ir a Usuarios → Importar → "Descargar Plantilla"
- **Contenido:** Archivo Excel con encabezados, ejemplos e instrucciones

### 2. Exportar Usuarios Actuales
- **Desde el frontend:** Ir a Usuarios → "Exportar"
- **Contenido:** Todos los usuarios del sistema en formato Excel

### 3. Importar Usuarios Masivamente
- **Desde el frontend:** Ir a Usuarios → "Importar"
- **Formato:** Archivo Excel (.xlsx o .xls)
- **Permisos:** Solo ADMIN y MANAGER pueden importar

---

## 📄 Estructura de la Plantilla

### Columnas Requeridas (*)

| Columna | Nombre | Descripción | Ejemplo |
|---------|--------|-------------|---------|
| A | Email * | Correo único | `juan@example.com` |
| B | Password * | Mínimo 6 caracteres | `password123` |
| C | FirstName * | Nombre | `Juan` |
| D | LastName * | Apellido | `Pérez` |
| E | Department Code * | Código de departamento | `OPS` |

### Columnas Opcionales

| Columna | Nombre | Descripción | Ejemplo |
|---------|--------|-------------|---------|
| F | Phone | Teléfono | `04121234567` |
| G | Guard Level | Nivel | `Junior` |
| H | Roles | Separados por coma | `ROLE_USER` |
| I | Active | 1=Activo, 0=Inactivo | `1` |

---

## 🎯 Códigos Válidos

### Departamentos
- `OPS` - Operaciones
- `TECH` - Tecnología  
- `HR` - Recursos Humanos

### Niveles de Guardia
- `Junior`
- `Senior`
- `Residente`

### Roles
- `ROLE_USER`
- `ROLE_MANAGER`
- `ROLE_ADMIN`

---

## 📝 Ejemplo de Datos

```
Email                 | Password    | FirstName | LastName | Department Code | Phone       | Guard Level | Roles       | Active
----------------------|-------------|-----------|----------|-----------------|-------------|-------------|-------------|-------
usuario1@example.com  | password123 | Juan      | Pérez    | OPS             | 04121234567 | Junior      | ROLE_USER   | 1
usuario2@example.com  | password123 | María     | García   | TECH            | 04147654321 | Senior      | ROLE_USER   | 1
```

---

## ⚠️ Validaciones

### Errores Comunes

| Error | Causa | Solución |
|-------|-------|----------|
| `Campos requeridos vacíos` | Faltan columnas A-E | Completar todos los campos |
| `Email ya está en uso` | Email duplicado | Usar email diferente |
| `Departamento no encontrado` | Código inválido | Usar OPS, TECH o HR |
| `El archivo debe ser Excel` | Formato incorrecto | Guardar como .xlsx |

---

## 🔧 Solución de Problemas

### La plantilla no se descarga
1. Verificar que tiene rol ADMIN o MANAGER
2. Abrir consola del navegador (F12)
3. Verificar errores en la consola
4. Reintentar la descarga

### Error al importar
1. Revisar que el archivo sea .xlsx o .xls
2. Verificar que los encabezados estén correctos
3. Asegurarse de no modificar los nombres de columnas
4. Revisar el mensaje de error para identificar la fila problemática

---

## 📊 Proceso de Importación

1. **Descargar plantilla** → Botón "Descargar Plantilla"
2. **Completar datos** → En Excel, respetando el formato
3. **Guardar archivo** → Como .xlsx o .xls
4. **Subir archivo** → Botón "Importar" → Seleccionar archivo
5. **Ver resultados** → El sistema muestra:
   - ✅ Usuarios importados exitosamente
   - ⚠️ Errores encontrados (fila y motivo)

---

## 🔐 Permisos

| Rol | Descargar Plantilla | Exportar | Importar |
|-----|---------------------|----------|----------|
| ADMIN | ✅ | ✅ | ✅ |
| MANAGER | ✅ | ✅ | ✅ |
| USER | ❌ | ❌ | ❌ |

---

## 📞 Soporte

Si tiene problemas con la carga masiva:
1. Verifique el formato del archivo
2. Revise los códigos de departamento
3. Asegúrese de que los emails sean únicos
4. Consulte los mensajes de error detallados

---

*Documento generado para el Sistema de Guardias*
*Última actualización: 2026-03-10*
