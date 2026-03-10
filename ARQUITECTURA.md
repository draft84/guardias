# 🏗️ Arquitectura - Sistema de Guardias

## 📋 Descripción General

Sistema de gestión de guardias para organizaciones con múltiples departamentos, usuarios, calendarización de turnos y administración de personal activo. Implementa control de acceso basado en roles y departamentos.

**Estado:** ✅ **COMPLETADO Y FUNCIONANDO**

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
├── backend/                    # Aplicación Symfony
│   ├── config/
│   │   ├── packages/
│   │   │   ├── doctrine.yaml
│   │   │   ├── security.yaml
│   │   │   ├── lexik_jwt_authentication.yaml
│   │   │   └── nelmio_cors.yaml
│   │   └── routes.yaml
│   ├── migrations/             # Migraciones de Doctrine
│   ├── public/                 # Punto de entrada público
│   ├── src/
│   │   ├── Command/
│   │   │   └── InitRolesCommand.php
│   │   ├── Controller/Api/
│   │   │   ├── AuthController.php
│   │   │   ├── DepartmentController.php
│   │   │   ├── UserController.php
│   │   │   ├── GuardController.php
│   │   │   ├── AssignmentController.php
│   │   │   └── RoleController.php
│   │   ├── DataFixtures/       # Datos de prueba
│   │   ├── Entity/
│   │   │   ├── Department.php
│   │   │   ├── User.php
│   │   │   ├── Guard.php
│   │   │   ├── Shift.php
│   │   │   ├── GuardAssignment.php
│   │   │   ├── ShiftSwapRequest.php
│   │   │   ├── GuardLevel.php
│   │   │   └── Role.php
│   │   ├── Repository/
│   │   │   ├── UserRepository.php
│   │   │   ├── DepartmentRepository.php
│   │   │   ├── GuardRepository.php
│   │   │   ├── ShiftRepository.php
│   │   │   ├── GuardAssignmentRepository.php
│   │   │   └── RoleRepository.php
│   │   ├── Security/
│   │   │   └── UserChecker.php
│   │   ├── Service/
│   │   │   ├── GuardService.php
│   │   │   ├── ShiftService.php
│   │   │   ├── DepartmentService.php
│   │   │   ├── UserService.php
│   │   │   └── CalendarService.php
│   │   ├── Traits/
│   │   │   └── CurrentUserTrait.php
│   │   └── Kernel.php
│   ├── tests/
│   ├── .env
│   ├── composer.json
│   └── symfony.lock
│
├── frontend/                   # Aplicación Vue.js
│   ├── public/
│   ├── src/
│   │   ├── assets/
│   │   ├── components/
│   │   │   └── common/
│   │   ├── layouts/
│   │   │   └── DashboardLayout.vue
│   │   ├── router/
│   │   │   └── index.js
│   │   ├── services/
│   │   │   ├── api.js
│   │   │   ├── authService.js
│   │   │   ├── departmentService.js
│   │   │   ├── userService.js
│   │   │   ├── guardService.js
│   │   │   └── shiftService.js
│   │   ├── stores/
│   │   │   ├── auth.store.js
│   │   │   ├── department.store.js
│   │   │   ├── user.store.js
│   │   │   ├── guard.store.js
│   │   │   └── shift.store.js
│   │   ├── utils/
│   │   │   ├── validators.js
│   │   │   ├── formatters.js
│   │   │   └── constants.js
│   │   ├── views/
│   │   │   ├── LoginView.vue
│   │   │   ├── DashboardView.vue
│   │   │   ├── DepartmentsView.vue
│   │   │   ├── UsersView.vue
│   │   │   ├── GuardsView.vue
│   │   │   ├── CalendarView.vue
│   │   │   └── SettingsView.vue
│   │   ├── App.vue
│   │   └── main.js
│   ├── index.html
│   ├── package.json
│   └── vite.config.js
│
├── docker/                     # Configuración Docker
│   ├── docker-compose.yml
│   ├── Dockerfile.backend
│   ├── Dockerfile.frontend
│   ├── nginx/default.conf
│   ├── backup.sh
│   ├── restore.sh
│   └── manage_backups.sh
│
├── docs/                       # Documentación
│   ├── ARQUITECTURA.md
│   ├── ESTADO.md
│   ├── INSTRUCCIONES.md
│   └── README.md
│
└── ARQUITECTURA.md
```

---

## 🗄️ Modelo de Datos

### Entidades Principales

#### 1. Department (Departamento)
```yaml
Department:
  id: UUID (Primary Key)
  name: string (unique, not null)
  code: string (unique, not null)
  description: text (nullable)
  active: boolean (default: true)
  createdAt: datetime
  updatedAt: datetime
  parentDepartment: Department (self-referencing, nullable)
  users: OneToMany -> User
  guards: OneToMany -> Guard
```

#### 2. User (Usuario)
```yaml
User:
  id: UUID (Primary Key)
  email: string (unique, not null)
  password: string (hashed, not null)
  firstName: string (not null)
  lastName: string (not null)
  phone: string (nullable)
  roles: array (default: ['ROLE_USER'])
  active: boolean (default: true)
  department: ManyToOne -> Department (nullable)
  guardLevel: ManyToOne -> GuardLevel (nullable)
  createdAt: datetime
  updatedAt: datetime
  lastLogin: datetime (nullable)
  guardAssignments: OneToMany -> GuardAssignment
  assignedGuards: OneToMany -> GuardAssignment
  swapRequests: OneToMany -> ShiftSwapRequest
```

#### 3. Guard (Guardia - Tipo de Servicio)
```yaml
Guard:
  id: UUID (Primary Key)
  name: string (not null)
  code: string (unique, not null)
  description: text (nullable)
  department: ManyToOne -> Department (nullable)
  startTime: time (not null)
  endTime: time (not null)
  duration: integer (minutes, calculated)
  active: boolean (default: true)
  validFrom: date (nullable)
  validUntil: date (nullable)
  weekDays: array (nullable)
  createdAt: datetime
  updatedAt: datetime
  assignments: OneToMany -> GuardAssignment
```

#### 4. Shift (Turno - Configuración de Horario)
```yaml
Shift:
  id: UUID (Primary Key)
  name: string (not null)
  code: string (unique, not null)
  startTime: time (not null)
  endTime: time (not null)
  type: enum ['morning', 'afternoon', 'night', 'custom']
  color: string (for calendar display)
  active: boolean (default: true)
```

#### 5. GuardAssignment (Asignación de Guardia)
```yaml
GuardAssignment:
  id: UUID (Primary Key)
  guard: ManyToOne -> Guard
  user: ManyToOne -> User
  assignedBy: ManyToOne -> User
  date: date (not null)
  startTime: time (not null)
  endTime: time (not null)
  status: enum ['scheduled', 'active', 'completed', 'cancelled', 'swapped']
  notes: text (nullable)
  swapRequest: OneToOne -> ShiftSwapRequest (nullable)
  createdAt: datetime
  updatedAt: datetime
```

#### 6. ShiftSwapRequest (Solicitud de Cambio de Turno)
```yaml
ShiftSwapRequest:
  id: UUID (Primary Key)
  originalAssignment: ManyToOne -> GuardAssignment
  newUser: ManyToOne -> User
  requestedBy: ManyToOne -> User
  status: enum ['pending', 'approved', 'rejected']
  approvedBy: ManyToOne -> User (nullable)
  reason: text (nullable)
  rejectionReason: text (nullable)
```

#### 7. GuardLevel (Nivel de Guardia)
```yaml
GuardLevel:
  id: UUID (Primary Key)
  name: string (not null)
  users: OneToMany -> User
```

#### 8. Role (Rol del Sistema)
```yaml
Role:
  id: UUID (Primary Key)
  name: string (unique, not null)
  description: text (nullable)
  active: boolean (default: true)
  createdAt: datetime
  updatedAt: datetime
```

---

## 🔐 Sistema de Autenticación y Autorización

### Roles de Usuario

| Rol | Descripción | Permisos |
|-----|-------------|----------|
| **ROLE_ADMIN** | Administrador del sistema | Acceso completo a todas las secciones y funcionalidades |
| **ROLE_MANAGER** | Gestor de departamento | Gestión limitada a su departamento. No puede acceder a Configuración ni Departamentos |
| **ROLE_USER** | Usuario estándar | Solo visualización. Puede ver/editar solo registros de su departamento |

### Matriz de Acceso por Sección

| Sección | ADMIN | MANAGER | USER |
|---------|-------|---------|------|
| Dashboard | ✅ | ✅ | ✅ |
| Departamentos | ✅ | ❌ | ❌ |
| Usuarios | ✅ | ✅ (solo su depto) | ✅ (solo su depto) |
| Guardias | ✅ | ✅ (solo su depto) | ✅ (solo su depto) |
| Calendario | ✅ | ✅ | ✅ |
| Configuración | ✅ | ❌ | ❌ |

### Endpoints de Autenticación

```yaml
POST /api/auth/login:
  description: Autenticar usuario y obtener JWT
  body: { email, password }
  response: { token, user }

POST /api/auth/logout:
  description: Cerrar sesión
  headers: { Authorization: Bearer <token> }

GET /api/auth/me:
  description: Obtener usuario autenticado
  headers: { Authorization: Bearer <token> }
  response: { user: { id, email, firstName, lastName, roles, department, departmentName } }

POST /api/auth/refresh:
  description: Refresh token
  headers: { Authorization: Bearer <token> }
  response: { token }

POST /api/auth/profile/change-password:
  description: Cambiar contraseña
  headers: { Authorization: Bearer <token> }
  body: { currentPassword, newPassword, confirmPassword }
```

---

## 🌐 API RESTful

### Endpoints por Módulo

#### Auth
```
POST   /api/auth/login                     # Autenticar usuario
POST   /api/auth/logout                    # Cerrar sesión
GET    /api/auth/me                        # Usuario autenticado
POST   /api/auth/refresh                   # Refresh token
POST   /api/auth/profile/change-password   # Cambiar contraseña
```

#### Departments (Solo ADMIN)
```
GET    /api/departments              # Listar departamentos (filtrado por rol)
POST   /api/departments              # Crear departamento (ADMIN/MANAGER)
GET    /api/departments/{id}         # Obtener departamento
PUT    /api/departments/{id}         # Actualizar departamento (ADMIN/MANAGER)
DELETE /api/departments/{id}         # Eliminar departamento (ADMIN/MANAGER)
GET    /api/departments/{id}/users   # Obtener usuarios del departamento
```

#### Users
```
GET    /api/users                    # Listar usuarios (filtrado por departamento)
POST   /api/users                    # Crear usuario (ADMIN/MANAGER)
GET    /api/users/{id}               # Obtener usuario
PUT    /api/users/{id}               # Actualizar usuario (ADMIN/MANAGER)
DELETE /api/users/{id}               # Eliminar usuario (ADMIN/MANAGER)
GET    /api/users/department/{id}    # Usuarios por departamento
```

#### Guards
```
GET    /api/guards                   # Listar guardias (filtrado por departamento)
POST   /api/guards                   # Crear guardia (ADMIN/MANAGER)
GET    /api/guards/{id}              # Obtener guardia
PUT    /api/guards/{id}              # Actualizar guardia (ADMIN/MANAGER)
DELETE /api/guards/{id}              # Eliminar guardia (ADMIN/MANAGER)
GET    /api/guards/active            # Guardias activas
GET    /api/guards/{id}/assignments  # Asignaciones de una guardia
```

#### Guard Assignments
```
GET    /api/assignments                      # Listar asignaciones (filtrado por departamento)
POST   /api/assignments                      # Crear asignación (ADMIN/MANAGER)
GET    /api/assignments/{id}                 # Obtener asignación
PUT    /api/assignments/{id}                 # Actualizar asignación (ADMIN/MANAGER)
DELETE /api/assignments/{id}                 # Eliminar asignación (ADMIN/MANAGER)
GET    /api/assignments/calendar             # Calendario de guardias
GET    /api/assignments/date/{date}          # Guardias por fecha
GET    /api/assignments/user/{userId}        # Guardias por usuario
POST   /api/assignments/{id}/swap            # Solicitar cambio
PUT    /api/assignments/swap/{swapId}/approve # Aprobar cambio
PUT    /api/assignments/swap/{swapId}/reject  # Rechazar cambio
```

#### Shifts
```
GET    /api/shifts                   # Listar turnos
POST   /api/shifts                   # Crear turno (ADMIN/MANAGER)
GET    /api/shifts/{id}              # Obtener turno
PUT    /api/shifts/{id}              # Actualizar turno (ADMIN/MANAGER)
DELETE /api/shifts/{id}              # Eliminar turno (ADMIN/MANAGER)
```

#### Roles (Solo ADMIN/MANAGER)
```
GET    /api/roles                    # Listar roles
POST   /api/roles                    # Crear rol
PUT    /api/roles/{id}               # Actualizar rol
DELETE /api/roles/{id}               # Eliminar rol
```

---

## 🎨 Frontend - Vue.js

### Estructura de Vistas

```
┌─────────────────────────────────────────────────────────┐
│                     LoginView                           │
│                     /login                              │
└─────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────┐
│                  DashboardLayout                        │
│  ┌─────────────┬──────────────────────────────────────┐ │
│  │   Sidebar   │           Main Content               │ │
│  │             │                                      │ │
│  │  Dashboard  │  ┌────────────────────────────────┐  │ │
│  │  (ADMIN)    │  │  DashboardView                 │  │ │
│  │  Departam.  │  │  - Resumen de guardias         │  │ │
│  │  Usuarios   │  │  - Próximas guardias           │  │ │
│  │  Guardias   │  │  - Estadísticas                │  │ │
│  │  Calendario │  │                                │  │ │
│  │  (ADMIN)    │  │  [Mensaje de Acceso Denegado]  │  │ │
│  │  Config.    │  │  Si se intenta acceder sin     │  │ │
│  │             │  │  permisos                      │  │ │
│  │             │  └────────────────────────────────┘  │ │
│  └─────────────┴──────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────┘
```

### Stores (Pinia)

#### auth.store.js
```javascript
{
  state: {
    user: null,
    token: null
  },
  getters: {
    isAuthenticated: Boolean,
    isAdmin: Boolean,
    isManager: Boolean,
    isManagerOrAdmin: Boolean,
    userName: String
  },
  actions: {
    login(email, password),
    logout(),
    fetchUser(),
    changePassword(current, newPassword, confirm)
  }
}
```

#### department.store.js
```javascript
{
  state: {
    departments: [],
    loading: false
  },
  actions: {
    fetchDepartments(),
    fetchDepartment(id),
    createDepartment(data),
    updateDepartment(id, data),
    deleteDepartment(id)
  }
}
```

#### user.store.js
```javascript
{
  state: {
    users: [],
    levels: [],
    roles: [],
    loading: false
  },
  actions: {
    fetchUsers(),
    fetchLevels(),
    fetchRoles(),
    createUser(data),
    updateUser(id, data),
    deleteUser(id),
    createLevel(name),
    updateLevel(id, name),
    deleteLevel(id),
    createRole(name, description),
    updateRole(id, name, description),
    deleteRole(id)
  }
}
```

#### guard.store.js
```javascript
{
  state: {
    guards: [],
    loading: false
  },
  actions: {
    fetchGuards(),
    fetchGuard(id),
    createGuard(data),
    updateGuard(id, data),
    deleteGuard(id)
  }
}
```

#### shift.store.js
```javascript
{
  state: {
    shifts: [],
    assignments: [],
    calendarEvents: [],
    loading: false
  },
  actions: {
    fetchShifts(),
    fetchAssignments(filters),
    createAssignment(data),
    updateAssignment(id, data),
    deleteAssignment(id),
    fetchCalendarEvents(month, year)
  }
}
```

### Sistema de Enrutamiento

#### Guards de Navegación

```javascript
router.beforeEach((to, from, next) => {
  const token = localStorage.getItem('token')
  const user = JSON.parse(localStorage.getItem('user'))
  const isAuthenticated = !!token
  
  // Rutas que requieren autenticación
  if (requiresAuth && !isAuthenticated) {
    next('/day-shifts')
    return
  }
  
  // Rutas que requieren rol ADMIN (Departamentos, Configuración)
  if (requiresAdmin && !user?.roles?.includes('ROLE_ADMIN')) {
    next({ name: 'Dashboard', query: { access: 'denied' } })
    return
  }
  
  // Rutas que requieren MANAGER o ADMIN
  if (requiresManagerOrAdmin && !(user?.roles?.includes('ROLE_ADMIN') || user?.roles?.includes('ROLE_MANAGER'))) {
    next({ name: 'Dashboard', query: { access: 'denied' } })
    return
  }
  
  next()
})
```

---

## 🔧 Servicios Backend

### GuardService

```php
class GuardService
{
    public function __construct(
        GuardRepository $guardRepository,
        GuardAssignmentRepository $assignmentRepository,
        EntityManagerInterface $entityManager,
        Security $security
    )
    
    public function getAllGuards(): array
    // ADMIN: devuelve todas las guardias
    // USER/MANAGER: devuelve solo guardias de su departamento
    
    public function getActiveGuards(): array
    // ADMIN: devuelve todas las guardias activas
    // USER/MANAGER: devuelve solo guardias activas de su departamento
    
    public function getGuardById(string $id): ?Guard
    // Verifica permisos por departamento
    
    public function createGuard(...): Guard
    // ADMIN/MANAGER pueden crear
    // MANAGER solo en su departamento
}
```

### DepartmentService

```php
class DepartmentService
{
    public function getAllDepartments(): array
    // ADMIN: devuelve todos los departamentos
    // USER/MANAGER: devuelve solo su departamento
    
    public function getDepartmentById(string $id): ?Department
    // Verifica permisos por departamento
}
```

### UserService

```php
class UserService
{
    public function getAllUsers(): array
    // ADMIN: devuelve todos los usuarios
    // USER/MANAGER: devuelve solo usuarios de su departamento
    
    public function getUserById(string $id): ?User
    // Verifica que el usuario pertenezca al mismo departamento
}
```

### CurrentUserTrait

```php
trait CurrentUserTrait
{
    private function getCurrentUser(Security $security): ?User
    // Obtiene el usuario autenticado
    
    private function getCurrentUserDepartment(Security $security): ?string
    // Obtiene el ID del departamento del usuario
    
    private function isAdmin(Security $security): bool
    // Verifica si es ADMIN
    
    private function isManagerOrAdmin(Security $security): bool
    // Verifica si es MANAGER o ADMIN
    
    private function checkWritePermissions(): ?JsonResponse
    // Verifica permisos de escritura (MANAGER o ADMIN)
    
    private function canManageDepartment(?Department $dept): ?JsonResponse
    // Verifica si puede gestionar un departamento
    
    private function canManageUser(?User $user): ?JsonResponse
    // Verifica si puede gestionar un usuario
    
    private function canManageGuard($guard): ?JsonResponse
    // Verifica si puede gestionar una guardia
}
```

---

## 📊 Flujos de Trabajo

### 1. Control de Acceso por Departamento

```
┌─────────────┐     ┌──────────────┐     ┌─────────────┐
│  Usuario    │────▶│  Login       │────▶│  Obtener    │
│  inicia     │     │              │     │  token JWT  │
│  sesión     │     │              │     │             │
└─────────────┘     └──────────────┘     └─────────────┘
                                              │
                                              ▼
                                       ┌─────────────┐
                                       │  Guardar    │
                                       │  token en   │
                                       │  localStorage│
                                       └─────────────┘
                                              │
                                              ▼
                                       ┌─────────────┐
                                       │  Fetch User │
                                       │  /api/auth/me│
                                       └─────────────┘
                                              │
                                              ▼
                                       ┌─────────────┐
                                       │  Guardar    │
                                       │  user con   │
                                       │  department │
                                       └─────────────┘
```

### 2. Filtrado de Datos por Departamento

```
┌─────────────┐     ┌──────────────┐     ┌─────────────┐
│  Frontend   │────▶│  Controller  │────▶│  Service    │
│  GET /guards│     │              │     │  (con Security)│
└─────────────┘     └──────────────┘     └─────────────┘
                                              │
                    ┌─────────────────────────┴──────────┐
                    ▼                                    ▼
             ┌─────────────┐                    ┌─────────────┐
             │  ADMIN      │                    │  USER/      │
             │  Todas las  │                    │  MANAGER    │
             │  guardias   │                    │  Solo su    │
             │             │                    │  departamento│
             └─────────────┘                    └─────────────┘
```

### 3. Creación de Guardia con Asignación

```
┌─────────────┐     ┌──────────────┐     ┌─────────────┐
│  MANAGER    │────▶│  Formulario  │────▶│  Validar    │
│  crea       │     │  GuardForm   │     │  departamento│
│  guardia    │     │              │     │             │
└─────────────┘     └──────────────┘     └─────────────┘
                                              │
                                              ▼
                                       ┌─────────────┐
                                       │  Verificar  │
                                       │  permisos   │
                                       │  (canManage)│
                                       └─────────────┘
                                              │
                                              ▼
                                       ┌─────────────┐
                                       │  Crear      │
                                       │  Guardia    │
                                       └─────────────┘
                                              │
                                              ▼
                                       ┌─────────────┐
                                       │  Crear      │
                                       │  Asignaciones│
                                       │  (por fechas)│
                                       └─────────────┘
```

### 4. Gestión de Roles (Solo ADMIN)

```
┌─────────────┐     ┌──────────────┐     ┌─────────────┐
│  ADMIN      │────▶│  Settings    │────▶│  Pestaña    │
│  accede a   │     │  View        │     │  Roles      │
│  Config.    │     │              │     │             │
└─────────────┘     └──────────────┘     └─────────────┘
                                              │
                    ┌─────────────────────────┴──────────┐
                    ▼                                    ▼
             ┌─────────────┐                    ┌─────────────┐
             │  Agregar    │                    │  Editar     │
             │  Rol        │                    │  Inline     │
             │  (nombre +  │                    │  (click en  │
             │  descrip.)  │                    │  lápiz)     │
             └─────────────┘                    └─────────────┘
                    │                                    │
                    └────────────────┬───────────────────┘
                                     ▼
                              ┌─────────────┐
                              │  POST/PUT   │
                              │  /api/roles │
                              └─────────────┘
```

---

## 🧪 Testing Strategy

### Backend Tests

```php
// tests/Service/GuardServiceTest.php
class GuardServiceTest extends TestCase
{
    public function testGetAllGuardsAsAdmin(): void;
    public function testGetAllGuardsAsUser(): void;
    public function testCreateGuardAsManager(): void;
    public function testCreateGuardAsUserForbidden(): void;
}

// tests/Controller/Api/DepartmentControllerTest.php
class DepartmentControllerTest extends ApiTestCase
{
    public function testGetDepartmentsAsAdmin(): void;
    public function testGetDepartmentsAsManager(): void;
    public function testCreateDepartmentAsUserForbidden(): void;
}
```

### Frontend Tests

```javascript
// tests/stores/auth.store.test.js
describe('authStore', () => {
  it('login successfully', async () => {});
  it('check isAdmin getter', () => {});
  it('check isManagerOrAdmin getter', () => {});
});

// tests/router/router.test.js
describe('Router Guards', () => {
  it('redirects non-admin from departments', () => {});
  it('redirects non-admin from settings', () => {});
  it('allows admin to access all routes', () => {});
});
```

---

## 🚀 Características Implementadas

### ✅ Autenticación y Autorización
- [x] Login con JWT
- [x] Logout
- [x] Obtener usuario actual
- [x] Cambio de contraseña
- [x] Guards de navegación por rol

### ✅ Control de Acceso por Departamento
- [x] Filtrado automático en servicios
- [x] Verificación en controladores
- [x] Trait reutilizable CurrentUserTrait
- [x] ADMIN ve todo, USER/MANAGER ven solo su departamento

### ✅ Gestión de Departamentos (Solo ADMIN)
- [x] Listar departamentos
- [x] Crear departamento
- [x] Editar departamento
- [x] Eliminar departamento
- [x] Restringido a ADMIN en frontend y backend

### ✅ Gestión de Usuarios
- [x] Listar usuarios (filtrado por departamento)
- [x] Crear usuario (MANAGER/ADMIN)
- [x] Editar usuario (MANAGER/ADMIN en su depto)
- [x] Eliminar usuario (MANAGER/ADMIN en su depto)
- [x] Asignar departamento y nivel

### ✅ Gestión de Guardias
- [x] Listar guardias (filtrado por departamento)
- [x] Crear guardia (MANAGER/ADMIN)
- [x] Editar guardia (MANAGER/ADMIN en su depto)
- [x] Eliminar guardia (MANAGER/ADMIN en su depto)
- [x] Asignación automática de usuarios al crear
- [x] Calendario de guardias

### ✅ Gestión de Roles (Solo ADMIN)
- [x] Listar roles en Configuración
- [x] Crear nuevo rol
- [x] Editar rol inline
- [x] Eliminar rol (si no está en uso)
- [x] Dropdown de roles en Usuarios

### ✅ Configuración (Solo ADMIN)
- [x] Pestaña Perfil y Seguridad
- [x] Cambio de contraseña
- [x] Pestaña Niveles de Usuario
- [x] Pestaña Roles del Sistema

### ✅ Backup y Restauración
- [x] Script de backup completo
- [x] Script de restauración
- [x] Script de gestión de backups
- [x] Documentación de backups

---

## 📦 Dependencias Principales

### Backend (composer.json)
```json
{
  "require": {
    "symfony/framework-bundle": "^7.0",
    "symfony/security-bundle": "^7.0",
    "doctrine/orm": "^3.0",
    "lexik/jwt-authentication-bundle": "^2.19",
    "symfony/validator": "^7.0",
    "nelmio/cors-bundle": "^2.4",
    "symfony/uid": "^7.0"
  },
  "require-dev": {
    "phpunit/phpunit": "^11.0",
    "symfony/maker-bundle": "^1.52",
    "doctrine/doctrine-fixtures-bundle": "^3.5"
  }
}
```

### Frontend (package.json)
```json
{
  "dependencies": {
    "vue": "^3.4",
    "vue-router": "^4.2",
    "pinia": "^2.1",
    "axios": "^1.6",
    "primevue": "^4.0",
    "@fullcalendar/vue3": "^6.1",
    "date-fns": "^3.0"
  },
  "devDependencies": {
    "vite": "^5.0",
    "@vitejs/plugin-vue": "^5.0",
    "vitest": "^1.0"
  }
}
```

---

## 🔐 Seguridad

### Configuración de Seguridad

```yaml
# config/packages/security.yaml
security:
  providers:
    app_user_provider:
      entity:
        class: App\Entity\User
        property: email

  firewalls:
    login:
      pattern: ^/api/auth/login
      stateless: true
      json_login:
        check_path: /api/auth/login
        username_path: email
        password_path: password
        success_handler: lexik_jwt_authentication.handler.authentication_success
        failure_handler: lexik_jwt_authentication.handler.authentication_failure

    api:
      pattern: ^/api
      stateless: true
      jwt: ~

  access_control:
    - { path: ^/api/auth/login, roles: PUBLIC_ACCESS }
    - { path: ^/api/auth/refresh, roles: PUBLIC_ACCESS }
    - { path: ^/api, roles: IS_AUTHENTICATED_FULLY }
```

### Validación de Permisos

```php
// En controladores
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class DepartmentController extends AbstractController
{
    use CurrentUserTrait;
    
    #[Route('', methods: ['POST'])]
    public function create(): JsonResponse
    {
        $error = $this->checkWritePermissions();
        if ($error) {
            return $error; // 403 Forbidden
        }
        
        // Solo ADMIN puede crear departamentos
        if (!$this->isAdmin($this->security)) {
            return new JsonResponse(
                ['error' => 'Solo ADMIN puede crear departamentos'],
                Response::HTTP_FORBIDDEN
            );
        }
    }
}
```

---

## 📊 URLs y Puertos

| Servicio | URL | Puerto |
|----------|-----|--------|
| Frontend | http://localhost:5173 | 5173 |
| Backend API | http://localhost:8000 | 8000 |
| phpMyAdmin | http://localhost:18080 | 18080 |
| MySQL | localhost | 13306 |

---

## 📝 Credenciales de Prueba

| Email | Password | Rol | Departamento |
|-------|----------|-----|--------------|
| `admin@example.com` | `admin123` | ADMIN | Tecnología |
| `manager@example.com` | `manager123` | MANAGER | Operaciones |
| `tech_user1@example.com` | `user123` | USER | Tecnología |
| `ops_user1@example.com` | `user123` | USER | Operaciones |
| `hr_user1@example.com` | `user123` | USER | Recursos Humanos |

---

*Documento generado para el Sistema de Guardias*
*Última actualización: 2026-03-10*
