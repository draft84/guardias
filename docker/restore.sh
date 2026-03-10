#!/bin/bash

# =============================================================================
# Script de Restauración del Sistema de Guardias
# =============================================================================
# Este script restaura un backup completo del proyecto incluyendo:
# - Base de datos MySQL
# - Código fuente del backend y frontend
# - Volúmenes Docker
# =============================================================================

set -e

# Configuración
PROJECT_NAME="guardias"
BACKUP_DIR="${BACKUP_DIR:-./backups}"

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Variables globales
BACKUP_FILE=""
BACKUP_NAME=""
TEMP_DIR=""

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

cleanup() {
    if [ -n "$TEMP_DIR" ] && [ -d "$TEMP_DIR" ]; then
        rm -rf "$TEMP_DIR"
        log_info "Directorio temporal limpiado"
    fi
}

trap cleanup EXIT

# Mostrar uso del script
show_usage() {
    echo "Uso: $0 <archivo_backup.tar.gz>"
    echo ""
    echo "Ejemplo:"
    echo "  $0 guardias_backup_20260310_120000.tar.gz"
    echo ""
    echo "Backups disponibles:"
    ls -lh "$BACKUP_DIR"/*.tar.gz 2>/dev/null || echo "  No hay backups disponibles"
    echo ""
}

# Verificar argumentos
check_arguments() {
    if [ $# -eq 0 ]; then
        log_error "No se especificó archivo de backup"
        show_usage
        exit 1
    fi
    
    BACKUP_FILE="$1"
    
    # Si es un nombre relativo, buscar en el directorio de backups
    if [ ! -f "$BACKUP_FILE" ]; then
        if [ -f "${BACKUP_DIR}/${BACKUP_FILE}" ]; then
            BACKUP_FILE="${BACKUP_DIR}/${BACKUP_FILE}"
        else
            log_error "Archivo de backup no encontrado: $BACKUP_FILE"
            exit 1
        fi
    fi
    
    # Extraer nombre del backup sin extensión
    BACKUP_NAME=$(basename "$BACKUP_FILE" .tar.gz)
    
    log_success "Archivo de backup identificado: $BACKUP_FILE"
}

# Verificar que Docker esté corriendo
check_docker() {
    if ! docker ps > /dev/null 2>&1; then
        log_error "Docker no está corriendo. Por favor inicia Docker Desktop."
        exit 1
    fi
    log_success "Docker verificado"
}

# Detener contenedores
stop_containers() {
    log_info "Deteniendo contenedores..."
    docker-compose down
    log_success "Contenedores detenidos"
}

# Extraer backup
extract_backup() {
    log_info "Extrayendo backup..."
    
    TEMP_DIR=$(mktemp -d)
    cd "$TEMP_DIR"
    tar -xzf "$BACKUP_FILE"
    
    # Encontrar el directorio extraído
    BACKUP_EXTRACTED_DIR=$(find . -maxdepth 1 -type d -name "${PROJECT_NAME}_backup_*" | head -1)
    
    if [ -z "$BACKUP_EXTRACTED_DIR" ]; then
        log_error "No se pudo encontrar el directorio del backup"
        exit 1
    fi
    
    log_success "Backup extraído en: $TEMP_DIR/$BACKUP_EXTRACTED_DIR"
}

# Restaurar código fuente
restore_source_code() {
    log_info "Restaurando código fuente..."
    
    SOURCE_DIR="${TEMP_DIR}/${BACKUP_EXTRACTED_DIR}/source"
    
    if [ ! -d "$SOURCE_DIR" ]; then
        log_warning "Directorio source no encontrado en el backup"
        return
    fi
    
    # Copiar backend
    if [ -d "$SOURCE_DIR/backend" ]; then
        log_info "Restaurando backend..."
        rm -rf ../backend
        cp -r "$SOURCE_DIR/backend" ../
        log_success "Backend restaurado"
    fi
    
    # Copiar frontend
    if [ -d "$SOURCE_DIR/frontend" ]; then
        log_info "Restaurando frontend..."
        rm -rf ../frontend
        cp -r "$SOURCE_DIR/frontend" ../
        log_success "Frontend restaurado"
    fi
    
    # Copiar archivos de configuración Docker
    if [ -f "$SOURCE_DIR/docker-compose.yml" ]; then
        cp "$SOURCE_DIR/docker-compose.yml" ./
    fi
    
    if [ -f "$SOURCE_DIR/Dockerfile.backend" ]; then
        cp "$SOURCE_DIR/Dockerfile.backend" ./
    fi
    
    if [ -f "$SOURCE_DIR/Dockerfile.frontend" ]; then
        cp "$SOURCE_DIR/Dockerfile.frontend" ./
    fi
    
    if [ -d "$SOURCE_DIR/nginx" ]; then
        cp -r "$SOURCE_DIR/nginx" ./
    fi
    
    # Copiar documentación
    for file in README.md ARQUITECTURA.md ESTADO.md INSTRUCCIONES.md QWEN.md; do
        if [ -f "$SOURCE_DIR/$file" ]; then
            cp "$SOURCE_DIR/$file" ../
        fi
    done
    
    log_success "Código fuente restaurado"
}

# Restaurar base de datos
restore_database() {
    log_info "Restaurando base de datos..."
    
    # Iniciar solo MySQL
    docker-compose up -d database
    
    log_info "Esperando a que MySQL esté listo..."
    sleep 10
    
    # Verificar si hay dump de base de datos
    DB_DUMP_FILE="${TEMP_DIR}/${BACKUP_EXTRACTED_DIR}/database.sql.gz"
    
    if [ ! -f "$DB_DUMP_FILE" ]; then
        log_warning "No se encontró dump de base de datos en el backup"
        return
    fi
    
    # Eliminar base de datos existente y crear nueva
    docker-compose exec -T database mysql \
        -u root \
        -proot_password \
        -e "DROP DATABASE IF EXISTS guardias; CREATE DATABASE guardias;"
    
    # Restaurar dump
    gunzip -c "$DB_DUMP_FILE" | docker-compose exec -T database mysql \
        -u guardias_user \
        -pguardias_password \
        guardias
    
    log_success "Base de datos restaurada"
}

# Restaurar volúmenes Docker
restore_volumes() {
    log_info "Restaurando volúmenes Docker..."

    VOLUMES_FILE="${TEMP_DIR}/${BACKUP_EXTRACTED_DIR}/volumes/mysql_data.tar.gz"

    if [ ! -f "$VOLUMES_FILE" ]; then
        log_warning "No se encontró backup de volúmenes en el backup"
        return
    fi

    # Eliminar volumen existente (probar con ambos nombres posibles)
    docker volume rm docker_mysql_data 2>/dev/null || true
    docker volume rm ${PROJECT_NAME}_mysql_data 2>/dev/null || true

    # Crear nuevo volumen y restaurar datos
    docker volume create docker_mysql_data

    docker run --rm \
        -v docker_mysql_data:/data \
        -v "$TEMP_DIR/${BACKUP_EXTRACTED_DIR}/volumes":/backup \
        alpine \
        tar -xzf /backup/mysql_data.tar.gz -C /data

    log_success "Volúmenes restaurados"
}

# Iniciar contenedores
start_containers() {
    log_info "Iniciando contenedores..."
    docker-compose up -d
    log_success "Contenedores iniciados"
}

# Esperar servicios
wait_for_services() {
    log_info "Esperando a que los servicios estén listos..."
    
    # Esperar backend
    log_info "Esperando backend..."
    for i in {1..30}; do
        if curl -s http://localhost:8000 > /dev/null 2>&1; then
            log_success "Backend listo"
            break
        fi
        sleep 1
    done
    
    # Esperar frontend
    log_info "Esperando frontend..."
    for i in {1..30}; do
        if curl -s http://localhost:5173 > /dev/null 2>&1; then
            log_success "Frontend listo"
            break
        fi
        sleep 1
    done
}

# Verificar restauración
verify_restoration() {
    log_info "Verificando restauración..."
    
    # Verificar servicios
    if docker-compose ps | grep -q "Up"; then
        log_success "Servicios corriendo correctamente"
    else
        log_error "Algunos servicios no están corriendo"
        return 1
    fi
    
    # Mostrar información del backup restaurado
    METADATA_FILE="${TEMP_DIR}/${BACKUP_EXTRACTED_DIR}/metadata.json"
    if [ -f "$METADATA_FILE" ]; then
        echo ""
        echo "=================================================="
        echo -e "${BLUE}INFORMACIÓN DEL BACKUP RESTAURADO${NC}"
        echo "=================================================="
        cat "$METADATA_FILE" | grep -E '"backup_name"|"backup_date"|"version"' | sed 's/[",]//g'
        echo "=================================================="
    fi
}

# Mostrar resumen
show_summary() {
    echo ""
    echo "=================================================="
    echo -e "${GREEN}RESTAURACIÓN COMPLETADA EXITOSAMENTE${NC}"
    echo "=================================================="
    echo "Backup restaurado: ${BACKUP_NAME}"
    echo "Fecha: $(date)"
    echo ""
    echo "Servicios disponibles:"
    echo "  - Frontend: http://localhost:5173"
    echo "  - Backend API: http://localhost:8000"
    echo "  - phpMyAdmin: http://localhost:18080"
    echo ""
    echo "Credenciales de acceso:"
    echo "  - Email: admin@example.com"
    echo "  - Password: admin123"
    echo "=================================================="
}

# Confirmación del usuario
confirm_restoration() {
    echo ""
    echo -e "${YELLOW}⚠️  ADVERTENCIA${NC}"
    echo "=================================================="
    echo "Esta operación:"
    echo "  1. Detendrá todos los contenedores"
    echo "  2. Eliminará la base de datos actual"
    echo "  3. Reemplazará el código fuente"
    echo "  4. Restaurará los volúmenes Docker"
    echo ""
    echo "Backup a restaurar: ${BACKUP_NAME}"
    echo ""
    read -p "¿Estás seguro de continuar? (y/N): " -n 1 -r
    echo ""
    
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        log_info "Restauración cancelada"
        exit 0
    fi
}

# Función principal
main() {
    echo ""
    echo "=================================================="
    echo -e "${BLUE}SISTEMA DE RESTAURACIÓN - GUARDIAS${NC}"
    echo "=================================================="
    echo ""
    
    check_arguments "$@"
    check_docker
    confirm_restoration
    stop_containers
    extract_backup
    restore_source_code
    restore_database
    restore_volumes
    start_containers
    wait_for_services
    verify_restoration
    show_summary
}

# Ejecutar script
main "$@"
