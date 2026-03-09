# 🏗️ Sistema de Guardias - Contexto del Proyecto

## 📋 Descripción General

Sistema de gestión de guardias para organizaciones con múltiples departamentos, calendarización de turnos, administración de personal activo y solicitudes de cambio de turno.

**Estado Actual:** ✅ **COMPLETADO Y FUNCIONANDO** - Todos los servicios operativos con datos de prueba.

---

## 🛠️ Stack Tecnológico

### Backend
| Tecnología | Versión | Propósito |
|------------|---------|-----------|
| **Framework** | Symfony 7.2 | API RESTful |
| **Lenguaje** | PHP 8.2+ | Backend |
| **Base de Datos** | MySQL 8.0 | Persistencia |
| **ORM** | Doctrine ORM 3.x | Mapeo objeto-relacional |
| **Autenticación** | LexikJWTAuthenticationBundle | JWT |
| **Validación** | Symfony Validator | Validación de datos |

### Frontend
| Tecnología | Versión | Propósito |
|------------|---------|-----------|
| **Framework** | Vue.js 3.x | UI con Composition API |
| **Build Tool** | Vite 5.x | Bundler y dev server |
| **UI Library** | PrimeVue 4.x | Componentes UI |
| **Estado** | Pinia 2.x | State management |
| **Router** | Vue Router 4.x | Navegación |
| **HTTP Client** | Axios 1.6.x | Peticiones API |
| **Calendario** | FullCalendar 6.x | Vista de calendario |
| **Utilidades** | date-fns 3.x | Manipulación de fechas |

### DevOps
| Tecnología | Versión | Propósito |
|------------|---------|-----------|
| **Docker** | Latest | Contenedores |
| **Testing Backend** | PHPUnit 11.x | Tests unitarios |
| **Testing Frontend** | Vitest 1.x | Tests frontend |

---

## 📁 Estructura del Proyecto

```
guardias/
├── ARQUITECTURA.md              # Arquitectura completa del sistema
├── ESTADO.md                    # Estado actual y credenciales
├── INSTRUCCIONES.md             # Instrucciones de desarrollo
├── README.md                    # Documentación principal
├── QWEN.md                      # Este archivo - Contexto para Qwen
│
├── backend/                     # Aplicación Symfony
│   ├── bin/                     # Scripts ejecutables
│   ├── config/
│   │   ├── packages/            # Configuración de bundles
│   │   │   ├── doctrine.yaml
│   │   │   ├── security.yaml
│   │   │   ├── lexik_jwt_authentication.yaml
│   │   │   └── nelmio_cors.yaml
│   │   └── routes.yaml
│   ├── migrations/              # Migraciones de Doctrine
│   ├── public/                  # Punto de entrada público
│   ├── src/
│   │   ├── Command/             # Comandos de consola
│   │   ├── Controller/Api/      # Controladores REST
│   │   │   ├── AuthController.php
│   │   │   ├── DepartmentController.php
│   │   │   ├── UserController.php
│   │   │   ├── GuardController.php
│   │   │   ├── ShiftController.php
│   │   │   └── AssignmentController.php
│   │   ├── DataFixtures/        # Datos de prueba
│   │   ├── Entity/              # Entidades Doctrine
│   │   │   ├── Department.php
│   │   │   ├── User.php
│   │   │   ├── Guard.php
│   │   │   ├── Shift.php
│   │   │   ├── GuardAssignment.php
│   │   │   └── ShiftSwapRequest.php
│   │   ├── EventListener/       # Event listeners
│   │   ├── Repository/          # Repositorios Doctrine
│   │   ├── Security/            # Voter y seguridad
│   │   ├── Service/             # Servicios de negocio
│   │   │   ├── GuardService.php
│   │   │   ├── ShiftService.php
│   │   │   ├── DepartmentService.php
│   │   │   ├── UserService.php
│   │   │   └── CalendarService.php
│   │   └── Kernel.php
│   ├── tests/                   # Tests PHPUnit
│   ├── .env                     # Variables de entorno
│   ├── .env.dev                 # Variables para desarrollo
│   ├── .env.test                # Variables para tests
│   ├── composer.json            # Dependencias PHP
│   └── compose.yaml             # Docker Compose del backend
│
├── frontend/                    # Aplicación Vue.js
│   ├── public/                  # Assets públicos
│   ├── src/
│   │   ├── assets/              # Recursos estáticos
│   │   ├── components/          # Componentes Vue
│   │   │   ├── common/          # Componentes compartidos
│   │   │   ├── auth/            # Componentes de autenticación
│   │   │   ├── departments/     # Componentes de departamentos
│   │   │   ├── users/           # Componentes de usuarios
│   │   │   ├── guards/          # Componentes de guardias
│   │   │   └── shifts/          # Componentes de turnos
│   │   ├── composables/         # Composables reutilizables
│   │   ├── layouts/             # Layouts principales
│   │   │   └── DashboardLayout.vue
│   │   ├── router/              # Configuración de rutas
│   │   │   └── index.js
│   │   ├── services/            # Servicios API
│   │   │   ├── api.js
│   │   │   ├── authService.js
│   │   │   ├── departmentService.js
│   │   │   ├── userService.js
│   │   │   ├── guardService.js
│   │   │   └── shiftService.js
│   │   ├── stores/              # Pinia stores
│   │   │   ├── auth.store.js
│   │   │   ├── department.store.js
│   │   │   ├── user.store.js
│   │   │   ├── guard.store.js
│   │   │   └── shift.store.js
│   │   ├── utils/               # Utilidades
│   │   │   ├── validators.js
│   │   │   ├── formatters.js
│   │   │   └── constants.js
│   │   ├── views/               # Vistas principales
│   │   │   ├── LoginView.vue
│   │   │   ├── DashboardView.vue
│   │   │   ├── DepartmentsView.vue
│   │   │   ├── UsersView.vue
│   │   │   ├── GuardsView.vue
│   │   │   ├── ShiftsView.vue
│   │   │   ├── CalendarView.vue
│   │   │   ├── SettingsView.vue
│   │   │   └── NotFoundView.vue
│   │   ├── App.vue
│   │   ├── main.js
│   │   └── style.css
│   ├── index.html
│   ├── package.json             # Dependencias Node.js
│   └── vite.config.js           # Configuración de Vite
│
├── docker/                      # Configuración Docker
│   ├── docker-compose.yml       # Orquestación de contenedores
│   ├── Dockerfile.backend       # Imagen del backend
│   ├── Dockerfile.frontend      # Imagen del frontend
│   ├── nginx/
│   │   └── default.conf         # Configuración de Nginx
│   └── setup.sh                 # Script de configuración
│
└── .qwen/
    └── agents/                  # Agentes Qwen personalizados
```

---

## 🚀 Servicios y Puertos

| Servicio | URL/Puerto | Credenciales | Descripción |
|----------|------------|--------------|-------------|
| **Frontend** | http://localhost:5173 | - | Aplicación Vue.js |
| **Backend API** | http://localhost:8000 | - | API REST Symfony |
| **phpMyAdmin** | http://localhost:18080 | `guardias_user` / `guardias_password` | Gestión MySQL |
| **MySQL** | localhost:13306 | `guardias_user` / `guardias_password` | Base de datos |

---

## 🔐 Credenciales de Acceso

### Usuario Administrador
| Campo | Valor |
|-------|-------|
| **URL** | http://localhost:5173 |
| **Email** | `admin@example.com` |
| **Contraseña** | `admin123` |
| **Rol** | ADMIN |

### Otros Usuarios de Prueba
| Email | Password | Rol | Departamento |
|-------|----------|-----|--------------|
| `manager@example.com` | `manager123` | MANAGER | Operaciones |
| `juan@example.com` | `user123` | USER | Operaciones |
| `maria@example.com` | `user123` | USER | Operaciones |

---

## 📦 Comandos Principales

### Docker - Gestión de Contenedores

```bash
# Iniciar todos los servicios
cd docker
docker-compose up -d

# Ver logs en tiempo real
docker-compose logs -f

# Ver logs de un servicio específico
docker-compose logs -f backend
docker-compose logs -f frontend
docker-compose logs -f database

# Detener servicios
docker-compose down

# Reiniciar servicios
docker-compose down && docker-compose up -d

# Acceder al contenedor del backend
docker-compose exec backend bash

# Acceder al contenedor del frontend
docker-compose exec frontend bash
```

### Backend - Symfony

```bash
# Dentro del contenedor del backend
docker-compose exec backend bash

# Consola de Symfony
php bin/console

# Crear base de datos
php bin/console doctrine:database:create

# Generar migraciones
php bin/console make:migration

# Ejecutar migraciones
php bin/console doctrine:migrations:migrate

# Limpiar caché
php bin/console cache:clear

# Cargar datos de prueba (fixtures)
php bin/console doctrine:fixtures:load

# Crear usuario administrador
php bin/console app:create-admin

# Ver migraciones pendientes
php bin/console doctrine:migrations:status
```

### Frontend - Vue.js

```bash
# Dentro del contenedor del frontend
docker-compose exec frontend bash

# Instalar dependencias (si es necesario)
npm install

# Servidor de desarrollo (ya corre con docker-compose)
npm run dev

# Build de producción
npm run build

# Ejecutar tests
npm run test
```

### API - Endpoints Principales

```bash
# Login (obtener token JWT)
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@example.com","password":"admin123"}'

# Obtener usuario autenticado
curl -X GET http://localhost:8000/api/auth/me \
  -H "Authorization: Bearer TU_TOKEN_JWT"

# Listar departamentos
curl -X GET http://localhost:8000/api/departments \
  -H "Authorization: Bearer TU_TOKEN_JWT"

# Listar usuarios
curl -X GET http://localhost:8000/api/users \
  -H "Authorization: Bearer TU_TOKEN_JWT"

# Listar guardias
curl -X GET http://localhost:8000/api/guards \
  -H "Authorization: Bearer TU_TOKEN_JWT"

# Calendario de guardias
curl -X GET http://localhost:8000/api/assignments/calendar \
  -H "Authorization: Bearer TU_TOKEN_JWT"
```

---

## 🗄️ Modelo de Datos

### Entidades Principales

#### Department
- `id` (UUID) - Primary Key
- `name` (string, unique) - Nombre del departamento
- `code` (string, unique) - Código corto
- `description` (text, nullable)
- `active` (boolean, default: true)
- `parentDepartment` (self-reference, nullable)
- `users` (OneToMany -> User)
- `guards` (OneToMany -> Guard)

#### User
- `id` (UUID) - Primary Key
- `email` (string, unique)
- `password` (string, hashed)
- `firstName` (string)
- `lastName` (string)
- `phone` (string, nullable)
- `roles` (array, default: ['ROLE_USER'])
- `active` (boolean, default: true)
- `department` (ManyToOne -> Department)
- `guardAssignments` (OneToMany -> GuardAssignment)

#### Guard
- `id` (UUID) - Primary Key
- `name` (string) - Nombre de la guardia
- `code` (string, unique) - Código
- `description` (text, nullable)
- `department` (ManyToOne -> Department)
- `startTime` (time)
- `endTime` (time)
- `duration` (integer, calculated) - Duración en minutos
- `active` (boolean, default: true)
- `assignments` (OneToMany -> GuardAssignment)

#### Shift
- `id` (UUID) - Primary Key
- `name` (string) - Nombre del turno
- `code` (string, unique)
- `startTime` (time)
- `endTime` (time)
- `type` (enum: morning, afternoon, night, custom)
- `color` (string) - Color para calendario
- `active` (boolean, default: true)

#### GuardAssignment
- `id` (UUID) - Primary Key
- `guard` (ManyToOne -> Guard)
- `user` (ManyToOne -> User)
- `assignedBy` (ManyToOne -> User)
- `date` (date)
- `startTime` (time)
- `endTime` (time)
- `status` (enum: scheduled, active, completed, cancelled, swapped)
- `notes` (text, nullable)
- `swapRequest` (OneToOne -> ShiftSwapRequest, nullable)

#### ShiftSwapRequest
- `id` (UUID) - Primary Key
- `originalAssignment` (ManyToOne -> GuardAssignment)
- `newUser` (ManyToOne -> User)
- `requestedBy` (ManyToOne -> User)
- `status` (enum: pending, approved, rejected)
- `approvedBy` (ManyToOne -> User, nullable)
- `reason` (text, nullable)
- `rejectionReason` (text, nullable)

---

## 🌐 API RESTful - Endpoints

### Auth
```
POST   /api/auth/login          # Autenticar usuario
POST   /api/auth/logout         # Cerrar sesión
GET    /api/auth/me             # Usuario actual
POST   /api/auth/refresh        # Refresh token
```

### Departments
```
GET    /api/departments         # Listar departamentos
POST   /api/departments         # Crear departamento
GET    /api/departments/{id}    # Obtener departamento
PUT    /api/departments/{id}    # Actualizar departamento
DELETE /api/departments/{id}    # Eliminar departamento
GET    /api/departments/{id}/users  # Usuarios del departamento
```

### Users
```
GET    /api/users               # Listar usuarios
POST   /api/users               # Crear usuario
GET    /api/users/{id}          # Obtener usuario
PUT    /api/users/{id}          # Actualizar usuario
DELETE /api/users/{id}          # Eliminar usuario
GET    /api/users/{id}/guards   # Guardias del usuario
```

### Guards
```
GET    /api/guards              # Listar guardias
POST   /api/guards              # Crear guardia
GET    /api/guards/active       # Guardias activas
GET    /api/guards/{id}         # Obtener guardia
PUT    /api/guards/{id}         # Actualizar guardia
DELETE /api/guards/{id}         # Eliminar guardia
GET    /api/guards/{id}/assignments  # Asignaciones
```

### Shifts
```
GET    /api/shifts              # Listar turnos
POST   /api/shifts              # Crear turno
GET    /api/shifts/{id}         # Obtener turno
PUT    /api/shifts/{id}         # Actualizar turno
DELETE /api/shifts/{id}         # Eliminar turno
```

### Assignments
```
GET    /api/assignments                 # Listar asignaciones
POST   /api/assignments                 # Crear asignación
GET    /api/assignments/{id}            # Obtener asignación
PUT    /api/assignments/{id}            # Actualizar asignación
DELETE /api/assignments/{id}            # Eliminar asignación
GET    /api/assignments/calendar        # Calendario
GET    /api/assignments/date/{date}     # Por fecha
GET    /api/assignments/user/{userId}   # Por usuario
POST   /api/assignments/{id}/swap       # Solicitar cambio
PUT    /api/assignments/swap/{swapId}/approve   # Aprobar cambio
PUT    /api/assignments/swap/{swapId}/reject    # Rechazar cambio
```

---

## 🎨 Frontend - Estructura

### Rutas (Vue Router)

```javascript
{
  path: '/login',
  name: 'Login',
  component: LoginView
},
{
  path: '/',
  component: DashboardLayout,
  children: [
    { path: '', name: 'Dashboard', component: DashboardView },
    { path: 'departments', name: 'Departments', component: DepartmentsView },
    { path: 'users', name: 'Users', component: UsersView },
    { path: 'guards', name: 'Guards', component: GuardsView },
    { path: 'shifts', name: 'Shifts', component: ShiftsView },
    { path: 'calendar', name: 'Calendar', component: CalendarView },
    { path: 'settings', name: 'Settings', component: SettingsView }
  ]
}
```

### Stores (Pinia)

| Store | Propósito |
|-------|-----------|
| `auth.store` | Autenticación, token JWT, usuario actual |
| `department.store` | CRUD de departamentos |
| `user.store` | CRUD de usuarios |
| `guard.store` | CRUD de guardias, guardias activas |
| `shift.store` | CRUD de turnos, asignaciones, calendario |

### Servicios API

| Servicio | Funciones |
|----------|-----------|
| `api.js` | Configuración de Axios, interceptores |
| `authService.js` | login, logout, me, refreshToken |
| `departmentService.js` | getAll, getById, create, update, delete |
| `userService.js` | getAll, getById, create, update, delete |
| `guardService.js` | getAll, getActive, getById, create, update, delete |
| `shiftService.js` | getAll, getById, create, update, delete, requestSwap |

---

## 🧪 Testing

### Backend (PHPUnit)

```bash
# Ejecutar tests
docker-compose exec backend vendor/bin/phpunit

# Ejecutar tests con cobertura
docker-compose exec backend vendor/bin/phpunit --coverage-html var/coverage
```

### Frontend (Vitest)

```bash
# Ejecutar tests
docker-compose exec frontend npm run test

# Ejecutar tests en modo watch
docker-compose exec frontend npm run test -- --watch
```

---

## 🔧 Solución de Problemas

### Error: "Cannot connect to Docker daemon"
```bash
# Asegúrate de que Docker Desktop esté corriendo
# En macOS, abre Docker Desktop desde Applications
```

### Error: "Port already in use"
```bash
# Verifica qué proceso usa el puerto
lsof -i :5173  # Frontend
lsof -i :8000  # Backend
lsof -i :13306 # MySQL
lsof -i :18080 # phpMyAdmin

# Detén el proceso o cambia el puerto en docker-compose.yml
```

### Error: "Database connection failed"
```bash
# Verifica que MySQL esté corriendo
docker-compose ps database

# Revisa los logs
docker-compose logs database

# Espera unos segundos, MySQL puede tardar en iniciar
```

### Error: "npm install failed" (permisos)
```bash
# Corregir permisos de npm
sudo chown -R $(whoami) ~/.npm

# Luego intenta de nuevo
cd frontend
npm install
```

### Error: "JWT keys not found"
```bash
# Regenerar claves JWT
docker-compose exec backend php bin/console lexik:jwt:generate-keypair
```

---

## 📊 Datos de Prueba (Fixtures)

### Departamentos (3)
- Tecnología (TECH)
- Recursos Humanos (HR)
- Operaciones (OPS)

### Turnos (3)
- Turno Mañana (06:00 - 14:00) - Azul
- Turno Tarde (14:00 - 22:00) - Naranja
- Turno Noche (22:00 - 06:00) - Oscuro

### Guardias (3)
- Guardia Matutina (06:00 - 14:00)
- Guardia Vespertina (14:00 - 22:00)
- Guardia Nocturna (22:00 - 06:00)

### Usuarios (4)
- Admin (admin@example.com) - ADMIN - Tecnología
- Manager (manager@example.com) - MANAGER - Operaciones
- Juan Pérez (juan@example.com) - USER - Operaciones
- María García (maria@example.com) - USER - Operaciones

### Asignaciones (21)
- Guardias para los próximos 7 días
- Usuarios: Juan Pérez y María García
- Estado: scheduled

---

## 📝 Convenciones de Desarrollo

### Backend (PHP/Symfony)
- **Naming:** PascalCase para clases, camelCase para métodos
- **Entidades:** Una entidad por archivo en `src/Entity/`
- **Controladores:** Sufijo `Controller`, extienden `AbstractController`
- **Servicios:** Sufijo `Service`, inyección de dependencias
- **Tests:** Sufijo `Test`, en `tests/`

### Frontend (Vue.js)
- **Componentes:** PascalCase, nombre descriptivo (ej: `GuardList.vue`)
- **Composables:** Prefijo `use` (ej: `useAuth.js`)
- **Stores:** Sufijo `.store.js` (ej: `auth.store.js`)
- **Servicios:** Sufijo `Service` (ej: `authService.js`)
- **Vistas:** Sufijo `View` (ej: `DashboardView.vue`)

### Git
- **Ramas:** `main`, `develop`, `feature/*`, `bugfix/*`, `hotfix/*`
- **Commits:** Mensajes descriptivos en presente

---

## 🚀 Flujo de Trabajo Típico

### Añadir nueva funcionalidad

1. **Backend:**
   ```bash
   # Crear entidad (si aplica)
   docker-compose exec backend php bin/console make:entity
   
   # Generar migración
   docker-compose exec backend php bin/console make:migration
   
   # Ejecutar migración
   docker-compose exec backend php bin/console doctrine:migrations:migrate
   
   # Crear controlador/método
   docker-compose exec backend php bin/console make:controller Api/NewController
   ```

2. **Frontend:**
   ```bash
   # Crear vista
   # src/views/NewView.vue
   
   # Crear servicio API
   # src/services/newService.js
   
   # Añadir ruta
   # src/router/index.js
   
   # Crear store (si aplica)
   # src/stores/new.store.js
   ```

3. **Verificar:**
   ```bash
   # Backend tests
   docker-compose exec backend vendor/bin/phpunit
   
   # Frontend tests
   docker-compose exec frontend npm run test
   ```

---

## 📚 Recursos y Documentación

| Documento | Descripción |
|-----------|-------------|
| `ARQUITECTURA.md` | Arquitectura completa, flujos, diagramas |
| `ESTADO.md` | Estado actual, credenciales, endpoints |
| `INSTRUCCIONES.md` | Instrucciones de desarrollo paso a paso |
| `README.md` | Documentación principal de acceso |

### Enlaces Externos
- [Symfony Documentation](https://symfony.com/doc/current/index.html)
- [Vue.js Documentation](https://vuejs.org/guide/introduction.html)
- [PrimeVue Documentation](https://primevue.org/)
- [Pinia Documentation](https://pinia.vuejs.org/)
- [Doctrine ORM](https://www.doctrine-project.org/projects/doctrine-orm/en/current/index.html)

---

*Documento generado para Qwen Code - Sistema de Guardias*
*Última actualización: 2026-03-09*
