import clienteApi from '../../api/cliente'
import type { Indicadores } from '../administrador/api'

// Portal del hospital: tipos de datos y llamadas que usa el Resumen.
// Los integrantes del rol hospital pueden agregar aquí las demás llamadas.

export type Prioridad = 'baja' | 'media' | 'alta' | 'critica'

export interface SolicitudHospital {
  id: number
  unidades_requeridas: number
  unidades_confirmadas: number
  prioridad: Prioridad
  motivo: string | null
  fecha_limite: string
  estado: 'abierta' | 'cubierta' | 'cerrada' | 'cancelada'
  created_at: string
  tipo_sangre: { id: number; codigo: string }
}

export interface CitaHospital {
  id: number
  fecha_hora: string
  estado: 'pendiente' | 'confirmada' | 'asistio' | 'no_asistio' | 'cancelada'
  donante: { apellidos: string; usuario: { nombre: string }; tipo_sangre: { codigo: string } }
  solicitud: { id: number } | null
  campana: { id: number; nombre: string } | null
}

export async function obtenerIndicadoresHospital(): Promise<Indicadores> {
  const respuesta = await clienteApi.get('/hospital/indicadores')
  return respuesta.data.indicadores
}

export async function obtenerSolicitudesHospital(): Promise<SolicitudHospital[]> {
  const respuesta = await clienteApi.get('/hospital/solicitudes')
  return respuesta.data.solicitudes
}

export async function obtenerCitasHospital(): Promise<CitaHospital[]> {
  const respuesta = await clienteApi.get('/hospital/citas')
  return respuesta.data.citas
}
