import clienteApi from '../../api/cliente'

// Portal del donante: solicitudes, citas y campañas (rutas del módulo 4).
// Las usa la pantalla Inicio; las pantallas Solicitudes, Mis citas y Campañas pueden reutilizarlas.

export interface HospitalResumido {
  id: number
  razon_social: string
  direccion: string
  whatsapp: string | null
}

export interface SolicitudParaDonante {
  id: number
  unidades_requeridas: number
  unidades_confirmadas: number
  prioridad: 'baja' | 'media' | 'alta' | 'critica'
  motivo: string | null
  fecha_limite: string
  tipo_sangre: { codigo: string }
  hospital: HospitalResumido
}

export interface CitaDonante {
  id: number
  fecha_hora: string
  estado: 'pendiente' | 'confirmada' | 'asistio' | 'no_asistio' | 'cancelada'
  solicitud: { id: number; hospital?: HospitalResumido } | null
  campana: { id: number; nombre: string; lugar: string; hospital: { razon_social: string } } | null
}

export interface CampanaParaDonante {
  id: number
  nombre: string
  lugar: string
  fecha: string
  cupos_disponibles: number
  inicio_utc: string
  fin_utc: string
  hospital: HospitalResumido
}

export async function obtenerSolicitudesDonante(): Promise<SolicitudParaDonante[]> {
  const respuesta = await clienteApi.get('/donante/solicitudes')
  return respuesta.data.solicitudes
}

export async function obtenerCitasDonante(): Promise<CitaDonante[]> {
  const respuesta = await clienteApi.get('/donante/citas')
  return respuesta.data.citas
}

export async function obtenerCampanasDonante(): Promise<CampanaParaDonante[]> {
  const respuesta = await clienteApi.get('/donante/campanas')
  return respuesta.data.campanas
}
