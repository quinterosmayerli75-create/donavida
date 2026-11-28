import { useState, type FormEvent } from 'react'
import { Link, Navigate, useLocation, useNavigate } from 'react-router-dom'
import { IconArrowRight, IconBuildingHospital, IconHeart, IconLock, IconMail, IconShieldCheck, type Icon } from '@tabler/icons-react'
import { Boton, Campo, CampoClave, MensajeError, MensajeExito } from '../../compartido/ui'
import { mensajeDeError } from '../../compartido/utilidades'
import { iniciarSesion, type NombreRol } from './api'
import { rutaInicial, useSesion } from './ContextoSesion'
import PanelBienvenida from './PanelBienvenida'

// Módulo 1 (Mayerli): pantalla de inicio de sesión.
// Las tarjetas de rol solo ayudan a orientar al usuario: el rol real lo decide la API.

const roles: { clave: NombreRol; icono: Icon; titulo: string; texto: string }[] = [
  { clave: 'donante', icono: IconHeart, titulo: 'Donante', texto: 'Quiero donar' },
  { clave: 'hospital', icono: IconBuildingHospital, titulo: 'Hospital', texto: 'Pido sangre' },
  { clave: 'administrador', icono: IconShieldCheck, titulo: 'Admin', texto: 'SEDES' },
]

function PaginaLogin() {
  const { usuario, guardarSesion } = useSesion()
  const navegar = useNavigate()
  const { state } = useLocation()
  const [rol, setRol] = useState<NombreRol>('donante')
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [error, setError] = useState<string | null>(null)
  const [cargando, setCargando] = useState(false)

  if (usuario !== null) return <Navigate to={rutaInicial(usuario.rol.nombre)} replace />

  async function ingresar(evento: FormEvent) {
    evento.preventDefault()
    setError(null)
    setCargando(true)
    try {
      const respuesta = await iniciarSesion(email, password)
      guardarSesion(respuesta.token, respuesta.usuario)
      navegar(rutaInicial(respuesta.usuario.rol.nombre), { replace: true })
    } catch (excepcion) {
      setError(mensajeDeError(excepcion))
    } finally {
      setCargando(false)
    }
  }

  const textoRol = { donante: 'donante', hospital: 'hospital', administrador: 'administrador' }[rol]

  return (
    <div className="flex min-h-screen">
      <PanelBienvenida
        titulo={
          <>
            Cada donación
            <br />
            conecta vidas.
          </>
        }
        texto="Hospitales y donantes de Cochabamba, conectados en un solo lugar."
      >
        <div className="flex gap-3">
          {[
            ['Compatibles', 'Solo te avisamos si tu sangre sirve'],
            ['Cerca', 'Hospitales de tu zona'],
            ['Seguro', 'Respetamos los 3 meses entre donaciones'],
          ].map(([titulo, texto]) => (
            <div key={titulo} className="flex-1 rounded-2xl bg-white/12 p-4">
              <b className="block text-base">{titulo}</b>
              <small className="text-xs text-white/80">{texto}</small>
            </div>
          ))}
        </div>
      </PanelBienvenida>

      <div className="flex flex-1 items-center justify-center bg-white p-8">
        <form onSubmit={ingresar} className="w-full max-w-[400px]">
          <h2 className="text-[26px] font-bold">Bienvenido</h2>
          <p className="mt-1 text-texto-suave">¿Cómo quieres entrar?</p>

          <div className="my-6 grid grid-cols-3 gap-2.5">
            {roles.map((opcion) => {
              const IconoRol = opcion.icono
              const activo = rol === opcion.clave
              return (
                <button
                  type="button"
                  key={opcion.clave}
                  onClick={() => setRol(opcion.clave)}
                  className={`rounded-2xl border-[1.5px] px-2 py-3.5 text-center transition ${
                    activo ? 'border-marca bg-marca-suave text-marca' : 'border-borde text-texto-suave hover:bg-fondo'
                  }`}
                >
                  <IconoRol size={24} className="mx-auto" />
                  <b className={`mt-1 block text-[13px] ${activo ? 'text-marca' : 'text-texto'}`}>{opcion.titulo}</b>
                  <small className="text-[11px]">{opcion.texto}</small>
                </button>
              )
            })}
          </div>

          {state?.mensaje && (
            <div className="mb-4">
              <MensajeExito>{state.mensaje}</MensajeExito>
            </div>
          )}

          <div className="space-y-3.5">
            <Campo etiqueta="Correo" type="email" icono={IconMail} placeholder="nombre@correo.com" value={email} onChange={(e) => setEmail(e.target.value)} required />
            <CampoClave etiqueta="Contraseña" icono={IconLock} placeholder="Tu contraseña" value={password} onChange={(e) => setPassword(e.target.value)} required />
          </div>

          <div className="mb-5 mt-2.5 flex justify-between text-[13px]">
            <label className="flex items-center gap-1.5 text-texto-suave">
              <input type="checkbox" className="accent-marca" /> Recordarme
            </label>
            <span className="font-semibold text-marca" title="Próximamente: recuperación por correo">
              Olvidé mi contraseña
            </span>
          </div>

          {error && (
            <div className="mb-4">
              <MensajeError>{error}</MensajeError>
            </div>
          )}

          <Boton type="submit" variante="principal" disabled={cargando} className="w-full !py-3.5 !text-[15px]">
            {cargando ? 'Ingresando...' : `Ingresar como ${textoRol}`}
            {!cargando && <IconArrowRight size={18} />}
          </Boton>

          <p className="mt-5 text-center text-[13px] text-texto-suave">
            {rol === 'donante' && (
              <>
                ¿Aún no eres donante?{' '}
                <Link to="/registro" className="font-semibold text-marca">
                  Crea tu cuenta
                </Link>
              </>
            )}
            {rol === 'hospital' && (
              <>
                ¿Tu hospital no está en la red?{' '}
                <Link to="/registro?perfil=hospital" className="font-semibold text-marca">
                  Solicita el registro
                </Link>
              </>
            )}
            {rol === 'administrador' && 'Acceso solo para personal de SEDES'}
          </p>
        </form>
      </div>
    </div>
  )
}

export default PaginaLogin
