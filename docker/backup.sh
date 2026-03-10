#!/bin/bash

# =============================================================================
# Script de Backup del Sistema de Guardias
# =============================================================================
# Este script crea backups completos del proyecto incluyendo:
# - Base de datos MySQL (dump SQL)
# - Código fuente del backend y frontend
# - Archivos de configuración
# =============================================================================

set -e

# Configuración
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
PROJECT_NAME="guardias"
BACKUP_DIR="${BACKUP_DIR:-${SCRIPT_DIR}/backups}"
DATE=$(date +"%Y%m%d_%H%M%S")
BACKUP_NAME="${PROJECT_NAME}_backup_${DATE}"
BACKUP_PATH="${BACKUP_DIR}/${BACKUP_NAME}"

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Funciones de utilidad
log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[OK]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Verificar que Docker esté corriendo
check_docker() {
    if ! docker ps > /dev/null 2>&1; then
        log_error "Docker no está corriendo. Por favor inicia Docker Desktop."
        exit 1
    fi
    log_success "Docker verificado"
}

# Verificar que los contenedores estén activos
check_containers() {
    if ! docker-compose ps | grep -q "guardias_mysql.*Up"; then
        log_error "El contenedor de MySQL no está corriendo"
        exit 1
    fi
    log_success "Contenedores verificados"
}

# Crear directorio de backups
create_backup_dir() {
    if [ ! -d "$BACKUP_DIR" ]; then
        mkdir -p "$BACKUP_DIR"
        log_info "Directorio de backups creado: $BACKUP_DIR"
    fi
}

# Crear backup de la base de datos
backup_database() {
    log_info "Creando backup de la base de datos..."
    
    DB_DUMP_FILE="${BACKUP_PATH}/database.sql"
    
    # Usar root para evitar problemas de permisos con mysqldump
    docker-compose exec -T database mysqldump \
        -u root \
        -proot_password \
        --databases guardias \
        --single-transaction \
        --quick \
        --skip-lock-tables \
        > "$DB_DUMP_FILE" 2>/dev/null || \
    docker-compose exec -T database mysqldump \
        -u root \
        -proot_password \
        guardias \
        --single-transaction \
        --quick \
        > "$DB_DUMP_FILE"
    
    # Comprimir el dump
    gzip "$DB_DUMP_FILE"
    
    log_success "Backup de base de datos creado: ${DB_DUMP_FILE}.gz"
}

# Crear backup del código fuente
backup_source_code() {
    log_info "Creando backup del código fuente..."
    
    SOURCE_DIR="${BACKUP_PATH}/source"
    mkdir -p "$SOURCE_DIR"
    
    # Copiar backend
    if [ -d "../backend" ]; then
        cp -r ../backend "$SOURCE_DIR/"
        log_success "Backend copiado"
    else
        log_warning "Directorio backend no encontrado"
    fi
    
    # Copiar frontend
    if [ -d "../frontend" ]; then
        cp -r ../frontend "$SOURCE_DIR/"
        log_success "Frontend copiado"
    else
        log_warning "Directorio frontend no encontrado"
    fi
    
    # Copiar configuración Docker
    if [ -f "docker-compose.yml" ]; then
        cp docker-compose.yml "$SOURCE_DIR/"
    fi
    
    # Copiar Dockerfiles
    if [ -f "Dockerfile.backend" ]; then
        cp Dockerfile.backend "$SOURCE_DIR/"
    fi
    
    if [ -f "Dockerfile.frontend" ]; then
        cp Dockerfile.frontend "$SOURCE_DIR/"
    fi
    
    # Copiar configuración de nginx
    if [ -d "nginx" ]; then
        cp -r nginx "$SOURCE_DIR/"
    fi
    
    # Copiar archivos de documentación
    for file in ../README.md ../ARQUITECTURA.md ../ESTADO.md ../INSTRUCCIONES.md ../QWEN.md; do
        if [ -f "$file" ]; then
            cp "$file" "$SOURCE_DIR/"
        fi
    done
    
    # Excluir node_modules, vendor y otros directorios grandes
    find "$SOURCE_DIR" -type d -name "node_modules" -exec rm -rf {} + 2>/dev/null || true
    find "$SOURCE_DIR" -type d -name "vendor" -exec rm -rf {} + 2>/dev/null || true
    find "$SOURCE_DIR" -type d -name ".git" -exec rm -rf {} + 2>/dev/null || true
    find "$SOURCE_DIR" -type d -name "var" -exec rm -rf {} + 2>/dev/null || true
    
    log_success "Código fuente copiado"
}

# Crear backup de volúmenes Docker
backup_volumes() {
    log_info "Creando backup de volúmenes Docker..."
    
    VOLUMES_DIR="${BACKUP_PATH}/volumes"
    mkdir -p "$VOLUMES_DIR"
    
    # Backup del volumen de MySQL (probar con ambos nombres posibles)
    MYSQL_VOLUME=""
    if docker volume inspect docker_mysql_data > /dev/null 2>&1; then
        MYSQL_VOLUME="docker_mysql_data"
    elif docker volume inspect ${PROJECT_NAME}_mysql_data > /dev/null 2>&1; then
        MYSQL_VOLUME="${PROJECT_NAME}_mysql_data"
    fi
    
    if [ -n "$MYSQL_VOLUME" ]; then
        docker run --rm \
            -v ${MYSQL_VOLUME}:/data:ro \
            -v "$VOLUMES_DIR":/backup \
            alpine \
            tar -czf /backup/mysql_data.tar.gz -C /data .
        log_success "Volumen MySQL copiado"
    else
        log_warning "Volumen MySQL no encontrado"
    fi
}

# Crear archivo de metadatos
create_metadata() {
    log_info "Creando archivo de metadatos..."
    
    cat > "${BACKUP_PATH}/metadata.json" << EOF
{
    "backup_name": "${BACKUP_NAME}",
    "backup_date": "$(date -Iseconds)",
    "project_name": "${PROJECT_NAME}",
    "version": "1.0.0",
    "components": {
        "database": true,
        "source_code": true,
        "volumes": true
    },
    "docker_containers": {
        "backend": "$(docker-compose ps -q backend 2>/dev/null || echo 'N/A')",
        "frontend": "$(docker-compose ps -q frontend 2>/dev/null || echo 'N/A')",
        "database": "$(docker-compose ps -q database 2>/dev/null || echo 'N/A')"
    },
    "notes": ""
}
EOF
    
    log_success "Metadatos creados"
}

# Comprimir backup completo
compress_backup() {
    log_info "Comprimiendo backup completo..."
    
    cd "$BACKUP_DIR"
    tar -czf "${BACKUP_NAME}.tar.gz" "${BACKUP_NAME}"
    rm -rf "${BACKUP_NAME}"
    
    log_success "Backup comprimido: ${BACKUP_DIR}/${BACKUP_NAME}.tar.gz"
}

# Limpiar backups antiguos (mantener últimos 10)
cleanup_old_backups() {
    log_info "Limpiando backups antiguos..."
    
    cd "$BACKUP_DIR" 2>/dev/null || return 0
    
    ls -t ${PROJECT_NAME}_backup_*.tar.gz 2>/dev/null | tail -n +11 | xargs -r rm
    
    log_success "Backups antiguos limpiados (se mantienen los últimos 10)"
}

# Mostrar resumen del backup
show_summary() {
    echo ""
    echo "=================================================="
    echo -e "${GREEN}BACKUP COMPLETADO EXITOSAMENTE${NC}"
    echo "=================================================="
    echo "Nombre: ${BACKUP_NAME}.tar.gz"
    echo "Ubicación: ${BACKUP_DIR}/${BACKUP_NAME}.tar.gz"
    echo "Tamaño: $(du -h "${BACKUP_DIR}/${BACKUP_NAME}.tar.gz" | cut -f1)"
    echo "Fecha: $(date)"
    echo "=================================================="
    echo ""
    echo "Para restaurar este backup:"
    echo "  ./restore.sh ${BACKUP_NAME}.tar.gz"
    echo ""
}

# Función principal
main() {
    echo ""
    echo "=================================================="
    echo -e "${BLUE}SISTEMA DE BACKUP - GUARDIAS${NC}"
    echo "=================================================="
    echo ""
    
    check_docker
    check_containers
    create_backup_dir
    
    # Crear directorio temporal para el backup
    mkdir -p "$BACKUP_PATH"
    
    backup_database
    backup_source_code
    backup_volumes
    create_metadata
    compress_backup
    cleanup_old_backups
    
    show_summary
}

# Ejecutar script
main "$@"
