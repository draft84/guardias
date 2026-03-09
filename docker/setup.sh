#!/bin/bash

# Script para configurar la base de datos y ejecutar migraciones

echo "🚀 Configurando base de datos y migraciones..."

# Esperar a que MySQL esté disponible
echo "⏳ Esperando a que MySQL esté disponible..."
sleep 10

# Crear base de datos
echo "📦 Creando base de datos..."
docker-compose exec -T backend php bin/console doctrine:database:create --if-not-exists

# Generar migraciones
echo "📝 Generando migraciones..."
docker-compose exec -T backend php bin/console make:migration --no-interaction

# Ejecutar migraciones
echo "⚙️  Ejecutando migraciones..."
docker-compose exec -T backend php bin/console doctrine:migrations:migrate --no-interaction

# Limpiar caché
echo "🧹 Limpiando caché..."
docker-compose exec -T backend php bin/console cache:clear

echo "✅ ¡Configuración completada!"
echo ""
echo "📋 Accesos:"
echo "   - Frontend: http://localhost:5173"
echo "   - Backend API: http://localhost:8000"
echo "   - phpMyAdmin: http://localhost:8080 (guardias_user / guardias_password)"
