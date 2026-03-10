#!/bin/bash

# =============================================================================
# Script de Gestión de Backups del Sistema de Guardias
# =============================================================================
# Permite listar, verificar y eliminar backups existentes
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
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# Funciones de utilidad
log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[OK]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Listar backups disponibles
list_backups() {
    echo ""
    echo "=================================================="
    echo -e "${CYAN}BACKUPS DISPONIBLES${NC}"
    echo "=================================================="
    echo ""
    
    if [ ! -d "$BACKUP_DIR" ]; then
        log_info "No hay directorio de backups"
        return
    fi
    
    BACKUP_COUNT=0
    TOTAL_SIZE=0
    
    printf "%-50s %-15s %-20s\n" "NOMBRE" "TAMAÑO" "FECHA"
    printf "%-50s %-15s %-20s\n" "------" "------" "-----"
    
    for backup in $(ls -t "$BACKUP_DIR"/*.tar.gz 2>/dev/null); do
        if [ -f "$backup" ]; then
            BACKUP_NAME=$(basename "$backup")
            BACKUP_SIZE=$(du -h "$backup" | cut -f1)
            BACKUP_DATE=$(stat -f "%Sm" -t "%Y-%m-%d %H:%M" "$backup" 2>/dev/null || stat -c "%y" "$backup" 2>/dev/null | cut -d' ' -f1,2 | cut -d'.' -f1)
            
            printf "%-50s %-15s %-20s\n" "$BACKUP_NAME" "$BACKUP_SIZE" "$BACKUP_DATE"
            
            ((BACKUP_COUNT++))
        fi
    done
    
    if [ $BACKUP_COUNT -eq 0 ]; then
        log_info "No hay backups disponibles"
    else
        echo ""
        echo "Total: $BACKUP_COUNT backup(s)"
    fi
    
    echo "=================================================="
    echo ""
}

# Verificar integridad de un backup
verify_backup() {
    if [ $# -eq 0 ]; then
        log_error "No se especificó archivo de backup"
        echo "Uso: $0 verify <archivo_backup.tar.gz>"
        exit 1
    fi
    
    BACKUP_FILE="$1"
    
    if [ ! -f "$BACKUP_FILE" ]; then
        if [ -f "${BACKUP_DIR}/${BACKUP_FILE}" ]; then
            BACKUP_FILE="${BACKUP_DIR}/${BACKUP_FILE}"
        else
            log_error "Archivo no encontrado: $BACKUP_FILE"
            exit 1
        fi
    fi
    
    echo ""
    echo "=================================================="
    echo -e "${CYAN}VERIFICANDO BACKUP${NC}"
    echo "=================================================="
    echo ""
    
    log_info "Verificando integridad del archivo..."
    
    # Verificar que sea un tar.gz válido
    if tar -tzf "$BACKUP_FILE" > /dev/null 2>&1; then
        log_success "Archivo tar.gz válido"
    else
        log_error "Archivo corrupto o inválido"
        exit 1
    fi
    
    # Mostrar contenido
    log_info "Contenido del backup:"
    echo ""
    tar -tzf "$BACKUP_FILE" | head -20
    
    FILE_COUNT=$(tar -tzf "$BACKUP_FILE" | wc -l)
    if [ $FILE_COUNT -gt 20 ]; then
        echo "... ($((FILE_COUNT - 20)) archivos más)"
    fi
    
    # Mostrar metadata si existe
    echo ""
    log_info "Metadatos:"
    METADATA_FILE=$(tar -tzf "$BACKUP_FILE" | grep "metadata.json" | head -1)
    if [ -n "$METADATA_FILE" ]; then
        tar -xzf "$BACKUP_FILE" -O "$METADATA_FILE" 2>/dev/null | grep -E '"backup_name"|"backup_date"|"version"' | sed 's/[",]//g'
    fi
    
    echo ""
    echo "=================================================="
    log_success "Backup verificado correctamente"
    echo "=================================================="
}

# Eliminar backup
delete_backup() {
    if [ $# -eq 0 ]; then
        log_error "No se especificó archivo de backup"
        echo "Uso: $0 delete <archivo_backup.tar.gz>"
        exit 1
    fi
    
    BACKUP_FILE="$1"
    
    if [ ! -f "$BACKUP_FILE" ]; then
        if [ -f "${BACKUP_DIR}/${BACKUP_FILE}" ]; then
            BACKUP_FILE="${BACKUP_DIR}/${BACKUP_FILE}"
        else
            log_error "Archivo no encontrado: $BACKUP_FILE"
            exit 1
        fi
    fi
    
    read -p "¿Estás seguro de eliminar $(basename "$BACKUP_FILE")? (y/N): " -n 1 -r
    echo ""
    
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        rm "$BACKUP_FILE"
        log_success "Backup eliminado"
    else
        log_info "Eliminación cancelada"
    fi
}

# Mostrar uso del script
show_usage() {
    echo ""
    echo "=================================================="
    echo -e "${BLUE}GESTIÓN DE BACKUPS - GUARDIAS${NC}"
    echo "=================================================="
    echo ""
    echo "Uso: $0 <comando> [argumentos]"
    echo ""
    echo "Comandos disponibles:"
    echo "  list              Listar backups disponibles"
    echo "  verify <archivo>  Verificar integridad de un backup"
    echo "  delete <archivo>  Eliminar un backup"
    echo "  help              Mostrar esta ayuda"
    echo ""
    echo "Ejemplos:"
    echo "  $0 list"
    echo "  $0 verify guardias_backup_20260310_120000.tar.gz"
    echo "  $0 delete guardias_backup_20260310_120000.tar.gz"
    echo ""
    echo "=================================================="
}

# Función principal
main() {
    case "$1" in
        list)
            list_backups
            ;;
        verify)
            shift
            verify_backup "$@"
            ;;
        delete)
            shift
            delete_backup "$@"
            ;;
        help|--help|-h)
            show_usage
            ;;
        *)
            show_usage
            ;;
    esac
}

# Ejecutar script
main "$@"
