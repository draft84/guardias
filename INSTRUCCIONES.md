# 🚀 Sistema de Guardias - Instrucciones de Continuación

## ✅ Lo que ya está configurado

### Backend (Symfony 7.2)
- ✅ Proyecto Symfony creado en `/backend`
- ✅ Doctrine ORM configurado con **MySQL 8.0**
- ✅ Security bundle configurado
- ✅ JWT Authentication (Lexik) configurado
- ✅ CORS habilitado para frontend
- ✅ Entidades creadas:
  - `Department` - Departamentos
  - `User` - Usuarios con autenticación
  - `Guard` - Tipos de guardia
  - `Shift` - Turnos
  - `GuardAssignment` - Asignaciones de guardia
  - `ShiftSwapRequest` - Solicitudes de cambio
- ✅ Repositorios creados para cada entidad
- ✅ **Controladores API REST creados:**
  - `AuthController` - Login, logout, me, refresh
  - `DepartmentController` - CRUD departamentos
  - `UserController` - CRUD usuarios
  - `GuardController` - CRUD guardias
  - `ShiftController` - CRUD turnos
  - `AssignmentController` - CRUD asignaciones, calendario, cambios
- ✅ **Servicios creados:**
  - `GuardService` - Lógica de guardias
  - `ShiftService` - Lógica de turnos y cambios
  - `DepartmentService` - Lógica de departamentos
  - `UserService` - Lógica de usuarios
  - `CalendarService` - Lógica de calendario

### Frontend (Vue.js 3 + Vite + PrimeVue)
- ✅ Proyecto Vue.js creado en `/frontend`
- ✅ **Vue Router configurado** con rutas protegidas
- ✅ **Pinia stores creados:**
  - `auth.store` - Autenticación
  - `department.store` - Departamentos
  - `user.store` - Usuarios
  - `guard.store` - Guardias
  - `shift.store` - Turnos y asignaciones
- ✅ **Servicios API creados:**
  - `api.js` - Configuración de Axios
  - `authService.js` - Autenticación
  - `departmentService.js` - Departamentos
  - `userService.js` - Usuarios
  - `guardService.js` - Guardias
  - `shiftService.js` - Turnos y asignaciones
- ✅ **Layouts creados:**
  - `DashboardLayout.vue` - Layout principal con sidebar
- ✅ **Vistas creadas:**
  - `LoginView.vue` - Login
  - `DashboardView.vue` - Dashboard con estadísticas
  - `DepartmentsView.vue` - CRUD departamentos
  - `UsersView.vue` - CRUD usuarios
  - `GuardsView.vue` - CRUD guardias
  - `ShiftsView.vue` - CRUD turnos
  - `CalendarView.vue` - Calendario FullCalendar
  - `SettingsView.vue` - Configuración
  - `NotFoundView.vue` - 404

### Docker
- ✅ `docker-compose.yml` configurado con **MySQL + phpMyAdmin**
- ✅ `Dockerfile.backend` para Symfony
- ✅ `Dockerfile.frontend` para Vue.js
- ✅ Configuración de Nginx

---

## ⚠️ PASO CRÍTICO: Instalar dependencias del Frontend

**PROBLEMA CONOCIDO**: El caché de npm tiene problemas de permisos.

**SOLUCIÓN**: Ejecuta el siguiente comando en tu terminal:

```bash
# 1. Corregir permisos de npm (requiere sudo)
sudo chown -R $(whoami) ~/.npm

# 2. Instalar dependencias del frontend
cd frontend
npm install
```

---

## 📋 Próximos Pasos

### 1. Levantar Docker con MySQL y phpMyAdmin

```bash
cd docker
docker-compose up -d

# Verificar que los contenedores estén corriendo
docker-compose ps

# Ver logs
docker-compose logs -f
```

**Accesos:**
- **phpMyAdmin**: http://localhost:8080
  - Usuario: `guardias_user`
  - Password: `guardias_password`
- **MySQL**: localhost:3306

### 2. Generar migraciones de la base de datos

```bash
cd backend

# Crear la base de datos
php bin/console doctrine:database:create

# Generar migraciones
php bin/console make:migration

# Ejecutar migraciones
php bin/console doctrine:migrations:migrate
```

### 3. Crear Fixtures (Datos de prueba)

```bash
cd backend
composer require --dev doctrine/doctrine-fixtures-bundle
php bin/console make:fixtures
php bin/console doctrine:fixtures:load
```

### 4. Probar la API

```bash
# Probar endpoint de departamentos (requiere token JWT)
curl -X GET http://localhost:8000/api/departments \
  -H "Authorization: Bearer TU_TOKEN_JWT"
```

### 5. Configurar Frontend

En `frontend/`:

#### Instalar dependencias adicionales
```bash
npm install vue-router pinia axios primevue @fullcalendar/vue3 date-fns
```

#### Estructura de carpetas a crear:
```
src/
├── components/
│   ├── common/
│   ├── auth/
│   ├── departments/
│   ├── users/
│   ├── guards/
│   └── shifts/
├── composables/
├── layouts/
├── router/
├── services/
├── stores/
├── views/
└── utils/
```

---

## 🏃 Comandos Útiles

### Backend
```bash
# Servidor de desarrollo
cd backend
php -S localhost:8000 -t public

# Consola Symfony
php bin/console

# Verificar estado de la BD
php bin/console doctrine:schema:validate

# Limpiar caché
php bin/console cache:clear
```

### Frontend
```bash
# Servidor de desarrollo
cd frontend
npm run dev

# Build de producción
npm run build

# Tests
npm run test
```

### Docker
```bash
# Iniciar todos los servicios
docker-compose up -d

# Ver logs
docker-compose logs -f

# Detener
docker-compose down
```

---

## 📁 Estructura Actual del Proyecto

```
guardias/
├── ARQUITECTURA.md              # ✅ Arquitectura completa
├── README.md                    # Este archivo
├── backend/                     # ✅ Symfony 7.2
│   ├── config/
│   │   ├── packages/
│   │   │   ├── doctrine.yaml    # ✅ Configurado
│   │   │   ├── security.yaml    # ✅ Configurado
│   │   │   ├── lexik_jwt_authentication.yaml  # ✅ Configurado
│   │   │   └── nelmio_cors.yaml # ✅ Configurado
│   │   └── routes.yaml          # ✅ Configurado
│   ├── src/
│   │   ├── Entity/              # ✅ 6 entidades creadas
│   │   │   ├── Department.php
│   │   │   ├── User.php
│   │   │   ├── Guard.php
│   │   │   ├── Shift.php
│   │   │   ├── GuardAssignment.php
│   │   │   └── ShiftSwapRequest.php
│   │   └── Repository/          # ✅ 6 repositorios creados
│   ├── .env                     # ✅ Configurado PostgreSQL
│   └── composer.json            # ✅ Dependencias instaladas
├── frontend/                    # ✅ Vue.js 3 + Vite
│   ├── package.json             # ⚠️ Ejecutar npm install
│   └── src/
├── docker/                      # ✅ Configuración Docker
│   ├── docker-compose.yml
│   ├── Dockerfile.backend
│   ├── Dockerfile.frontend
│   └── nginx/
│       └── default.conf
└── .qwen/
    └── agents/
        └── fullstack-developer.md  # ✅ Agente creado
```

---

## 🔑 Variables de Entorno

### Backend (.env)
```env
APP_ENV=dev
APP_SECRET=7a8f9c3e2b1d6a5f4e8c9b2a1d3f5e7c9b4a6d8f
DATABASE_URL="postgresql://guardias_user:guardias_password@127.0.0.1:5432/guardias?serverVersion=15&charset=utf8"
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=3888bc5e21e0988e2c87459469d87dc10a63e0b543aa9638e44bc709a72b6547
CORS_ALLOW_ORIGIN='^https?://(localhost|127\.0\.0\.1)(:[0-9]+)?$'
```

---

## 📝 Checklist de Tareas Pendientes

### Fase 1 - Completada ✅
- [x] Proyecto Symfony
- [x] Proyecto Vue.js
- [x] Docker configurado
- [x] Base de datos configurada
- [x] JWT configurado
- [x] CORS configurado
- [x] Entidades creadas
- [x] Repositorios creados

### Fase 2 - Pendiente
- [ ] Instalar dependencias frontend
- [ ] Generar migraciones
- [ ] Ejecutar migraciones
- [ ] Crear controladores API
- [ ] Crear servicios
- [ ] Crear fixtures

### Fase 3 - Pendiente
- [ ] Configurar Vue Router
- [ ] Configurar Pinia stores
- [ ] Crear servicios API frontend
- [ ] Crear componentes de autenticación
- [ ] Crear vistas principales

---

## 🆘 Soporte

Si encuentras problemas:

1. **Problemas de npm**: Ejecuta `sudo chown -R $(whoami) ~/.npm`
2. **Problemas de base de datos**: Verifica que PostgreSQL esté corriendo
3. **Problemas de JWT**: Regenera las claves con `php bin/console lexik:jwt:generate-keypair`

---

*Documento generado automáticamente - Sistema de Guardias*
*Fecha: 2026-03-03*
