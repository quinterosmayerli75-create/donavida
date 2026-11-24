# Módulo 4 Citas donaciones campañas e indicadores

Este módulo completa el flujo de solicitud o campaña → cita → asistencia → donación. Las rutas requieren tokens de Sanctum. Cada hospital administra sus propios registros y debe estar activo y verificado. El administrador dispone de indicadores globales.

Se reutilizan las tablas y migraciones de `develop`; no hay cambios de esquema ni nuevas dependencias de Composer. El frontend queda pendiente.

## Rutas

Todas las rutas de la tabla llevan el prefijo `/api`. Enviar `Accept: application/json` y `Authorization: Bearer <token>`.

| Rol | Método y ruta | Resultado |
| --- | --- | --- |
| Donante | `GET /donante/solicitudes` | Solicitudes compatibles, abiertas y vigentes de hospitales activos y verificados |
| Donante | `GET /donante/campanas` | Campañas vigentes con cupos disponibles |
| Donante | `GET /donante/citas` | Citas propias, con origen y donación si existe |
| Donante | `POST /donante/citas` | Reservar un horario para una solicitud o campaña |
| Donante | `PATCH /donante/citas/{cita}/cancelar` | Cancelar una reserva futura propia |
| Hospital | `GET /hospital/citas` | Agenda propia, con datos del donante |
| Hospital | `PATCH /hospital/citas/{cita}/asistencia` | Registrar `asistio` o `no_asistio` |
| Hospital | `PATCH /hospital/citas/{cita}/cancelar` | Cancelar una reserva futura de su agenda |
| Hospital | `POST /hospital/citas/{cita}/donacion` | Registrar una donación válida |
| Hospital | `GET /hospital/donaciones` | Donaciones registradas en ese hospital |
| Hospital | `GET /hospital/campanas` | Campañas propias, incluidos estados finales |
| Hospital | `POST /hospital/campanas` | Crear una campaña programada |
| Hospital | `GET /hospital/campanas/{campana}` | Consultar una campaña propia |
| Hospital | `PUT/PATCH /hospital/campanas/{campana}` | Editar una campaña propia |
| Hospital | `DELETE /hospital/campanas/{campana}` | Cancelar sin borrar; también cancela reservas pendientes y confirmadas |
| Hospital | `GET /hospital/indicadores` | Indicadores de ese hospital |
| Administrador | `GET /admin/indicadores` | Indicadores globales |

Las dos agendas admiten `estado`, `desde=YYYY-MM-DD` y `hasta=YYYY-MM-DD`. El rango es inclusivo y se interpreta en UTC. Las respuestas usan las claves `citas`, `campanas`, `solicitudes`, `donaciones` o `indicadores`; una creación devuelve `cita`, `campana` o `donacion` con HTTP 201.

## Reservar y registrar una donación

Ejemplo del cuerpo de `POST /api/donante/citas`:

```json
{
  "solicitud_id": 1,
  "fecha_hora": "2026-12-01T10:00:00-04:00",
  "observaciones": "Llegaré a recepción"
}
```

Para una campaña, reemplazar `solicitud_id` por `campana_id`. Debe haber exactamente un origen. Usar un ID y un horario vigentes de la instalación local.

La reserva usa el donante del token, verifica que su cuenta esté activa, su disponibilidad, edad de 18 a 60 años y `puedeDonarHoy()`. La regla se evalúa hoy, incluso si el horario elegido es lejano. El donante solo puede mantener una cita pendiente o confirmada; debe cancelarla antes de reservar otra. La API crea la cita en estado `confirmada` y no acepta un estado ni un donante elegidos por el cliente.

En solicitudes, el tipo del donante debe estar incluido en `puede_recibir_de` y el horario debe ser futuro y no superar `fecha_limite`. En campañas debe estar dentro del intervalo `[inicio, fin)`, con cupos disponibles. Una reserva cancelada libera el cupo; las asistencias e inasistencias conservan el cupo utilizado.

Después de llegar el horario, el hospital envía a `PATCH /api/hospital/citas/{cita}/asistencia`:

```json
{ "estado": "asistio" }
```

También se acepta `no_asistio`. Repetir el mismo estado es idempotente; cambiar una asistencia ya registrada devuelve HTTP 409. Una cita cancelada no puede marcar asistencia.

Con asistencia registrada, enviar a `POST /api/hospital/citas/{cita}/donacion`:

```json
{ "volumen_ml": 450, "observaciones": "Registro de prueba" }
```

`volumen_ml` es opcional, entero entre 1 y 65535 según el esquema; su valor por defecto es 450. La fecha se obtiene del servidor en UTC. El cliente no puede elegir otra fecha, donante o estado.

La transacción crea la donación, actualiza `fecha_ultima_donacion` y suma **una unidad por donación** a la solicitud, independientemente del volumen. Al alcanzar las unidades requeridas cambia a `cubierta`. Se permite registrar donaciones de citas existentes cuando la solicitud ya está cubierta o venció su plazo, para conservar las donaciones efectivamente realizadas; una solicitud cancelada o cerrada no admite registros. El contador puede superar las unidades requeridas si asisten más donantes con citas previas.

La regla de los tres meses se verifica otra vez al registrar. Una cita solo admite una donación, y dos citas del mismo donante no permiten eludir el intervalo. Si falla cualquier actualización se revierte toda la operación. El historial e insignias del módulo de Maya leen automáticamente estos registros.

## Campañas y fechas

Ejemplo de `POST /api/hospital/campanas`:

```json
{
  "nombre": "Jornada solidaria",
  "descripcion": "Donación voluntaria",
  "lugar": "Hospital Demo Central",
  "fecha": "2026-12-15",
  "hora_inicio": "13:00",
  "hora_fin": "17:00",
  "cupos": 40
}
```

Las coordenadas son opcionales. La fecha y las horas de campaña se interpretan en **UTC**, dentro del mismo día; el ejemplo corresponde a 09:00–13:00 de Bolivia. Las horas aceptan `HH:mm` o `HH:mm:ss`. La respuesta incluye `inicio_utc` y `fin_utc` en ISO 8601 para que el frontend convierta a `America/La_Paz` sin ambigüedad. `fecha_hora` acepta ISO 8601 con `Z` o un desfase como `-04:00`; se almacena y devuelve en UTC. Los filtros de agenda usan días UTC.

Las campañas nuevas empiezan `programada`. Dentro de su horario se puede cambiar a `en_curso`; después del fin, a `finalizada`. Una campaña en curso no puede volver a programada. Las campañas finalizadas o canceladas quedan cerradas a edición. Solo se puede cancelar una campaña programada que aún no empezó.

No se puede reducir `cupos` por debajo de las reservas existentes ni cambiar fecha, horario, lugar o coordenadas mientras haya reservas no canceladas. Las respuestas incluyen `cupos_ocupados` y `cupos_disponibles`. La reserva bloquea el donante y el origen dentro de una transacción; el recuento de reservas usa una lectura con bloqueo para evitar instantáneas antiguas de MySQL cuando dos donantes compiten por el último cupo.

## Indicadores

Se devuelven donaciones válidas, volumen total en ml, donaciones por cada uno de los ocho tipos de sangre, solicitudes por estado, citas por estado y unidades confirmadas. Los grupos sin registros devuelven cero. Las donaciones anuladas no suman al total ni al volumen. El hospital solo ve sus solicitudes, campañas y citas; el administrador ve el conjunto completo.

## Correo y Mailtrap

Cada reserva encola `ConfirmacionCita` después de confirmar la transacción. El correo muestra hospital, lugar y horario de Bolivia. Para desarrollo, el valor por defecto `MAIL_MAILER=log` escribe el correo en el log al procesar la cola.

En otra terminal de `backend` ejecutar:

```bash
php artisan queue:work --tries=3
```

Para probar SMTP, copiar las credenciales del sandbox propio de Mailtrap al `.env` local, según su [guía de configuración SMTP](https://docs.mailtrap.io/email-sandbox/setup):

```dotenv
MAIL_MAILER=smtp
MAIL_SCHEME=null
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=usuario_del_sandbox
MAIL_PASSWORD=clave_del_sandbox
MAIL_FROM_ADDRESS=citas@donavida.test
MAIL_FROM_NAME=DonaVida
```

Después, ejecutar `php artisan config:clear` y reiniciar el worker. Una falla SMTP puede reintentarse sin deshacer una cita confirmada. Las credenciales y la entrega real a Mailtrap deben verificarse en cada entorno.

Para verificar el flujo completo contra el sandbox configurado, desde `backend`:

```bash
php tests/Support/verificar-mailtrap.php
```

El verificador usa una base SQLite temporal y crea una reserva con los datos de prueba. Comprueba que la confirmación se encole después del commit, procesa el job y exige que el transporte SMTP emita `MessageSent`, sin trabajos pendientes ni fallidos. Solo admite `sandbox.smtp.mailtrap.io`, no imprime credenciales y elimina su base temporal al terminar. No modifica la base configurada en `.env`. El resultado acredita la aceptación SMTP; el correo se puede consultar en el inbox del sandbox de Mailtrap.

## Postman

Importar [la colección del módulo 4](postman/DonaVida-Modulo4-Citas-Donaciones.postman_collection.json) y ejecutarla en orden con Collection Runner. `base_url` vale `http://127.0.0.1:8000/api`. La API debe estar iniciada y la base debe contener los seeders habituales. Ana y Sofía deben poder donar y no tener citas activas.

La colección inicia sesión con las cuentas de prueba, guarda tokens e IDs, crea una campaña de un cupo, comprueba que se agota, libera el cupo y cancela la campaña. Después publica una solicitud O+, reserva con Ana para dentro de cinco segundos, espera brevemente mediante un script de Postman, marca asistencia y registra una donación. Comprueba el rechazo de un duplicado, la solicitud cubierta, el historial, los indicadores y los permisos.

El flujo modifica los datos de prueba. Después de donar, Ana no podrá donar otra vez durante tres meses. Para repetirlo, usar otra cuenta habilitada o preparar de nuevo **una base exclusiva de pruebas**. La colección no ejecuta borrados ni reinicia la base. En clientes sin soporte de scripts se deben copiar manualmente tokens e IDs y esperar a que llegue el horario antes de marcar asistencia.

## Pruebas automáticas

Desde `backend`, con las dependencias instaladas:

```bash
php artisan test
php artisan test --filter="CitasDonacionesApiTest|CampanasIndicadoresApiTest"
```

La suite usa SQLite en memoria y una clave exclusiva de pruebas en `phpunit.xml`; no necesita una base MySQL ni credenciales SMTP. El módulo incorpora 34 pruebas de API: compatibilidad, intervalo exacto de tres meses, permisos, propiedad, estados, cupos, cancelaciones, UTC, normalización de horarios, correo encolado, indicadores, integración del historial y rollback ante un fallo de actualización. La suite completa suma 68 pruebas.

## Concurrencia en MySQL

La suite separada `phpunit.mysql.xml` ejecuta siete escenarios con dos procesos PHP independientes, conexiones MySQL distintas y aislamiento `REPEATABLE READ`. Una barrera sincroniza ambos procesos justo antes de intentar el bloqueo compartido por las operaciones. En el caso del último cupo, ambos procesos ya abrieron su instantánea antes de disputar la campaña.

Los escenarios cubren: dos donantes por un cupo; dos reservas del mismo donante; dos registros de una misma donación; donaciones desde dos citas del mismo donante; suma simultánea de unidades de distintos donantes; reducción de cupos contra una reserva; y cancelación de campaña contra una reserva. También se comprueba que una reserva rechazada no encole una confirmación extra.

La configuración fuerza la base **`donavida_concurrency_test`**. Cada escenario ejecuta `migrate:fresh` únicamente en esa base, por lo que debe ser exclusiva de estas pruebas. El nombre se comprueba antes de ejecutar migraciones. Por defecto se conecta a `127.0.0.1:3307`, usuario `root`; host, puerto, usuario y contraseña pueden suministrarse mediante variables de entorno. Los workers reciben la misma conexión sin imprimir sus credenciales.

Preparar la base vacía en el servidor de pruebas y ejecutar desde `backend`:

```sql
CREATE DATABASE donavida_concurrency_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

```bash
php vendor/phpunit/phpunit/phpunit --configuration phpunit.mysql.xml
```

La suite habitual sigue utilizando SQLite en memoria; las pruebas de concurrencia se ejecutan explícitamente con la configuración MySQL.

Verificación del 24 de noviembre de 2026: 68 pruebas funcionales pasando tanto en SQLite como en MySQL 8.4.13, y los siete escenarios de concurrencia pasando en MySQL con `REPEATABLE READ`. La colección Postman también completó sus 34 peticiones y 36 comprobaciones contra una API local con base de pruebas independiente. La entrega real a Mailtrap requiere ejecutar el verificador SMTP con las credenciales del sandbox de cada entorno.

Errores esperados: HTTP 401 sin token; 403 por rol o cuenta no habilitada; 404 para IDs ajenos o inexistentes; 409 para conflictos de estado, intervalo o cupos; 422 para datos inválidos o incompatibilidad sanguínea.
