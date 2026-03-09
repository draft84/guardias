# 🎉 Sistema de Guardias - Servicios Levantados

## ✅ Servicios Disponibles

Una vez que los contenedores Docker estén corriendo, podrás acceder a:

### 🔹 Frontend (Vue.js)
- **URL:** http://localhost:5173
- **Descripción:** Aplicación web del sistema de guardias
- **Estado:** En desarrollo con Vite (HMR activado)

### 🔹 Backend API (Symfony)
- **URL:** http://localhost:8000
- **API Base:** http://localhost:8000/api
- **Descripción:** API RESTful con autenticación JWT
- **Endpoints principales:**
  - `POST /api/auth/login` - Login
  - `GET /api/auth/me` - Usuario actual
  - `GET /api/departments` - Listar departamentos
  - `GET /api/users` - Listar usuarios
  - `GET /api/guards` - Listar guardias
  - `GET /api/assignments/calendar` - Calendario de guardias

### 🔹 phpMyAdmin (MySQL)
- **URL:** http://localhost:8080
- **Usuario:** `guardias_user`
- **Contraseña:** `guardias_password`
- **Base de datos:** `guardias`

### 🔹 MySQL Database
- **Host:** localhost
- **Puerto:** 3306
- **Usuario:** `guardias_user`
- **Contraseña:** `guardias_password`
- **Base de datos:** `guardias`

---

## 📋 Comandos Útiles

### Ver logs de los contenedores
```bash
cd docker
docker-compose logs -f
```

### Ver logs de un servicio específico
```bash
docker-compose logs -f backend
docker-compose logs -f frontend
docker-compose logs -f database
docker-compose logs -f phpmyadmin
```

### Detener servicios
```bash
docker-compose down
```

### Reiniciar servicios
```bash
docker-compose down
docker-compose up -d
```

### Ejecutar migraciones (dentro del contenedor)
```bash
docker-compose exec backend php bin/console doctrine:database:create
docker-compose exec backend php bin/console make:migration
docker-compose exec backend php bin/console doctrine:migrations:migrate
```

### Acceder a la consola de Symfony
```bash
docker-compose exec backend php bin/console
```

### Acceder al contenedor del backend
```bash
docker-compose exec backend bash
```

---

## 🧪 Probar la API

### Login (obtener token JWT)
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@example.com","password":"admin123"}'
```

### Obtener usuario autenticado
```bash
curl -X GET http://localhost:8000/api/auth/me \
  -H "Authorization: Bearer TU_TOKEN_JWT"
```

### Listar departamentos
```bash
curl -X GET http://localhost:8000/api/departments \
  -H "Authorization: Bearer TU_TOKEN_JWT"
```

---

## 📝 Notas Importantes

1. **Primer inicio:** Las primeras veces que levantes los contenedores puede tomar varios minutos mientras se descargan las imágenes de Docker.

2. **Puertos:** Si algún puerto ya está en uso en tu sistema, modifica el archivo `docker-compose.yml` para usar puertos diferentes.

3. **Datos persistentes:** Los datos de MySQL se guardan en un volumen Docker llamado `mysql_data`. Para borrar los datos:
   ```bash
   docker-compose down -v
   ```

4. **Frontend:** El frontend se recarga automáticamente gracias a Vite HMR cuando modificas archivos en `frontend/src/`.

5. **Backend:** Después de modificar código PHP, es posible que necesites limpiar la caché:
   ```bash
   docker-compose exec backend php bin/console cache:clear
   ```

---

## 🐛 Solución de Problemas

### Error: "Cannot connect to the Docker daemon"
```bash
# Asegúrate de que Docker Desktop esté corriendo
# En macOS, abre Docker Desktop desde Applications
```

### Error: "Port already in use"
```bash
# Verifica qué proceso está usando el puerto
lsof -i :5173  # Para frontend
lsof -i :8000  # Para backend
lsof -i :3306  # Para MySQL
lsof -i :8080  # Para phpMyAdmin

# Detén el proceso o cambia el puerto en docker-compose.yml
```

### Error: "Database connection failed"
```bash
# Verifica que el contenedor de MySQL esté corriendo
docker-compose ps database

# Revisa los logs de MySQL
docker-compose logs database

# Espera unos segundos más, MySQL puede tardar en iniciar
```

### Error: "npm install failed" en el frontend
```bash
# Limpia el caché de npm
sudo chown -R $(whoami) ~/.npm

# Luego intenta de nuevo
cd frontend
npm install
```

---

## 📚 Estructura del Proyecto

```
guardias/
├── backend/                    # Symfony 7.2
│   ├── config/
│   ├── src/
│   │   ├── Controller/Api/    # Controladores REST
│   │   ├── Entity/            # Entidades Doctrine
│   │   ├── Repository/        # Repositorios
│   │   └── Service/           # Servicios
│   └── .env                   # Variables de entorno
│
├── frontend/                   # Vue.js 3 + Vite
│   └── src/
│       ├── router/            # Rutas
│       ├── stores/            # Pinia stores
│       ├── services/          # Servicios API
│       ├── views/             # Vistas
│       ├── layouts/           # Layouts
│       └── components/        # Componentes
│
├── docker/
│   ├── docker-compose.yml     # Configuración Docker
│   ├── Dockerfile.backend
│   ├── Dockerfile.frontend
│   └── nginx/
│       └── default.conf
│
├── ARQUITECTURA.md            # Arquitectura completa
├── INSTRUCCIONES.md           # Instrucciones detalladas
└── README.md                  # Este archivo
```

---

## 🚀 Siguientes Pasos

1. **Esperar a que los contenedores estén listos** (2-5 minutos la primera vez)

2. **Verificar que todos los servicios estén corriendo:**
   ```bash
   docker-compose ps
   ```

3. **Ejecutar las migraciones de la base de datos:**
   ```bash
   docker-compose exec backend php bin/console doctrine:database:create
   docker-compose exec backend php bin/console make:migration
   docker-compose exec backend php bin/console doctrine:migrations:migrate
   ```

4. **Crear un usuario administrador (fixtures):**
   ```bash
   # Opcional: crear datos de prueba
   docker-compose exec backend php bin/console doctrine:fixtures:load
   ```

5. **Acceder al frontend:** http://localhost:5173

6. **Hacer login con:**
   - Email: `admin@example.com`
   - Password: `admin123`

---

*Documento generado automáticamente - Sistema de Guardias*
*Fecha: 2026-03-03*
