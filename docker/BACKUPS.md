# 📦 Sistema de Backup y Restauración

Sistema completo de backup y restauración para el proyecto Guardias.

---

## 📋 Descripción

Este sistema permite:
- ✅ Crear backups completos del proyecto (base de datos + código + volúmenes)
- ✅ Restaurar backups en caso de necesitar revertir cambios
- ✅ Gestionar backups existentes (listar, verificar, eliminar)
- ✅ Mantener automáticamente los últimos 10 backups

---

## 🚀 Uso Rápido

### Crear un backup

```bash
cd docker
./backup.sh
```

**Esto creará:**
- Dump de la base de datos MySQL comprimido
- Copia del código fuente (backend y frontend)
- Copia de los volúmenes Docker
- Archivo de metadatos con información del backup
- Backup comprimido en `docker/backups/`

### Listar backups disponibles

```bash
./manage_backups.sh list
```

### Verificar un backup

```bash
./manage_backups.sh verify guardias_backup_20260310_120000.tar.gz
```

### Restaurar un backup

```bash
./restore.sh guardias_backup_20260310_120000.tar.gz
```

⚠️ **Advertencia:** La restauración:
1. Detiene todos los contenedores
2. Elimina la base de datos actual
3. Reemplaza el código fuente
4. Restaura los volúmenes Docker

---

## 📁 Estructura de Backups

```
docker/
├── backups/
│   ├── guardias_backup_20260310_120000.tar.gz
│   ├── guardias_backup_20260310_130000.tar.gz
│   └── guardias_backup_20260310_140000.tar.gz
├── backup.sh           # Script de creación de backups
├── restore.sh          # Script de restauración
└── manage_backups.sh   # Script de gestión
```

### Contenido de cada backup

```
guardias_backup_YYYYMMDD_HHMMSS/
├── database.sql.gz         # Dump de la base de datos comprimido
├── source/                 # Código fuente
│   ├── backend/
│   ├── frontend/
│   ├── docker-compose.yml
│   ├── Dockerfile.backend
│   ├── Dockerfile.frontend
│   ├── nginx/
│   └── Documentación (.md)
├── volumes/                # Volúmenes Docker
│   └── mysql_data.tar.gz
└── metadata.json           # Metadatos del backup
```

---

## 🔧 Scripts Disponibles

### 1. `backup.sh` - Crear backup

**Opciones:**
```bash
# Backup con directorio por defecto (./backups)
./backup.sh

# Backup con directorio personalizado
BACKUP_DIR=/ruta/personalizada ./backup.sh
```

**Proceso:**
1. Verifica que Docker esté corriendo
2. Verifica que los contenedores estén activos
3. Crea dump de la base de datos
4. Copia el código fuente (excluye node_modules, vendor, .git)
5. Copia los volúmenes Docker
6. Crea archivo de metadatos
7. Comprime todo en un solo archivo .tar.gz
8. Limpia backups antiguos (mantiene los últimos 10)

**Duración estimada:** 1-3 minutos

---

### 2. `restore.sh` - Restaurar backup

**Opciones:**
```bash
# Restaurar desde el directorio de backups
./restore.sh guardias_backup_20260310_120000.tar.gz

# Restaurar desde una ruta absoluta
./restore.sh /ruta/al/backup.tar.gz
```

**Proceso:**
1. Verifica Docker y argumentos
2. Solicita confirmación del usuario
3. Detiene todos los contenedores
4. Extrae el backup
5. Restaura el código fuente
6. Restaura la base de datos
7. Restaura los volúmenes Docker
8. Inicia los contenedores
9. Espera a que los servicios estén listos
10. Verifica la restauración

**Duración estimada:** 2-5 minutos

---

### 3. `manage_backups.sh` - Gestionar backups

**Comandos:**

```bash
# Listar backups disponibles
./manage_backups.sh list

# Verificar integridad de un backup
./manage_backups.sh verify guardias_backup_YYYYMMDD_HHMMSS.tar.gz

# Eliminar un backup
./manage_backups.sh delete guardias_backup_YYYYMMDD_HHMMSS.tar.gz

# Mostrar ayuda
./manage_backups.sh help
```

---

## 📅 Estrategia de Backups Recomendada

### Antes de cambios importantes
```bash
./backup.sh
```

### Automático (cron)
```bash
# Backup diario a las 3 AM
0 3 * * * cd /ruta/docker && ./backup.sh

# Backup semanal los domingos a las 2 AM
0 2 * * 0 cd /ruta/docker && ./backup.sh
```

### Retención
- El script mantiene automáticamente los últimos **10 backups**
- Los backups antiguos se eliminan automáticamente
- Se recomienda mantener backups de:
  - Antes de cada deploy a producción
  - Antes de migraciones de base de datos
  - Antes de cambios estructurales importantes

---

## 🔍 Archivos de Metadatos

Cada backup incluye un `metadata.json` con:

```json
{
    "backup_name": "guardias_backup_20260310_120000",
    "backup_date": "2026-03-10T12:00:00+00:00",
    "project_name": "guardias",
    "version": "1.0.0",
    "components": {
        "database": true,
        "source_code": true,
        "volumes": true
    },
    "notes": ""
}
```

---

## 🛠️ Solución de Problemas

### Error: "Docker no está corriendo"
```bash
# Verifica que Docker Desktop esté abierto
# En macOS, abre Docker Desktop desde Applications
```

### Error: "Archivo de backup no encontrado"
```bash
# Lista los backups disponibles
./manage_backups.sh list

# Usa el nombre exacto del archivo
./restore.sh guardias_backup_20260310_120000.tar.gz
```

### Error: "Permiso denegado" al crear backup
```bash
# Asegúrate de que los scripts sean ejecutables
chmod +x backup.sh restore.sh manage_backups.sh
```

### Restauración falla en medio del proceso
```bash
# Los contenedores pueden haber quedado detenidos
# Intenta reiniciar
docker-compose up -d

# Si hay problemas con la base de datos
docker-compose down -v  # Elimina volúmenes
docker-compose up -d database
# Espera 10 segundos
docker-compose up -d
```

### Backup ocupa demasiado espacio
```bash
# Lista backups y elimina los antiguos
./manage_backups.sh list
./manage_backups.sh delete guardias_backup_YYYYMMDD_HHMMSS.tar.gz

# El script automáticamente mantiene solo los últimos 10
```

---

## 📊 Ejemplos de Uso

### Escenario 1: Antes de actualizar el código

```bash
# 1. Crear backup
./backup.sh

# 2. Realizar cambios...

# 3. Si algo sale mal, restaurar
./restore.sh guardias_backup_20260310_120000.tar.gz
```

### Escenario 2: Migrar proyecto a otra máquina

```bash
# 1. En la máquina original
./backup.sh

# 2. Copiar el backup a la nueva máquina
scp backups/guardias_backup_20260310_120000.tar.gz usuario@nueva-maquina:/ruta/

# 3. En la nueva máquina
cd /ruta/docker
./restore.sh guardias_backup_20260310_120000.tar.gz
```

### Escenario 3: Probar cambios riesgosos

```bash
# 1. Crear backup antes de probar
./backup.sh

# 2. Realizar cambios y pruebas...

# 3. Si las pruebas fallan, restaurar
./restore.sh guardias_backup_20260310_120000.tar.gz
```

---

## 🔐 Consideraciones de Seguridad

- Los backups contienen **datos sensibles** (base de datos con usuarios, contraseñas hash)
- Almacena los backups en un lugar **seguro y privado**
- Considera **encriptar** los backups si los almacenas en la nube
- Elimina de forma segura los backups que ya no necesites

---

## 📈 Mejoras Futuras

- [ ] Encriptación de backups
- [ ] Backup incremental
- [ ] Subida automática a S3/Google Drive
- [ ] Programación integrada de backups
- [ ] Notificaciones de backup completado/fallido

---

*Documento generado para el Sistema de Guardias*
*Última actualización: 2026-03-10*
