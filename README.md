# 🩸 DonaVida – Red de donantes de sangre

Plataforma web que conecta a los hospitales de Cochabamba con donantes voluntarios de sangre, filtrando por compatibilidad sanguínea, cercanía y disponibilidad médica del donante.

Flujo principal: **solicitud → notificación → cita → donación registrada**.

## Estructura del repositorio

```
donavida/
├── backend/            # API REST – Laravel 13 + Sanctum + MySQL
├── frontend/           # SPA – React + TypeScript + Vite + Tailwind
├── database/
│   └── donavida.sql    # Script SQL equivalente a las migraciones (documentación y diagrama ER)
├── TAREAS.md           # División del trabajo y forma de trabajar con Git
└── README.md
```

## Requisitos

- PHP 8.3 o superior y [Composer](https://getcomposer.org/)
- MySQL 8 o XAMPP (MariaDB)
- Node.js 22 o superior
- Git

## Instalación (cada integrante, la primera vez)

### 1. Clonar

```bash
git clone https://github.com/USUARIO/donavida.git
cd donavida
git checkout develop
```

### 2. Base de datos

Crear una base vacía llamada `donavida` (en phpMyAdmin o con `CREATE DATABASE donavida;`).

### 3. Backend

```bash
cd backend
composer install
cp .env.example .env          # en Windows: copy .env.example .env
php artisan key:generate
```

Revisar en `.env` el usuario y contraseña de MySQL (`DB_USERNAME`, `DB_PASSWORD`). Después:

```bash
php artisan migrate:fresh --seed   # crea las 10 tablas y carga los datos de prueba
php artisan serve                  # API en http://localhost:8000
```

Probar en el navegador: http://localhost:8000/api/estado y http://localhost:8000/api/tipos-sangre

### 4. Frontend (en otra terminal)

```bash
cd frontend
npm install
cp .env.example .env          # en Windows: copy .env.example .env
npm run dev                   # http://localhost:5173
```

Si la página muestra los 8 tipos de sangre, frontend y backend están conectados ✅

## Probar el módulo de hospitales en Thunder Client

1. Con la API iniciada y los seeders cargados, importa la [colección del módulo 3](docs/postman/DonaVida-Modulo3-Hospitales.postman_collection.json) desde **Collections → Import**. Si las variables no aparecen, agrégalas a un entorno activo. Usa `base_url=http://127.0.0.1:8000/api`.
2. Ejecuta **Consultar catálogo de tipos de sangre** y guarda el ID de `A+` en `tipo_sangre_id`. Inicia sesión como Hospital Central y copia el campo `token` en `token_hospital`.
3. Ejecuta **Ver perfil** y **Listar solicitudes del hospital**. Completa `fecha_limite` con una fecha futura en formato ISO 8601 con `-04:00` para Bolivia. Publica la solicitud y guarda `solicitud.id` en `solicitud_id`.
4. Ejecuta **Buscar donantes compatibles por distancia**. Con los datos de prueba originales aparecen `O-` y `A+` en ese orden. Los donantes elegibles sin coordenadas quedan al final con `distancia_km: null`.
5. Edita la solicitud y cancélala al final. Usa **Ver solicitud** para confirmar que el registro conserva el estado `cancelada`.

## Cuentas de prueba

Todas usan la contraseña `password123`.

| Rol | Correo | Para probar |
|---|---|---|
| Administrador | admin@donavida.test | Validar hospitales |
| Hospital | central@donavida.test | Hospital verificado con 1 solicitud y 1 campaña |
| Hospital | norte@donavida.test | Hospital **sin verificar** |
| Donante O- | ana@donavida.test | Nunca donó: puede donar |
| Donante O+ | luis@donavida.test | Donó hace 1 mes: **no** puede donar |
| Donante A+ | sofia@donavida.test | Donó hace 4 meses: puede donar |
| Donante AB+ | diego@donavida.test | Receptor universal |

## Modelo de datos (10 tablas)

`roles`, `usuarios`, `tipos_sangre`, `donantes`, `hospitales`, `solicitudes`, `campanas`, `citas`, `donaciones`, `notificaciones`.

Laravel crea además tablas internas (`sessions`, `cache`, `jobs`, `personal_access_tokens`, `password_reset_tokens`). Son del framework y no forman parte del modelo del negocio.

Reglas de negocio importantes:

- Un donante solo puede agendar una cita si pasaron **3 meses** desde su última donación (`Donante::puedeDonarHoy()`).
- Solo pueden donar personas de **18 a 60 años** (Ley 1687, art. 19).
- Una cita pertenece a una solicitud **o** a una campaña, nunca a las dos (restricción `CHECK` en la base de datos).
- Nada se borra: los registros cambian de `estado`.
