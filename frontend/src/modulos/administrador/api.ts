import clienteApi from '../../api/cliente'

// Portal del administrador: tipos de datos y llamadas a la API.

export interface Hospital {
  id: number
  razon_social: string
  nit: string
  direccion: string
  zona: string | null
  telefono_contacto: string
  whatsapp: string | null
  latitud: string
  longitud: string
  verificado: boolean
  created_at: string
  usuario: { nombre: string; email: string; telefono: string | null; estado: string }
}

export interface Indicadores {
  donaciones_validas: number
  volumen_total_ml: number
  donaciones_por_tipo_sangre: { tipo_sangre: string; total: number }[]
  solicitudes_por_estado: Record<'abierta' | 'cubierta' | 'cerrada' | 'cancelada', number>
  citas_por_estado: Record<'pendiente' | 'confirmada' | 'asistio' | 'no_asistio' | 'cancelada', number>
  unidades_confirmadas: number
}

export async function obtenerIndicadores(): Promise<Indicadores> {
  const respuesta = await clienteApi.get('/admin/indicadores')
  return respuesta.data.indicadores
}

export async function obtenerHospitales(): Promise<Hospital[]> {
  const respuesta = await clienteApi.get('/admin/hospitales')
  return respuesta.data
}

export async function validarHospital(id: number): Promise<void> {
  await clienteApi.patch(`/admin/hospitales/${id}/validar`)
}

export async function desactivarHospital(id: number): Promise<void> {
  await clienteApi.patch(`/admin/hospitales/${id}/desactivar`)
}

export type RolUsuario = 'donante' | 'hospital' | 'administrador'

export interface UsuarioRed {
  id: number
  donante_id: number | null
  hospital_id: number | null
  foto_url: string | null
  nombre: string
  email: string
  telefono: string | null
  rol: RolUsuario
  tipo_sangre: string | null
  estado: 'activo' | 'inactivo' | 'pendiente'
  created_at: string
}

export interface ListaUsuarios {
  totales: Record<RolUsuario, number>
  usuarios: UsuarioRed[]
}

export async function obtenerUsuarios(): Promise<ListaUsuarios> {
  const respuesta = await clienteApi.get('/admin/usuarios')
  return respuesta.data
}

export async function cambiarEstadoUsuario(id: number, estado: 'activo' | 'inactivo'): Promise<void> {
  await clienteApi.patch(`/admin/usuarios/${id}/estado`, { estado })
}

export interface CampanaRed {
  id: number
  nombre: string
  lugar: string
  fecha: string
  hora_inicio: string
  hora_fin: string
  cupos: number
  cupos_ocupados: number
  cupos_disponibles: number
  estado: 'programada' | 'en_curso' | 'finalizada' | 'cancelada'
  inicio_utc: string
  fin_utc: string
  hospital: { id: number; razon_social: string }
}

export async function obtenerCampanas(): Promise<CampanaRed[]> {
  const respuesta = await clienteApi.get('/admin/campanas')
  return respuesta.data.campanas
}

// ---------- CRUD de donantes y hospitales ----------

export interface DonanteAdmin {
  id: number
  nombre: string
  apellidos: string
  email: string
  telefono: string | null
  estado: string
  ci: string
  fecha_nacimiento: string
  sexo: 'F' | 'M'
  tipo_sangre_id: number
  tipo_sangre: string
  zona: string | null
  fecha_ultima_donacion: string | null
  foto_url: string | null
  tiene_carnet: boolean
}

export interface DatosDonanteAdmin {
  nombre: string
  apellidos: string
  email: string
  telefono: string
  ci: string
  fecha_nacimiento: string
  sexo: 'F' | 'M' | ''
  tipo_sangre_id: number
  zona: string
  fecha_ultima_donacion: string
  password: string
  password_confirmation: string
}

export interface DatosHospitalAdmin {
  nombre: string
  email: string
  telefono: string
  razon_social: string
  nit: string
  direccion: string
  zona: string
  telefono_contacto: string
  whatsapp: string
  latitud: number | null
  longitud: number | null
  password: string
  password_confirmation: string
}

// Los campos vacíos se envían como null (así la contraseña vacía no se cambia al editar)
function sinVacios<T extends object>(datos: T): Record<string, unknown> {
  return Object.fromEntries(Object.entries(datos).map(([campo, valor]) => [campo, valor === '' ? null : valor]))
}

export async function obtenerDonante(id: number): Promise<DonanteAdmin> {
  const respuesta = await clienteApi.get(`/admin/donantes/${id}`)
  return respuesta.data.donante
}

export async function guardarDonante(id: number | null, datos: DatosDonanteAdmin): Promise<string> {
  const respuesta = id === null ? await clienteApi.post('/admin/donantes', sinVacios(datos)) : await clienteApi.put(`/admin/donantes/${id}`, sinVacios(datos))
  return respuesta.data.mensaje
}

export async function guardarHospital(id: number | null, datos: DatosHospitalAdmin): Promise<string> {
  const respuesta = id === null ? await clienteApi.post('/admin/hospitales', sinVacios(datos)) : await clienteApi.put(`/admin/hospitales/${id}`, sinVacios(datos))
  return respuesta.data.mensaje
}
