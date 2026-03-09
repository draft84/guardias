# 🏗️ Arquitectura Sistema de Guardias

## 📋 Descripción General

Sistema de gestión de guardias para organizaciones con múltiples departamentos, usuarios, calendarización de turnos y administración de personal activo.

---

## 🛠️ Stack Tecnológico

### Backend
- **Framework:** Symfony 7.x
- **Lenguaje:** PHP 8.2+
- **Base de Datos:** MySQL 8.0 / PostgreSQL 15
- **ORM:** Doctrine ORM
- **API:** RESTful API
- **Autenticación:** JWT (LexikJWTAuthenticationBundle)
- **Validación:** Symfony Validator

### Frontend
- **Framework:** Vue.js 3.x (Composition API)
- **Build Tool:** Vite
- **UI Library:** PrimeVue / Bootstrap 5
- **Estado:** Pinia
- **Router:** Vue Router 4
- **HTTP Client:** Axios

### DevOps
- **Docker:** Contenedores para desarrollo
- **Testing:** PHPUnit (Backend), Vitest (Frontend)

---

## 📁 Estructura del Proyecto

```
guardias/
├── backend/                    # Symfony Application
│   ├── config/
│   │   ├── packages/
│   │   ├── routes/
│   │   └── bundles.php
│   ├── migrations/             # Doctrine Migrations
│   ├── public/
│   ├── src/
│   │   ├── Controller/
│   │   │   ├── Api/
│   │   │   │   ├── AuthController.php
│   │   │   │   ├── DepartmentController.php
│   │   │   │   ├── UserController.php
│   │   │   │   ├── GuardController.php
│   │   │   │   └── ShiftController.php
│   │   │   └── AdminController.php
│   │   ├── Entity/
│   │   │   ├── User.php
│   │   │   ├── Department.php
│   │   │   ├── Guard.php
│   │   │   ├── Shift.php
│   │   │   └── GuardAssignment.php
│   │   ├── Repository/
│   │   │   ├── UserRepository.php
│   │   │   ├── DepartmentRepository.php
│   │   │   ├── GuardRepository.php
│   │   │   ├── ShiftRepository.php
│   │   │   └── GuardAssignmentRepository.php
│   │   ├── DTO/
│   │   │   ├── CreateGuardDTO.php
│   │   │   ├── AssignGuardDTO.php
│   │   │   └── ShiftSwapDTO.php
│   │   ├── Service/
│   │   │   ├── GuardService.php
│   │   │   ├── ShiftService.php
│   │   │   ├── DepartmentService.php
│   │   │   ├── UserService.php
│   │   │   └── CalendarService.php
│   │   ├── EventListener/
│   │   │   └── JwtAuthenticationListener.php
│   │   ├── Security/
│   │   │   └── Voter/
│   │   │       ├── GuardVoter.php
│   │   │       └── DepartmentVoter.php
│   │   ├── Validator/
│   │   │   └── Constraints/
│   │   └── Kernel.php
│   ├── templates/
│   ├── tests/
│   │   ├── Controller/
│   │   ├── Entity/
│   │   └── Service/
│   ├── .env
│   ├── .env.local
│   ├── composer.json
│   └── symfony.lock
│
├── frontend/                   # Vue.js Application
│   ├── public/
│   ├── src/
│   │   ├── assets/
│   │   ├── components/
│   │   │   ├── common/
│   │   │   │   ├── AppHeader.vue
│   │   │   │   ├── AppSidebar.vue
│   │   │   │   ├── AppFooter.vue
│   │   │   │   ├── LoadingSpinner.vue
│   │   │   │   └── ConfirmDialog.vue
│   │   │   ├── auth/
│   │   │   │   └── LoginForm.vue
│   │   │   ├── departments/
│   │   │   │   ├── DepartmentList.vue
│   │   │   │   ├── DepartmentForm.vue
│   │   │   │   └── DepartmentDetail.vue
│   │   │   ├── users/
│   │   │   │   ├── UserList.vue
│   │   │   │   ├── UserForm.vue
│   │   │   │   └── UserDetail.vue
│   │   │   ├── guards/
│   │   │   │   ├── GuardList.vue
│   │   │   │   ├── GuardForm.vue
│   │   │   │   ├── GuardCalendar.vue
│   │   │   │   └── GuardDetail.vue
│   │   │   └── shifts/
│   │   │       ├── ShiftList.vue
│   │   │       ├── ShiftForm.vue
│   │   │       └── ShiftSwapModal.vue
│   │   ├── composables/
│   │   │   ├── useAuth.js
│   │   │   ├── useDepartments.js
│   │   │   ├── useUsers.js
│   │   │   ├── useGuards.js
│   │   │   └── useShifts.js
│   │   ├── layouts/
│   │   │   ├── AuthLayout.vue
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
│   │   ├── views/
│   │   │   ├── LoginView.vue
│   │   │   ├── DashboardView.vue
│   │   │   ├── DepartmentsView.vue
│   │   │   ├── UsersView.vue
│   │   │   ├── GuardsView.vue
│   │   │   ├── CalendarView.vue
│   │   │   └── SettingsView.vue
│   │   ├── utils/
│   │   │   ├── validators.js
│   │   │   ├── formatters.js
│   │   │   └── constants.js
│   │   ├── App.vue
│   │   └── main.js
│   ├── index.html
│   ├── package.json
│   ├── vite.config.js
│   └── tailwind.config.js
│
├── docker/                     # Docker Configuration
│   ├── docker-compose.yml
│   ├── Dockerfile.backend
│   ├── Dockerfile.frontend
│   └── nginx/
│       └── default.conf
│
├── docs/                       # Documentación
│   ├── API.md
│   ├── DATABASE.md
│   └── DEPLOYMENT.md
│
├── .gitignore
├── README.md
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
  department: ManyToOne -> Department
  createdAt: datetime
  updatedAt: datetime
  lastLogin: datetime (nullable)
  guardAssignments: OneToMany -> GuardAssignment
```

#### 3. Guard (Guardia - Tipo de Servicio)
```yaml
Guard:
  id: UUID (Primary Key)
  name: string (not null)
  code: string (unique, not null)
  description: text (nullable)
  department: ManyToOne -> Department
  startTime: time (not null)
  endTime: time (not null)
  duration: integer (minutes, calculated)
  active: boolean (default: true)
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
  createdAt: datetime
  updatedAt: datetime
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
  swappedAt: datetime (nullable)
```

#### 6. ShiftSwapRequest (Solicitud de Cambio de Turno)
```yaml
ShiftSwapRequest:
  id: UUID (Primary Key)
  originalAssignment: ManyToOne -> GuardAssignment
  newUser: ManyToOne -> User
  requestedBy: ManyToOne -> User
  requestedAt: datetime
  status: enum ['pending', 'approved', 'rejected']
  approvedBy: ManyToOne -> User (nullable)
  approvedAt: datetime (nullable)
  reason: text (nullable)
  rejectionReason: text (nullable)
```

---

## 🔐 Sistema de Autenticación y Autorización

### Roles de Usuario

```php
ROLE_ADMIN      // Administrador del sistema - acceso completo
ROLE_MANAGER    // Gestor de departamento - gestión de su departamento
ROLE_USER       // Usuario estándar - solo visualización
```

### Endpoints de Autenticación

```yaml
POST /api/auth/login:
  description: Autenticar usuario y obtener JWT
  body: { email, password }
  response: { token, user }

POST /api/auth/logout:
  description: Invalidar token (opcional con JWT)
  headers: { Authorization: Bearer <token> }

GET /api/auth/me:
  description: Obtener usuario autenticado
  headers: { Authorization: Bearer <token> }
  response: { user }

POST /api/auth/refresh:
  description: Refresh token
  headers: { Authorization: Bearer <token> }
  response: { token }
```

---

## 🌐 API RESTful

### Endpoints por Módulo

#### Auth
```
POST   /api/auth/login
POST   /api/auth/logout
GET    /api/auth/me
POST   /api/auth/refresh
```

#### Departments
```
GET    /api/departments              # Listar departamentos
POST   /api/departments              # Crear departamento
GET    /api/departments/{id}         # Obtener departamento
PUT    /api/departments/{id}         # Actualizar departamento
DELETE /api/departments/{id}         # Eliminar departamento
GET    /api/departments/{id}/users   # Obtener usuarios del departamento
```

#### Users
```
GET    /api/users                    # Listar usuarios
POST   /api/users                    # Crear usuario
GET    /api/users/{id}               # Obtener usuario
PUT    /api/users/{id}               # Actualizar usuario
DELETE /api/users/{id}               # Eliminar usuario
GET    /api/users/{id}/guards        # Obtener guardias del usuario
```

#### Guards
```
GET    /api/guards                   # Listar guardias
POST   /api/guards                   # Crear guardia
GET    /api/guards/{id}              # Obtener guardia
PUT    /api/guards/{id}              # Actualizar guardia
DELETE /api/guards/{id}              # Eliminar guardia
GET    /api/guards/{id}/assignments  # Obtener asignaciones
GET    /api/guards/active            # Obtener guardias activas
```

#### Guard Assignments
```
GET    /api/assignments                      # Listar asignaciones
POST   /api/assignments                      # Crear asignación
GET    /api/assignments/{id}                 # Obtener asignación
PUT    /api/assignments/{id}                 # Actualizar asignación
DELETE /api/assignments/{id}                 # Eliminar asignación
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
POST   /api/shifts                   # Crear turno
GET    /api/shifts/{id}              # Obtener turno
PUT    /api/shifts/{id}              # Actualizar turno
DELETE /api/shifts/{id}              # Eliminar turno
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
│  │  Guardias   │  │  DashboardView                 │  │ │
│  │  Usuarios   │  │  - Resumen de guardias         │  │ │
│  │  Calendario │  │  - Próximas guardias           │  │ │
│  │             │  │  - Estadísticas                │  │ │
│  │             │  └────────────────────────────────┘  │ │
│  │             │                                      │ │
│  │             │  ┌────────────────────────────────┐  │ │
│  │             │  │  GuardsView                    │  │ │
│  │             │  │  - Lista de guardias           │  │ │
│  │             │  │  - Crear/Editar guardia        │  │ │
│  │             │  └────────────────────────────────┘  │ │
│  │             │                                      │ │
│  │             │  ┌────────────────────────────────┐  │ │
│  │             │  │  CalendarView                  │  │ │
│  │             │  │  - Calendario mensual          │  │ │
│  │             │  │  - Días con guardia (marcados) │  │ │
│  │             │  │  - Personal activo por día     │  │ │
│  │             │  │  - Horas de actividad          │  │ │
│  │             │  └────────────────────────────────┘  │ │
│  │             │                                      │ │
│  │             │  ┌────────────────────────────────┐  │ │
│  │             │  │  UsersView                     │  │ │
│  │             │  │  - Lista de usuarios           │  │ │
│  │             │  │  - Por departamento            │  │ │
│  │             │  └────────────────────────────────┘  │ │
│  │             │                                      │ │
│  │             │  ┌────────────────────────────────┐  │ │
│  │             │  │  DepartmentsView               │  │ │
│  │             │  │  - Lista de departamentos      │  │ │
│  │             │  └────────────────────────────────┘  │ │
│  └─────────────┴──────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────┘
```

### Stores (Pinia)

```javascript
// auth.store.js
{
  user: null,
  token: null,
  isAuthenticated: false,
  actions: {
    login(credentials),
    logout(),
    fetchUser(),
    refreshToken()
  }
}

// department.store.js
{
  departments: [],
  currentDepartment: null,
  loading: false,
  actions: {
    fetchDepartments(),
    fetchDepartment(id),
    createDepartment(data),
    updateDepartment(id, data),
    deleteDepartment(id)
  }
}

// user.store.js
{
  users: [],
  currentUser: null,
  loading: false,
  actions: {
    fetchUsers(departmentId?),
    fetchUser(id),
    createUser(data),
    updateUser(id, data),
    deleteUser(id)
  }
}

// guard.store.js
{
  guards: [],
  currentGuard: null,
  activeGuards: [],
  loading: false,
  actions: {
    fetchGuards(),
    fetchGuard(id),
    createGuard(data),
    updateGuard(id, data),
    deleteGuard(id),
    fetchActiveGuards()
  }
}

// shift.store.js
{
  shifts: [],
  assignments: [],
  calendarEvents: [],
  loading: false,
  actions: {
    fetchShifts(),
    fetchAssignments(filters),
    createAssignment(data),
    updateAssignment(id, data),
    deleteAssignment(id),
    requestSwap(assignmentId, newUserId),
    approveSwap(swapId),
    rejectSwap(swapId),
    fetchCalendarEvents(month, year)
  }
}
```

### Componentes Principales

#### GuardCalendar.vue
```vue
<template>
  <div class="calendar-container">
    <FullCalendar :options="calendarOptions" />
    <GuardModal v-model="showModal" :guard="selectedGuard" />
  </div>
</template>

<script setup>
// - Calendario mensual interactivo
// - Marcadores visuales para días con guardia
// - Click en día muestra personal activo
// - Click en guardia abre modal con detalles
// - Drag & drop para reasignar (admin)
</script>
```

#### GuardList.vue
```vue
<template>
  <div class="guard-list">
    <DataTable :value="guards">
      <Column field="name" header="Nombre" />
      <Column field="department.name" header="Departamento" />
      <Column field="startTime" header="Inicio" />
      <Column field="endTime" header="Fin" />
      <Column field="active" header="Estado">
        <template #body="{ data }">
          <Badge :severity="data.active ? 'success' : 'danger'" />
        </template>
      </Column>
      <Column header="Acciones">
        <Button icon="pi pi-edit" @click="editGuard" />
        <Button icon="pi pi-trash" @click="deleteGuard" />
      </Column>
    </DataTable>
  </div>
</template>
```

#### ShiftSwapModal.vue
```vue
<template>
  <Dialog v-model:visible="visible">
    <h3>Solicitar Cambio de Turno</h3>
    <Select v-model="newUser" :options="availableUsers" />
    <Textarea v-model="reason" placeholder="Motivo del cambio" />
    <Button @click="submitSwap">Solicitar</Button>
  </Dialog>
</template>
```

---

## 📊 Flujos de Trabajo

### 1. Creación de Guardia

```
┌─────────────┐     ┌──────────────┐     ┌─────────────┐
│  Usuario    │────▶│  Formulario  │────▶│  Servicio   │
│  (Admin)    │     │  GuardForm   │     │  GuardService│
└─────────────┘     └──────────────┘     └─────────────┘
                                              │
                                              ▼
                                       ┌─────────────┐
                                       │   API POST  │
                                       │  /guards    │
                                       └─────────────┘
                                              │
                                              ▼
                                       ┌─────────────┐
                                       │  Database   │
                                       │  INSERT     │
                                       └─────────────┘
                                              │
                                              ▼
                                       ┌─────────────┐
                                       │  Response   │
                                       │  201 Created│
                                       └─────────────┘
```

### 2. Asignación de Guardia

```
┌─────────────┐     ┌──────────────┐     ┌─────────────┐
│  Usuario    │────▶│  Selecciona  │────▶│  Validar    │
│  (Admin)    │     │  Usuario +   │     │  Disponibilidad│
│             │     │  Fecha       │     │             │
└─────────────┘     └──────────────┘     └─────────────┘
                                              │
                                              ▼
                                       ┌─────────────┐
                                       │  Crear      │
                                       │  GuardAssignment│
                                       └─────────────┘
                                              │
                                              ▼
                                       ┌─────────────┐
                                       │  Actualizar │
                                       │  Calendario │
                                       └─────────────┘
```

### 3. Cambio de Turno

```
┌─────────────┐     ┌──────────────┐     ┌─────────────┐
│  Usuario    │────▶│  Solicita    │────▶│  Crear      │
│             │     │  Cambio      │     │  ShiftSwap  │
└─────────────┘     └──────────────┘     └─────────────┘
                                              │
                                              ▼
                                       ┌─────────────┐
                                       │  Notificar  │
                                       │  a Admin    │
                                       └─────────────┘
                                              │
                    ┌─────────────────────────┴──────────┐
                    ▼                                    ▼
             ┌─────────────┐                    ┌─────────────┐
             │  Aprobar    │                    │  Rechazar   │
             │  (Admin)    │                    │  (Admin)    │
             └─────────────┘                    └─────────────┘
                    │                                    │
                    ▼                                    ▼
             ┌─────────────┐                    ┌─────────────┐
             │  Actualizar │                    │  Notificar  │
             │  Assignment │                    │  Rechazo    │
             │  Status     │                    │             │
             └─────────────┘                    └─────────────┘
```

---

## 🔧 Servicios Backend

### GuardService

```php
class GuardService
{
    public function createGuard(CreateGuardDTO $dto): Guard;
    public function updateGuard(Uuid $id, CreateGuardDTO $dto): Guard;
    public function deleteGuard(Uuid $id): void;
    public function getActiveGuards(): array;
    public function getGuardsByDepartment(Uuid $departmentId): array;
    public function assignGuard(AssignGuardDTO $dto): GuardAssignment;
    public function unassignGuard(Uuid $assignmentId): void;
}
```

### ShiftService

```php
class ShiftService
{
    public function createShift(ShiftDTO $dto): Shift;
    public function updateShift(Uuid $id, ShiftDTO $dto): Shift;
    public function deleteShift(Uuid $id): void;
    public function requestSwap(ShiftSwapDTO $dto): ShiftSwapRequest;
    public function approveSwap(Uuid $swapId, User $approver): ShiftSwapRequest;
    public function rejectSwap(Uuid $swapId, User $approver, string $reason): ShiftSwapRequest;
    public function getSwapRequests(User $user): array;
}
```

### CalendarService

```php
class CalendarService
{
    public function getCalendarEvents(int $month, int $year, ?Uuid $departmentId = null): array;
    public function getGuardsByDate(\DateTimeInterface $date, ?Uuid $departmentId = null): array;
    public function getUserGuards(Uuid $userId, \DateTimeInterface $start, \DateTimeInterface $end): array;
    public function getActiveGuardsAtTime(\DateTimeInterface $datetime): array;
}
```

---

## 🧪 Testing Strategy

### Backend Tests

```php
// tests/Service/GuardServiceTest.php
class GuardServiceTest extends TestCase
{
    public function testCreateGuard(): void;
    public function testUpdateGuard(): void;
    public function testDeleteGuard(): void;
    public function testAssignGuard(): void;
    public function testCannotAssignInactiveGuard(): void;
    public function testCannotAssignOverlappingGuard(): void;
}

// tests/Controller/Api/GuardControllerTest.php
class GuardControllerTest extends ApiTestCase
{
    public function testGetGuards(): void;
    public function testCreateGuard(): void;
    public function testCreateGuardRequiresAdminRole(): void;
    public function testUpdateGuard(): void;
    public function testDeleteGuard(): void;
}
```

### Frontend Tests

```javascript
// tests/stores/guard.store.test.js
describe('guardStore', () => {
  it('fetches guards successfully', async () => {});
  it('creates a new guard', async () => {});
  it('handles fetch error', async () => {});
});

// tests/components/GuardCalendar.test.js
describe('GuardCalendar', () => {
  it('renders calendar with events', () => {});
  it('shows guard details on click', () => {});
  it('filters by department', () => {});
});
```

---

## 🚀 Plan de Implementación

### Fase 1: Setup Inicial (Día 1-2)
- [ ] Configurar proyecto Symfony
- [ ] Configurar proyecto Vue.js
- [ ] Configurar Docker
- [ ] Configurar base de datos
- [ ] Configurar JWT

### Fase 2: Entidades y Migraciones (Día 3-4)
- [ ] Crear entidades Doctrine
- [ ] Generar migraciones
- [ ] Crear repositories
- [ ] Seeders de datos iniciales

### Fase 3: API Backend (Día 5-10)
- [ ] Controller de Autenticación
- [ ] Controller de Departamentos
- [ ] Controller de Usuarios
- [ ] Controller de Guardias
- [ ] Controller de Turnos
- [ ] Services y DTOs
- [ ] Validadores
- [ ] Tests unitarios

### Fase 4: Frontend Base (Día 11-15)
- [ ] Configurar Vue Router
- [ ] Configurar Pinia stores
- [ ] Crear servicios API
- [ ] Layout principal
- [ ] Login view
- [ ] Dashboard view

### Fase 5: Módulos Frontend (Día 16-25)
- [ ] Módulo de Departamentos
- [ ] Módulo de Usuarios
- [ ] Módulo de Guardias
- [ ] Calendario de Guardias
- [ ] Gestión de Cambios de Turno

### Fase 6: Testing y QA (Día 26-28)
- [ ] Tests E2E
- [ ] Tests de integración
- [ ] Bug fixing
- [ ] Optimización

### Fase 7: Deploy (Día 29-30)
- [ ] Configurar producción
- [ ] Deploy
- [ ] Monitoreo

---

## 📦 Dependencias Principales

### Backend (composer.json)
```json
{
  "require": {
    "symfony/framework-bundle": "^7.0",
    "symfony/security-bundle": "^7.0",
    "doctrine/orm": "^2.17",
    "lexik/jwt-authentication-bundle": "^2.19",
    "symfony/validator": "^7.0",
    "nelmio/cors-bundle": "^2.4"
  },
  "require-dev": {
    "phpunit/phpunit": "^10.0",
    "symfony/maker-bundle": "^1.52",
    "dama/doctrine-test-bundle": "^8.0"
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
    "primevue": "^3.47",
    "@fullcalendar/vue3": "^6.1",
    "date-fns": "^3.0"
  },
  "devDependencies": {
    "vite": "^5.0",
    "@vitejs/plugin-vue": "^5.0",
    "vitest": "^1.0",
    "@vue/test-utils": "^2.4"
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
    - { path: ^/api, roles: IS_AUTHENTICATED_FULLY }
```

---

## 📈 Métricas y Monitoreo

### Dashboard Metrics
- Total de guardias activas
- Guardias por departamento
- Usuarios activos
- Cambios de turno pendientes
- Guardias completadas este mes

### Logs y Auditoría
- Logs de autenticación
- Logs de cambios en guardias
- Logs de solicitudes de cambio
- Auditoría de acciones de administradores

---

## 📝 Convenciones de Código

### Backend (PHP)
- PSR-12 coding standards
- Type hints obligatorios
- PHPDoc en métodos públicos
- Tests por cada service/controller

### Frontend (Vue.js)
- Composition API con `<script setup>`
- Nombres de componentes en PascalCase
- Props con validación de tipos
- Tests de componentes críticos

---

## 🎯 Criterios de Aceptación

### Funcionales
- ✅ CRUD completo de departamentos
- ✅ CRUD completo de usuarios
- ✅ CRUD completo de guardias
- ✅ Asignación de guardias a usuarios
- ✅ Calendario con visualización mensual
- ✅ Días marcados con personal de guardia
- ✅ Visualización de horas activas por guardia
- ✅ Sistema de cambio de turnos
- ✅ Login de administradores
- ✅ Filtrado por departamento

### No Funcionales
- ✅ Tiempo de respuesta < 200ms
- ✅ Soporte para 100+ usuarios concurrentes
- ✅ Responsive design
- ✅ Tests con cobertura > 80%
- ✅ Documentación API completa

---

## 📞 Soporte y Mantenimiento

### Issues Comunes
1. **Problemas de autenticación:** Verificar expiración de JWT
2. **Conflictos de horario:** Validar solapamientos antes de asignar
3. **Rendimiento de calendario:** Implementar paginación de eventos

### Backup y Recovery
- Backup diario de base de datos
- Retención de 30 días
- Scripts de recovery automatizados

---

*Documento creado para guiar el desarrollo del Sistema de Guardias*
*Versión: 1.0 | Fecha: 2026-03-03*
