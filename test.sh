#!/bin/bash
echo "=== PROBANDO API DE TAREAS ==="
echo ""

# Login
LOGIN=$(curl -s -X POST http://localhost:8000/api/auth/login -H "Content-Type: application/json" -d '{"email":"admin@example.com","password":"admin123"}')
echo "Login: $LOGIN" | head -c 100
echo "..."
echo ""

# Extraer token (formato simple)
TOKEN=$(echo "$LOGIN" | sed 's/.*"token":"\([^"]*\)".*/\1/')

# Obtener departamentos
DEPTS=$(curl -s -X GET http://localhost:8000/api/departments -H "Authorization: Bearer $TOKEN")
echo "Departamentos: $DEPTS" | head -c 200
echo "..."
echo ""

# Obtener turnos
SHIFTS=$(curl -s -X GET http://localhost:8000/api/shifts -H "Authorization: Bearer $TOKEN")
echo "Turnos: $SHIFTS" | head -c 200
echo "..."
echo ""

# Extraer IDs
DEPT_ID=$(echo "$DEPTS" | grep -o '"id":"[^"]*"' | head -1 | sed 's/"id":"//;s/"//')
SHIFT_ID=$(echo "$SHIFTS" | grep -o '"id":"[^"]*"' | head -1 | sed 's/"id":"//;s/"//')

echo "DEPT_ID: $DEPT_ID"
echo "SHIFT_ID: $SHIFT_ID"
echo ""

# Crear tarea
echo "=== CREANDO TAREA ==="
curl -s -X POST http://localhost:8000/api/tasks \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d "{
    \"title\": \"Test $(date +%s)\",
    \"description\": \"Descripcion de prueba\",
    \"startTime\": \"08:00\",
    \"endTime\": \"16:00\",
    \"departmentId\": \"$DEPT_ID\",
    \"shiftId\": \"$SHIFT_ID\",
    \"observations\": \"Test\"
  }"
echo ""
