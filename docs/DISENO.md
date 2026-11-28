# Guía de diseño – DonaVida

Esta guía es para que **todas las pantallas se vean iguales**. Mayerli hizo el diseño de **todas** las pantallas (login, registro, menú y las de los 3 roles). Cada integrante conecta la lógica (API) de su rol sin cambiar el diseño y después Mayerli revisa y une todo.

## 1. Ver el diseño aprobado

Abre [`docs/diseno/maquetas.html`](diseno/maquetas.html) en el navegador (doble clic). Arriba eliges **Login · Administrador · Hospital · Donante** y en cada rol haces clic en el menú de la izquierda. Copia de ahí la distribución de cada pantalla.

## 2. Reglas del diseño

- **Poco texto.** Títulos cortos y como máximo una línea de descripción. Sin párrafos.
- **Rojo (`marca`) para lo importante.** Hay **un solo botón rojo** por pantalla (la acción principal).
- **Verde suave solo para estados positivos**: "Apto", "Puedes donar", "Validado", "Completada".
- **Estados con etiquetas de color:** rojo = urgente, verde = apto/completado, ámbar = pendiente, azul = en camino, gris = otro.
- **Fechas en hora de Bolivia.** La API devuelve UTC: usa `fechaCorta()`, `hora()` o `fechaHora()` de `src/compartido/utilidades.ts`.
- Nada de datos técnicos en pantalla (WebSockets, latencia, Haversine, IDs internos).

## 3. Colores (Tailwind)

Están definidos en `src/index.css` y se usan como cualquier clase de Tailwind: `bg-marca`, `text-marca`, `bg-marca-suave`, `bg-exito-suave`, `text-exito`, `bg-aviso-suave`, `text-aviso`, `bg-info-suave`, `text-info`, `bg-fondo`, `border-borde`, `text-texto-suave`.

## 4. Piezas listas para usar (`src/compartido/ui.tsx`)

| Pieza | Para qué |
|---|---|
| `<Tarjeta titulo subtitulo icono extra>` | Caja blanca con título (todas las secciones van dentro de una) |
| `<Cifra titulo valor detalle icono color>` | Tarjeta de número grande (fila de 3 o 4 arriba de cada pantalla) |
| `<Etiqueta tono="exito \| urgente \| aviso \| info \| neutro">` | Estado de una fila |
| `<Boton variante="principal \| secundario \| exito \| borde" pequeno>` | Botones |
| `<GrupoSangre codigo="O-" />` | Cuadro del grupo sanguíneo (rojo fuerte si es Rh−) |
| `<Tabla columnas={[...]}>` + `<tr><td>` | Tablas con el encabezado gris |
| `<Filtros opciones valor alCambiar>` | Píldoras para filtrar (Todas, Urgentes…) |
| `<Campo>` y `<Selector>` | Campos de formulario con etiqueta y error |
| `<Ventana titulo alCerrar>` | Ventana emergente para formularios de crear/editar |
| `<Cargando>`, `<Vacio>`, `<MensajeError>`, `<MensajeExito>` | Estados de carga, lista vacía y mensajes |
| `obligatorio` en `<Campo>` y `<Selector>` | Pone el asterisco rojo `*` y hace el campo requerido |
| `<CampoClave>` + `<RequisitosClave clave>` | Contraseña con ojito para mostrarla y la lista de requisitos en verde |
| `<SubirArchivo>` | Subir una foto o un PDF con vista previa |
| `<BarraFiltros>` + `<SelectorFiltro>` (`compartido/maqueta.tsx`) | Buscador y selectores para filtrar listas |

**Validaciones** (`src/compartido/validaciones.ts`, iguales a las del backend en `app/Support/Reglas.php`):

- Nombres y apellidos: solo letras → `soloLetras(texto)` al escribir.
- Teléfono, carnet, NIT y WhatsApp: solo números → `soloNumeros(texto)` al escribir.
- Contraseña: mínimo 8, con mayúscula, minúscula, número y símbolo → `claveSegura(clave)`.
- Todos los campos obligatorios llevan `obligatorio` (asterisco rojo).

Íconos: [Tabler](https://tabler.io/icons), por ejemplo `import { IconDroplet } from '@tabler/icons-react'`.

Ejemplo de una pantalla:

```tsx
import { IconDroplet } from '@tabler/icons-react'
import { Cifra, Etiqueta, Tarjeta } from '../../compartido/ui'

function PaginaSolicitudes() {
  return (
    <div className="space-y-5">
      <div className="grid grid-cols-4 gap-4">
        <Cifra titulo="Activas" valor={4} detalle="En curso" icono={IconDroplet} />
        {/* ... */}
      </div>
      <Tarjeta titulo="Solicitudes en curso" icono={IconDroplet}>
        {/* lista o tabla */}
        <Etiqueta tono="urgente">Urgente</Etiqueta>
      </Tarjeta>
    </div>
  )
}
```

## 5. Sesión y menú (ya hechos, no hace falta tocarlos)

- `useSesion()` da el usuario conectado: `const { usuario } = useSesion()`. Para el hospital está en `usuario.hospital` y para el donante en `usuario.donante`.
- El token se envía solo en cada petición con `clienteApi` (`src/api/cliente.ts`).
- El título de la barra superior y el menú salen de `src/compartido/menus.ts`.
- Las rutas están protegidas por rol en `src/App.tsx`.

## 6. Cómo conectar la lógica de tu rol

**Todas las pantallas ya tienen el diseño final**, igual a la maqueta. Las de Hospital y Donante muestran **datos de ejemplo**: tu trabajo es cambiarlos por los datos de la API **sin cambiar cómo se ve**.

1. Abre la página de tu pantalla (tabla de abajo). Arriba tiene un comentario `DATOS DE EJEMPLO` con las rutas de la API que debe usar.
2. Usa las funciones que ya existen (`src/modulos/hospitales/api.ts`, `src/modulos/donantes/api.ts`, `src/modulos/donantes/apiPortal.ts`) o agrega las que falten ahí mismo.
3. Cambia la constante `ejemplo` (o lo que viene de `src/compartido/datosEjemplo.ts`) por lo que devuelve la API con `useQuery`. Muestra `<Cargando />` y `<MensajeError>` mientras tanto.
4. Las piezas grandes de la maqueta (tabla de citas, tarjetas de campañas, historial, carnet, radar, selector de grupos) están en `src/compartido/maqueta.tsx` y reciben los datos por props.
5. Fechas en hora de Bolivia con `fechaCorta()`, `hora()`, `fechaHora()` y `diaBolivia()`.

**Reparto del equipo:** Mayerli revisa y aprueba todos los PRs (y el administrador ya está terminado). Maya conecta el rol Donante. Mati y Mollo se dividen el rol Hospital según las rutas del backend que hizo cada uno. Mollo también ayuda a Maya con citas y campañas, porque esas rutas son suyas.

| Rol | Pantalla | Archivo | Datos | Responsable |
|---|---|---|---|---|
| Administrador | Resumen, Hospitales, Usuarios, Campañas, Reportes | `modulos/administrador/` | **Reales** (Mayerli) | **Mayerli** (terminado) |
| Hospital | Resumen | `modulos/hospitales/PaginaResumenHospital.tsx` | Ejemplo | Mati |
| Hospital | Solicitudes | `modulos/hospitales/PaginaSolicitudes.tsx` | Ejemplo | Mati |
| Hospital | Donantes | `modulos/hospitales/PaginaDonantes.tsx` | Ejemplo | Mati |
| Hospital | Citas y donaciones | `modulos/hospitales/PaginaCitas.tsx` | Ejemplo | Mollo |
| Hospital | Campañas | `modulos/hospitales/PaginaCampanasHospital.tsx` | Ejemplo | Mollo |
| Hospital | Reportes | `modulos/hospitales/PaginaReportesHospital.tsx` | Ejemplo | Mollo |
| Donante | Inicio | `modulos/donantes/PaginaInicio.tsx` | Ejemplo | Maya |
| Donante | Solicitudes | `modulos/donantes/PaginaSolicitudesDonante.tsx` | Ejemplo | Maya |
| Donante | Mis citas | `modulos/donantes/PaginaMisCitas.tsx` | Ejemplo | Maya |
| Donante | Campañas | `modulos/donantes/PaginaCampanasDonante.tsx` | Ejemplo | Maya |
| Donante | Historial | `modulos/donantes/PaginaHistorial.tsx` | Ejemplo (Maya: su lógica está en `componentes/HistorialDonaciones.tsx`) | Maya |
| Donante | Mi perfil | `modulos/donantes/PaginaPerfil.tsx` | Ejemplo (Maya: su lógica está en `componentes/FormularioPerfil.tsx`, `MapaUbicacion.tsx`, `TarjetaPuedoDonar.tsx`) | Maya |

Los numeritos rojos del menú y el botón rojo de arriba están en `src/compartido/menus.ts` (`insignia` y `accion`); también son de ejemplo.

## 7. Cuentas para probar

Todas usan la contraseña `password123`: `admin@donavida.test`, `central@donavida.test` (hospital), `sofia@donavida.test` (donante).
