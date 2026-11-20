# División del trabajo – DonaVida

Los 4 integrantes trabajan en **backend y frontend**. Cada uno es dueño de un módulo completo: hace su API en Laravel y luego las pantallas en React que la consumen.

## Integrantes y módulos

### 👤 Mayerli — Autenticación, usuarios y notificaciones
**Tablas:** `usuarios`, `roles`, `notificaciones`

Backend:
- [ ] `POST /api/registro`: registro de donante (crea usuario + donante) y de hospital (crea usuario + hospital con `verificado = false`)
- [ ] `POST /api/login` y `POST /api/logout` con Laravel Sanctum (tokens)
- [ ] Middleware de roles (`administrador`, `hospital`, `donante`)
- [ ] Recuperación de contraseña por correo
- [ ] Administrador: listar y validar hospitales
- [ ] Notificaciones: listar, marcar como leída, contador de no leídas
- [ ] Tiempo real con Laravel Reverb (`php artisan install:broadcasting`)

Frontend:
- [ ] Login y registro
- [ ] Guardar el token y rutas protegidas por rol
- [ ] Layout general con menú según el rol
- [ ] Campanita de notificaciones con Laravel Echo

### 👤 Maya — Donantes
**Tablas:** `donantes`, `tipos_sangre`

Backend:
- [x] Ver y editar el perfil del donante (tipo de sangre, ubicación, disponibilidad)
- [x] Validar edad de 18 a 60 años al registrar
- [x] Historial de donaciones del donante
- [x] Endpoint "¿puedo donar?" usando `Donante::puedeDonarHoy()` (regla de los 3 meses)
- [x] Pruebas automáticas de la regla de los 3 meses

Frontend:
- [x] Portal del donante: perfil, mapa (Leaflet) para marcar su ubicación
- [x] Historial de donaciones e insignias ("Héroe nivel X")

### 👤 Mati — Hospitales, solicitudes y emparejamiento
**Tablas:** `hospitales`, `solicitudes`

Backend:
- [ ] Perfil del hospital (editar datos, ubicación, WhatsApp)
- [ ] CRUD de solicitudes urgentes (cancelar = cambiar `estado`, no borrar)
- [ ] **Emparejamiento:** buscar donantes con tipo compatible (`puede_recibir_de`), disponibles, que puedan donar hoy, ordenados por distancia (fórmula de Haversine)
- [ ] Al publicar una solicitud, crear una notificación para cada donante compatible (usa el modelo de A)

Frontend:
- [ ] Panel del hospital: listado y formulario de solicitudes
- [ ] Mapa con el hospital y los donantes compatibles cercanos
- [ ] Botón "Abrir WhatsApp" del hospital (link `wa.me`)

### 👤 Mollo — Citas, donaciones, campañas y reportes
**Tablas:** `citas`, `donaciones`, `campanas`

Backend:
- [ ] Donante acepta una solicitud o campaña y elige horario: crea la cita **solo si** `puedeDonarHoy()` es verdadero
- [ ] Hospital confirma asistencia (`asistio` / `no_asistio`)
- [ ] Registrar donación: crea la donación, actualiza `fecha_ultima_donacion` del donante y `unidades_confirmadas` de la solicitud
- [ ] CRUD de campañas con control de cupos
- [ ] Indicadores para el panel (donaciones por tipo de sangre, solicitudes por estado)
- [ ] Correo de confirmación de cita (Mailtrap)

Frontend:
- [ ] Donante: ver solicitudes/campañas, aceptar y elegir horario, ver mis citas
- [ ] Hospital: agenda de citas, marcar asistencia, registrar donación
- [ ] Campañas y dashboard con indicadores

### Tareas compartidas (al final)
- [ ] Documentación de la API con Scribe y colección de Postman
- [ ] Pruebas generales y corrección de errores
- [ ] Preparar la defensa

## Orden de trabajo

| Fase | Qué se hace | Quién |
|---|---|---|
| 0 | Repositorio, base de datos (migraciones, modelos, seeders) y estructura del frontend | ✅ Listo |
| 1 | Backend de cada módulo + pruebas en Postman | Los 4, en paralelo |
| 2 | Frontend de cada módulo | Los 4, en paralelo |
| 3 | Integración: tiempo real, correos, documentación, pruebas | Los 4 |

**Mientras A termina el login**, los demás pueden probar sus rutas protegidas creando un token con los usuarios de prueba:

```bash
php artisan tinker
>>> App\Models\Usuario::where('email', 'central@donavida.test')->first()->createToken('pruebas')->plainTextToken
```

Ese token se usa en Postman como `Authorization: Bearer <token>`.

## Cómo trabajamos con Git

### Ramas

- `main` → versión estable, solo para entregas y presentaciones.
- `develop` → donde se junta el trabajo de todos.
- `feature/<modulo>-<tarea>` → una rama por tarea. Ejemplos: `feature/auth-login`, `feature/donantes-perfil`, `feature/solicitudes-emparejamiento`, `feature/citas-registrar-donacion`.

### Flujo de cada tarea

```bash
git checkout develop
git pull origin develop                      # traer lo último
git checkout -b feature/donantes-perfil      # crear tu rama

# ... programar ...

git add .
git commit -m "feat(donantes): editar perfil del donante"
git push -u origin feature/donantes-perfil
```

Después, en GitHub: **Pull Request de tu rama hacia `develop`**. Otro integrante lo revisa y lo aprueba.

### Mensajes de commit

- `feat(modulo): ...` → algo nuevo
- `fix(modulo): ...` → corrección de un error
- `docs: ...` → documentación
- `refactor(modulo): ...` → mejorar código sin cambiar lo que hace

### Reglas

1. Nunca subir el archivo `.env`.
2. Nadie programa directo en `main` ni en `develop`.
3. Para cambiar la base de datos se crea una **nueva migración** (`php artisan make:migration ...`), se avisa al grupo y se actualiza `database/donavida.sql`.
4. Si alguien cambió la base, los demás ejecutan `php artisan migrate:fresh --seed` después de hacer `pull`.
5. Hacer `pull` de `develop` seguido para evitar conflictos grandes.
