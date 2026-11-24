import { isAxiosError } from 'axios'
import clienteApi from '../../api/cliente'

// Módulo 2: Donantes (Maya)
// Tipos de datos que devuelve la API y funciones para llamarla.

export interface PerfilDonante {
  id: number
  nombre: string
  apellidos: string
  email: string
  telefono: string
  ci: string
  fecha_nacimiento: string
  edad: number
  sexo: 'F' | 'M'
  peso_kg: number | null
  tipo_sangre: { id: number; codigo: string }
  direccion: string | null
  zona: string | null
  latitud: number | null
  longitud: number | null
  disponible: boolean
  fecha_ultima_donacion: string | null
  puede_donar_hoy: boolean
}

// Campos que se pueden enviar al editar (todos opcionales)
export interface CambiosPerfil {
  nombre?: string
  apellidos?: string
  telefono?: string
  tipo_sangre_id?: number
  fecha_nacimiento?: string
  sexo?: 'F' | 'M'
  peso_kg?: number | null
  direccion?: string | null
  zona?: string | null
  latitud?: number
  longitud?: number
  disponible?: boolean
}

export interface EstadoPuedoDonar {
  puede_donar: boolean
  fecha_ultima_donacion: string | null
  proxima_fecha_para_donar: string | null
  dias_restantes: number
  meses_entre_donaciones: number
  mensaje: string
}

export interface Insignia {
  nivel: number
  titulo: string
  nombre: string | null
  donaciones_validas: number
  siguiente_nivel_en: number | null
}

export interface Donacion {
  id: number
  fecha_donacion: string
  volumen_ml: number
  estado: 'valida' | 'anulada'
  observaciones: string | null
  origen: string | null
  hospital: string | null
}

export interface Historial {
  insignia: Insignia
  total_donaciones: number
  total_ml: number
  donaciones: Donacion[]
}

export interface TipoSangre {
  id: number
  codigo: string
}

export async function obtenerPerfil(): Promise<PerfilDonante> {
  const respuesta = await clienteApi.get<{ perfil: PerfilDonante }>('/donante/perfil')
  return respuesta.data.perfil
}

export async function actualizarPerfil(cambios: CambiosPerfil): Promise<PerfilDonante> {
  const respuesta = await clienteApi.put<{ perfil: PerfilDonante }>('/donante/perfil', cambios)
  return respuesta.data.perfil
}

export async function consultarPuedoDonar(): Promise<EstadoPuedoDonar> {
  const respuesta = await clienteApi.get<EstadoPuedoDonar>('/donante/puedo-donar')
  return respuesta.data
}

export async function obtenerHistorial(): Promise<Historial> {
  const respuesta = await clienteApi.get<Historial>('/donante/historial')
  return respuesta.data
}

export async function obtenerTiposSangre(): Promise<TipoSangre[]> {
  const respuesta = await clienteApi.get<TipoSangre[]>('/tipos-sangre')
  return respuesta.data
}

// Errores de validación de Laravel (422): { campo: ["mensaje"] }
export type ErroresCampos = Record<string, string[]>

export function erroresDeValidacion(error: unknown): ErroresCampos {
  if (isAxiosError(error) && error.response?.status === 422) {
    return error.response.data.errors ?? {}
  }
  return {}
}

export function mensajeDeError(error: unknown): string {
  if (isAxiosError(error)) {
    return error.response?.data?.mensaje ?? error.response?.data?.message ?? 'No se pudo conectar con el servidor.'
  }
  return 'Ocurrió un error inesperado.'
}

// "2026-07-20" o "2026-07-20 09:00:00" → "20/07/2026"
export function formatearFecha(fecha: string): string {
  const [anio, mes, dia] = fecha.slice(0, 10).split('-')
  return `${dia}/${mes}/${anio}`
}
