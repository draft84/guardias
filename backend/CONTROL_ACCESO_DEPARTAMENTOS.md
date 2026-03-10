# 🔒 Control de Acceso por Departamentos

## 📋 Descripción

Se ha implementado un sistema de control de acceso basado en departamentos para que cada usuario solo pueda ver y gestionar los registros de su propio departamento.

---

## ✅ Cambios Realizados

### 1. Archivos Nuevos

| Archivo | Propósito |
|---------|-----------|
| `backend/src/Traits/CurrentUserTrait.php` | Trait para obtener información del usuario autenticado |

### 2. Servicios Modificados

#### GuardService (`backend/src/Service/GuardService.php`)
- ✅ `getAllGuards()`: Filtra por departamento (ADMIN ve todas)
- ✅ `getActiveGuards()`: Filtra por departamento (ADMIN ve todas)
- ✅ `getGuardById()`: Verifica que la guardia pertenezca al departamento del usuario

#### DepartmentService (`backend/src/Service/DepartmentService.php`)
- ✅ `getAllDepartments()`: ADMIN ve todos, otros solo el suyo
- ✅ `getActiveDepartments()`: ADMIN ve todos, otros solo el suyo
- ✅ `getDepartmentById()`: Verifica permisos por departamento

#### UserService (`backend/src/Service/UserService.php`)
- ✅ `getAllUsers()`: Filtra por departamento (ADMIN ve todos)
- ✅ `getActiveUsers()`: Filtra por departamento (ADMIN ve todos)
- ✅ `getUserById()`: Verifica que el usuario pertenezca al mismo departamento

### 3. Controladores Modificados

#### GuardController (`backend/src/Controller/Api/GuardController.php`)
- ✅ Ahora usa `GuardService` en lugar de `GuardRepository`
- ✅ Método `get()` actualizado para usar el servicio filtrado

#### DepartmentController (`backend/src/Controller/Api/DepartmentController.php`)
- ✅ Ahora usa `DepartmentService` en lugar de `DepartmentRepository`
- ✅ Método `get()` actualizado para usar el servicio filtrado

#### UserController (`backend/src/Controller/Api/UserController.php`)
- ✅ Ahora usa `UserService` en lugar de `UserRepository`
- ✅ Métodos `list()`, `listActive()`, `get()` actualizados

#### AssignmentController (`backend/src/Controller/Api/AssignmentController.php`)
- ✅ Nuevo método `filterByDepartment()`: Filtra asignaciones por departamento
- ✅ Método `list()`: Filtra asignaciones
- ✅ Método `calendar()`: Filtra eventos del calendario

---

## 🔐 Reglas de Acceso

### Usuario ADMIN (`ROLE_ADMIN`)
- ✅ Puede ver **todos** los departamentos
- ✅ Puede ver **todas** las guardias
- ✅ Puede ver **todos** los usuarios
- ✅ Puede ver **todas** las asignaciones

### Usuario Normal (`ROLE_USER`)
- ✅ Solo puede ver **su departamento**
- ✅ Solo puede ver **guardias de su departamento**
- ✅ Solo puede ver **usuarios de su departamento**
- ✅ Solo puede ver **asignaciones de guardias de su departamento**

---

## 🧪 Pruebas Realizadas

### Prueba 1: Admin ve todos los departamentos
```bash
# Login como admin
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@example.com","password":"admin123"}'

# Obtener departamentos (debe devolver 3)
curl -X GET http://localhost:8000/api/departments \
  -H "Authorization: Bearer TOKEN_ADMIN"
```
**Resultado:** ✅ Admin ve los 3 departamentos (Tecnología, Recursos Humanos, Operaciones)

---

### Prueba 2: Usuario de Operaciones ve solo su departamento
```bash
# Login como usuario de Operaciones
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"ops_user1@example.com","password":"user123"}'

# Obtener departamentos (debe devolver 1)
curl -X GET http://localhost:8000/api/departments \
  -H "Authorization: Bearer TOKEN_USER"
```
**Resultado:** ✅ Usuario ve solo "Operaciones"

---

### Prueba 3: Usuario de Operaciones ve guardias de su departamento
```bash
# Obtener guardias (debe devolver 3)
curl -X GET http://localhost:8000/api/guards \
  -H "Authorization: Bearer TOKEN_USER_OPS"
```
**Resultado:** ✅ Usuario de Operaciones ve las 3 guardias (todas son de Operaciones)

---

### Prueba 4: Usuario de Tecnología no ve guardias de otros departamentos
```bash
# Login como usuario de Tecnología
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"tech_user1@example.com","password":"user123"}'

# Obtener guardias (debe devolver 0)
curl -X GET http://localhost:8000/api/guards \
  -H "Authorization: Bearer TOKEN_USER_TECH"
```
**Resultado:** ✅ Usuario de Tecnología no ve guardias (no hay guardias de Tecnología)

---

## 📁 Usuarios de Prueba

| Email | Password | Rol | Departamento |
|-------|----------|-----|--------------|
| `admin@example.com` | `admin123` | ADMIN | Tecnología |
| `manager@example.com` | `manager123` | MANAGER | Operaciones |
| `tech_user1@example.com` | `user123` | USER | Tecnología |
| `tech_user2@example.com` | `user123` | USER | Tecnología |
| `hr_user1@example.com` | `user123` | USER | Recursos Humanos |
| `hr_user2@example.com` | `user123` | USER | Recursos Humanos |
| `ops_user1@example.com` | `user123` | USER | Operaciones |
| `ops_user2@example.com` | `user123` | USER | Operaciones |

---

## 🚀 Cómo Usar

### Desde el Frontend

El sistema filtra automáticamente los datos según el usuario autenticado. No se necesitan cambios en el frontend.

**Ejemplo:**
1. Usuario `ops_user1@example.com` inicia sesión
2. El frontend llama a `GET /api/departments`
3. El backend devuelve solo el departamento "Operaciones"
4. El frontend muestra solo las guardias, usuarios y asignaciones de Operaciones

---

## ⚠️ Consideraciones

### MANAGER
Actualmente los usuarios con rol `ROLE_MANAGER` tienen el mismo acceso que los usuarios normales (solo ven su departamento). Si se desea que los MANAGER puedan ver más, se debe actualizar la lógica en los servicios.

### Departamentos Jerárquicos
Si se implementa una jerarquía de departamentos (departamentos padre e hijos), se deberá actualizar la lógica para que los usuarios puedan ver también los departamentos hijos.

### Asignaciones Cruzadas
Si un usuario de un departamento puede ser asignado a una guardia de otro departamento, se deberá ajustar la lógica de filtrado en `AssignmentController`.

---

## 📝 Código de Ejemplo

### Servicio con Filtrado

```php
public function getAllGuards(): array
{
    $user = $this->security->getUser();
    
    // Si es ADMIN, devuelve todas las guardias
    if ($user instanceof User && in_array('ROLE_ADMIN', $user->getRoles(), true)) {
        return $this->guardRepository->findAll();
    }
    
    // Si no, solo las del departamento del usuario
    $department = $user?->getDepartment();
    if (!$department) {
        return [];
    }
    
    return $this->guardRepository->findBy(['department' => $department]);
}
```

### Controlador con Filtrado

```php
private function filterByDepartment(array $assignments): array
{
    $user = $this->getUser();
    
    // ADMIN puede ver todas las asignaciones
    if ($user instanceof User && in_array('ROLE_ADMIN', $user->getRoles(), true)) {
        return $assignments;
    }
    
    // Los demás solo ven asignaciones de su departamento
    $userDepartment = $user?->getDepartment();
    
    if (!$userDepartment) {
        return [];
    }
    
    return array_filter($assignments, function (GuardAssignment $assignment) use ($userDepartment) {
        $guard = $assignment->getGuard();
        return $guard && $guard->getDepartment() === $userDepartment;
    });
}
```

---

## 🔍 Verificación

Para verificar que el sistema funciona correctamente:

```bash
# Limpiar caché
docker-compose exec backend php bin/console cache:clear

# Probar con admin
curl -X GET http://localhost:8000/api/departments \
  -H "Authorization: Bearer TOKEN_ADMIN" | jq '.departments | length'
# Debe devolver: 3

# Probar con usuario normal
curl -X GET http://localhost:8000/api/departments \
  -H "Authorization: Bearer TOKEN_USER" | jq '.departments | length'
# Debe devolver: 1
```

---

*Documento generado para el Sistema de Guardias*
*Última actualización: 2026-03-10*
