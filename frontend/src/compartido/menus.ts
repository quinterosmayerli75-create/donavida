import {
  IconBuildingHospital,
  IconCalendarEvent,
  IconChartBar,
  IconDroplet,
  IconHeart,
  IconHistory,
  IconHome,
  IconLayoutGrid,
  IconShieldCheck,
  IconSpeakerphone,
  IconUser,
  IconUsers,
  type Icon,
} from '@tabler/icons-react'
import type { NombreRol } from '../modulos/autenticacion/api'

// Menú lateral de cada rol. Cada opción tiene su ruta, título y las rutas
// de la API que la pantalla debería usar (se muestran en las páginas pendientes).

export interface OpcionMenu {
  ruta: string
  texto: string
  subtitulo: string
  icono: Icon
  api?: string[]
  // Numerito rojo junto a la opción (dato de ejemplo hasta conectar la API)
  insignia?: number
}

export interface MenuRol {
  nombrePortal: string
  icono: Icon
  color: string
  opciones: OpcionMenu[]
  pie: { icono: Icon; titulo: string; texto: string }
  // Botón rojo de la barra superior
  accion?: { texto: string; ruta: string }
}

export const menus: Record<NombreRol, MenuRol> = {
  administrador: {
    nombrePortal: 'Administrador',
    icono: IconShieldCheck,
    color: '#2563eb',
    opciones: [
      { ruta: '/admin', texto: 'Resumen', subtitulo: 'Toda la red de Cochabamba', icono: IconLayoutGrid, api: ['GET /admin/indicadores', 'GET /admin/hospitales?verificado=0'] },
      { ruta: '/admin/hospitales', texto: 'Hospitales', subtitulo: 'Registro y validación', icono: IconBuildingHospital, insignia: 1, api: ['GET /admin/hospitales', 'PATCH /admin/hospitales/{id}/validar', 'PATCH /admin/hospitales/{id}/desactivar'] },
      { ruta: '/admin/usuarios', texto: 'Usuarios', subtitulo: 'Cuentas y roles', icono: IconUsers, api: ['GET /admin/usuarios', 'PATCH /admin/usuarios/{id}/estado'] },
      { ruta: '/admin/campanas', texto: 'Campañas', subtitulo: 'Jornadas de toda la red', icono: IconSpeakerphone, api: ['GET /admin/campanas'] },
      { ruta: '/admin/reportes', texto: 'Reportes', subtitulo: 'Indicadores de la red', icono: IconChartBar, api: ['GET /admin/indicadores'] },
    ],
    pie: { icono: IconShieldCheck, titulo: 'Control de la red', texto: 'Valida hospitales y revisa que la red funcione bien.' },
    accion: { texto: 'Nuevo hospital', ruta: '/admin/hospitales?nuevo=1' },
  },
  hospital: {
    nombrePortal: 'Hospital',
    icono: IconBuildingHospital,
    color: '#e11d48',
    opciones: [
      { ruta: '/hospital', texto: 'Resumen', subtitulo: 'Lo más importante de hoy', icono: IconLayoutGrid, api: ['GET /hospital/indicadores', 'GET /hospital/solicitudes', 'GET /hospital/citas'] },
      { ruta: '/hospital/solicitudes', texto: 'Solicitudes', subtitulo: 'Pedidos de sangre del hospital', icono: IconDroplet, insignia: 3, api: ['GET /hospital/solicitudes', 'POST /hospital/solicitudes', 'PUT /hospital/solicitudes/{id}', 'DELETE /hospital/solicitudes/{id}'] },
      { ruta: '/hospital/donantes', texto: 'Donantes', subtitulo: 'Compatibles con tus solicitudes', icono: IconUsers, api: ['GET /hospital/solicitudes/{id}/donantes-compatibles'] },
      { ruta: '/hospital/citas', texto: 'Citas y donaciones', subtitulo: 'Seguimiento de cada donante', icono: IconCalendarEvent, api: ['GET /hospital/citas', 'PATCH /hospital/citas/{id}/asistencia', 'POST /hospital/citas/{id}/donacion', 'GET /hospital/donaciones'] },
      { ruta: '/hospital/campanas', texto: 'Campañas', subtitulo: 'Jornadas de donación', icono: IconSpeakerphone, api: ['GET /hospital/campanas', 'POST /hospital/campanas', 'PUT /hospital/campanas/{id}', 'DELETE /hospital/campanas/{id}'] },
      { ruta: '/hospital/reportes', texto: 'Reportes', subtitulo: 'Indicadores del hospital', icono: IconChartBar, api: ['GET /hospital/indicadores'] },
    ],
    pie: { icono: IconHeart, titulo: 'Juntos salvamos vidas', texto: 'Publica una solicitud y avisamos a los donantes compatibles.' },
    accion: { texto: 'Nueva solicitud', ruta: '/hospital/solicitudes' },
  },
  donante: {
    nombrePortal: 'Donante',
    icono: IconHeart,
    color: '#e11d48',
    opciones: [
      { ruta: '/donante', texto: 'Inicio', subtitulo: 'Gracias por ser donante', icono: IconHome, api: ['GET /donante/perfil', 'GET /donante/puedo-donar', 'GET /donante/solicitudes', 'GET /donante/citas'] },
      { ruta: '/donante/solicitudes', texto: 'Solicitudes', subtitulo: 'Compatibles con tu sangre', icono: IconDroplet, insignia: 2, api: ['GET /donante/solicitudes', 'POST /donante/citas'] },
      { ruta: '/donante/citas', texto: 'Mis citas', subtitulo: 'Tus turnos para donar', icono: IconCalendarEvent, api: ['GET /donante/citas', 'PATCH /donante/citas/{id}/cancelar'] },
      { ruta: '/donante/campanas', texto: 'Campañas', subtitulo: 'Jornadas para donar', icono: IconSpeakerphone, api: ['GET /donante/campanas', 'POST /donante/citas'] },
      { ruta: '/donante/historial', texto: 'Historial', subtitulo: 'Todas tus donaciones', icono: IconHistory },
      { ruta: '/donante/perfil', texto: 'Mi perfil', subtitulo: 'Tus datos de donante', icono: IconUser },
    ],
    pie: { icono: IconHeart, titulo: 'Gracias por donar', texto: 'Tus 3 donaciones ayudaron a 9 personas.' },
  },
}
