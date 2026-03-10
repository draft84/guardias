# 📋 Guía Rápida - Sistema de Backup

## ✅ Estado del Sistema

**Estado:** ✅ **COMPLETADO Y FUNCIONANDO**

---

## 🚀 Comandos Esenciales

### 1️⃣ Crear Backup

```bash
cd docker
./backup.sh
```

**Crea:**
- ✅ Dump de base de datos MySQL
- ✅ Copia del código fuente (backend + frontend)
- ✅ Copia de volúmenes Docker
- ✅ Metadatos del backup
- ✅ Archivo comprimido en `docker/backups/`

**Duración:** ~1-2 minutos

---

### 2️⃣ Listar Backups

```bash
./manage_backups.sh list
```

**Muestra:**
- Nombre del archivo
- Tamaño
- Fecha de creación

---

### 3️⃣ Verificar Backup

```bash
./manage_backups.sh verify guardias_backup_YYYYMMDD_HHMMSS.tar.gz
```

**Verifica:**
- Integridad del archivo .tar.gz
- Muestra contenido
- Extrae metadatos

---

### 4️⃣ Restaurar Backup

```bash
./restore.sh guardias_backup_YYYYMMDD_HHMMSS.tar.gz
```

**⚠️ ADVERTENCIA:** Este comando:
1. Detiene todos los contenedores
2. Elimina la base de datos actual
3. Reemplaza el código fuente
4. Restaura volúmenes Docker
5. Inicia los contenedores

**Duración:** ~2-4 minutos

---

### 5️⃣ Eliminar Backup

```bash
./manage_backups.sh delete guardias_backup_YYYYMMDD_HHMMSS.tar.gz
```

**Solicita confirmación antes de eliminar**

---

## 📁 Ubicación de Backups

```
docker/backups/
└── guardias_backup_20260310_091937.tar.gz  ← Ejemplo
```

---

## 🎯 Cuándo Hacer Backup

| Situación | Prioridad |
|-----------|-----------|
| Antes de deploy a producción | 🔴 **OBLIGATORIO** |
| Antes de migraciones de BD | 🔴 **OBLIGATORIO** |
| Antes de cambios estructurales | 🔴 **OBLIGATORIO** |
| Semanal (respaldo preventivo) | 🟡 RECOMENDADO |
| Después de cambios menores | 🟢 OPCIONAL |

---

## 📊 Contenido del Backup

```
guardias_backup_YYYYMMDD_HHMMSS/
├── database.sql.gz          ← Base de datos (comprimida)
├── source/                  ← Código fuente
│   ├── backend/
│   ├── frontend/
│   ├── docker-compose.yml
│   ├── Dockerfile.backend
│   ├── Dockerfile.frontend
│   ├── nginx/
│   └── *.md (documentación)
├── volumes/
│   └── mysql_data.tar.gz    ← Volumen MySQL
└── metadata.json            ← Metadatos
```

---

## 🔧 Solución de Problemas

| Problema | Solución |
|----------|----------|
| "Docker no está corriendo" | Abre Docker Desktop |
| "Permiso denegado" | `chmod +x *.sh` |
| "Archivo no encontrado" | Usa `./manage_backups.sh list` para ver disponibles |
| Backup falla a mitad | Reintenta, verifica espacio en disco |

---

## 📞 Comandos de Emergencia

### Verificar servicios después de restaurar
```bash
docker-compose ps
```

### Ver logs en tiempo real
```bash
docker-compose logs -f
```

### Reiniciar todo si algo falla
```bash
docker-compose down
docker-compose up -d
```

---

## 📈 Mejores Prácticas

1. ✅ **Siempre** crea backup antes de cambios importantes
2. ✅ Verifica el backup después de crearlo
3. ✅ Mantén copias en ubicación externa (nube, otro disco)
4. ✅ Nombra los backups con notas si es necesario
5. ✅ Elimina backups antiguos manualmente si necesitas espacio

---

## 🔐 Seguridad

- Los backups contienen **datos sensibles**
- Almacena en lugar **seguro y privado**
- Considera **encriptar** para almacenamiento en la nube
- **No compartas** archivos de backup sin sanitizar

---

## 📞 Soporte

Para más detalles, consulta:
- `BACKUPS.md` - Documentación completa
- `README.md` - Documentación del proyecto
- `ESTADO.md` - Estado y credenciales

---

*Guía rápida - Sistema de Guardias*
*Última actualización: 2026-03-10*
