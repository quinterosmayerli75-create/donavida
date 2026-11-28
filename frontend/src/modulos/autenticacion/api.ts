import clienteApi from '../../api/cliente'

// Módulo 1: Autenticación (Mayerli)
// Tipos y llamadas a la API de login, registro, sesión y notificaciones.

export type NombreRol = 'administrador' | 'hospital' | 'donante'

export interface Usuario {
  id: number
  nombre: string
  email: string
  telefono: string
  estado: 'activo' | 'pendiente' | 'inactivo'
  rol: { id: number; nombre: NombreRol }
  donante?: {
    id: number
    apellidos: string
    tipo_sangre: { id: number; codigo: string }
    foto_url?: string | null
  } | null
  hospital?: {
    id: number
    razon_social: string
    verificado: boolean
  } | null
}

export interface RespuestaSesion {
  mensaje: string
  token: string
  usuario: Usuario
}

export interface DatosRegistroDonante {
  nombre: string
  apellidos: string
  email: string
  telefono: string
  password: string
  password_confirmation: string
  ci: string
  fecha_nacimiento: string
  sexo: 'F' | 'M'
  tipo_sangre_id: number
  zona?: string
  fecha_ultima_donacion?: string
  foto?: File | null
  carnet_donante?: File | null
}

export interface DatosRegistroHospital {
  nombre: string
  email: string
  telefono: string
  password: string
  password_confirmation: string
  razon_social: string
  nit: string
  direccion: string
  zona?: string
  latitud: number
  longitud: number
  telefono_contacto: string
  whatsapp?: string
}

export interface Notificacion {
  id: number
  tipo: string
  titulo: string
  mensaje: string
  leida: boolean
  created_at: string
}

export interface TipoSangre {
  id: number
  codigo: string
}

export async function iniciarSesion(email: string, password: string): Promise<RespuestaSesion> {
  const respuesta = await clienteApi.post<RespuestaSesion>('/login', { email, password })
  return respuesta.data
}

export async function cerrarSesion(): Promise<void> {
  await clienteApi.post('/logout')
}

export async function obtenerUsuarioActual(): Promise<Usuario> {
  const respuesta = await clienteApi.get<{ usuario: Usuario }>('/yo')
  return respuesta.data.usuario
}

// Se envía como formulario (FormData) porque puede llevar la foto y el carnet de donante.
export async function registrarDonante(datos: DatosRegistroDonante): Promise<RespuestaSesion> {
  const formulario = new FormData()
  Object.entries(datos).forEach(([campo, valor]) => {
    if (valor !== undefined && valor !== null && valor !== '') formulario.append(campo, valor instanceof File ? valor : String(valor))
  })
  const respuesta = await clienteApi.post<RespuestaSesion>('/registro/donante', formulario)
  return respuesta.data
}

// El hospital no recibe token: queda pendiente hasta que el administrador lo valide.
export async function registrarHospital(datos: DatosRegistroHospital): Promise<string> {
  const respuesta = await clienteApi.post<{ mensaje: string }>('/registro/hospital', datos)
  return respuesta.data.mensaje
}

export async function obtenerTiposSangre(): Promise<TipoSangre[]> {
  const respuesta = await clienteApi.get<TipoSangre[]>('/tipos-sangre')
  return respuesta.data
}

export async function listarNotificaciones(): Promise<Notificacion[]> {
  const respuesta = await clienteApi.get<Notificacion[]>('/notificaciones')
  return respuesta.data
}

export async function contarNoLeidas(): Promise<number> {
  const respuesta = await clienteApi.get<{ no_leidas: number }>('/notificaciones/no-leidas')
  return respuesta.data.no_leidas
}

export async function marcarLeida(id: number): Promise<void> {
  await clienteApi.patch(`/notificaciones/${id}/leer`)
}

export async function marcarTodasLeidas(): Promise<void> {
  await clienteApi.patch('/notificaciones/leer-todas')
}
