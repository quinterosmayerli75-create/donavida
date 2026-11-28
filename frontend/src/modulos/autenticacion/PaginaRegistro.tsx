import { useState, type FormEvent } from 'react'
import { Link, Navigate, useNavigate, useSearchParams } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import {
  IconArrowLeft,
  IconArrowRight,
  IconBell,
  IconBuildingHospital,
  IconCamera,
  IconFileText,
  IconHeart,
  IconHistory,
  IconId,
  IconLock,
  IconMail,
  IconMapPin,
  IconPhone,
  IconShieldCheck,
  IconUser,
} from '@tabler/icons-react'
import { MapaElegirPunto, ZONAS } from '../../compartido/MapaElegirPunto'
import { Boton, Campo, CampoClave, MensajeError, RequisitosClave, Selector, SubirArchivo } from '../../compartido/ui'
import { erroresDeValidacion, mensajeDeError, type ErroresCampos } from '../../compartido/utilidades'
import { soloLetras, soloNumeros, validarCuenta } from '../../compartido/validaciones'
import * as api from './api'
import { rutaInicial, useSesion } from './ContextoSesion'
import PanelBienvenida from './PanelBienvenida'

// Módulo 1 (Mayerli): registro en dos pasos.
//  - Donante: paso 1 cuenta, paso 2 datos de salud. Entra directo a su portal.
//  - Hospital: paso 1 cuenta del responsable, paso 2 datos del hospital. Queda pendiente de validación.

type Perfil = 'donante' | 'hospital'

const vacio = {
  nombre: '',
  apellidos: '',
  email: '',
  telefono: '',
  password: '',
  password_confirmation: '',
  ci: '',
  fecha_nacimiento: '',
  sexo: '' as '' | 'F' | 'M',
  tipo_sangre_id: 0,
  zona: ZONAS[0],
  razon_social: '',
  nit: '',
  direccion: '',
  telefono_contacto: '',
  whatsapp: '',
  fecha_ultima_donacion: '',
}

function PaginaRegistro() {
  const { usuario, guardarSesion } = useSesion()
  const navegar = useNavigate()
  const [parametros] = useSearchParams()
  const [perfil, setPerfil] = useState<Perfil>(parametros.get('perfil') === 'hospital' ? 'hospital' : 'donante')
  const [paso, setPaso] = useState<1 | 2>(1)
  const [datos, setDatos] = useState(vacio)
  const [ubicacion, setUbicacion] = useState<[number, number] | null>(null)
  const [aceptaTerminos, setAceptaTerminos] = useState(false)
  const [foto, setFoto] = useState<File | null>(null)
  const [yaDono, setYaDono] = useState(false)
  const [carnet, setCarnet] = useState<File | null>(null)
  const [errores, setErrores] = useState<ErroresCampos>({})
  const [error, setError] = useState<string | null>(null)
  const [cargando, setCargando] = useState(false)

  const tipos = useQuery({ queryKey: ['tipos-sangre'], queryFn: api.obtenerTiposSangre })

  if (usuario !== null) return <Navigate to={rutaInicial(usuario.rol.nombre)} replace />

  function cambiar(campo: keyof typeof vacio, valor: string | number) {
    // Nombres: solo letras. Teléfonos, carnet y NIT: solo números.
    let limpio = valor
    if (typeof valor === 'string') {
      if (campo === 'nombre' || campo === 'apellidos') limpio = soloLetras(valor)
      if (campo === 'telefono' || campo === 'telefono_contacto' || campo === 'whatsapp') limpio = soloNumeros(valor, 15)
      if (campo === 'ci') limpio = soloNumeros(valor, 12)
      if (campo === 'nit') limpio = soloNumeros(valor, 20)
    }
    setDatos({ ...datos, [campo]: limpio })
    setErrores({ ...errores, [campo]: [] })
  }

  const err = (campo: string) => errores[campo]?.[0]

  // Paso 1: validaciones antes de pasar al paso 2 (las mismas reglas que el backend)
  function continuar(evento: FormEvent) {
    evento.preventDefault()
    const nuevos: ErroresCampos = validarCuenta({
      nombre: datos.nombre,
      apellidos: perfil === 'donante' ? datos.apellidos : undefined,
      telefono: datos.telefono,
      password: datos.password,
      password_confirmation: datos.password_confirmation,
    })
    if (!aceptaTerminos) nuevos.terminos = ['Debes aceptar los términos para continuar.']
    setErrores(nuevos)
    if (Object.keys(nuevos).length === 0) setPaso(2)
  }

  async function registrar(evento: FormEvent) {
    evento.preventDefault()
    setError(null)
    setCargando(true)
    try {
      if (perfil === 'donante') {
        if (!datos.tipo_sangre_id) {
          setErrores({ tipo_sangre_id: ['Elige tu grupo sanguíneo.'] })
          return
        }
        if (yaDono && !datos.fecha_ultima_donacion) {
          setErrores({ fecha_ultima_donacion: ['Indica cuándo fue tu última donación.'] })
          return
        }
        const respuesta = await api.registrarDonante({
          nombre: datos.nombre,
          apellidos: datos.apellidos,
          email: datos.email,
          telefono: datos.telefono,
          password: datos.password,
          password_confirmation: datos.password_confirmation,
          ci: datos.ci,
          fecha_nacimiento: datos.fecha_nacimiento,
          sexo: datos.sexo as 'F' | 'M',
          tipo_sangre_id: datos.tipo_sangre_id,
          zona: datos.zona,
          foto,
          fecha_ultima_donacion: yaDono ? datos.fecha_ultima_donacion : undefined,
          carnet_donante: yaDono ? carnet : null,
        })
        guardarSesion(respuesta.token, respuesta.usuario)
        navegar('/donante', { replace: true })
      } else {
        if (ubicacion === null) {
          setErrores({ latitud: ['Marca la ubicación del hospital en el mapa.'] })
          return
        }
        const mensaje = await api.registrarHospital({
          nombre: datos.nombre,
          email: datos.email,
          telefono: datos.telefono,
          password: datos.password,
          password_confirmation: datos.password_confirmation,
          razon_social: datos.razon_social,
          nit: datos.nit,
          direccion: datos.direccion,
          zona: datos.zona,
          latitud: ubicacion[0],
          longitud: ubicacion[1],
          telefono_contacto: datos.telefono_contacto,
          whatsapp: datos.whatsapp || undefined,
        })
        navegar('/login', { replace: true, state: { mensaje } })
      }
    } catch (excepcion) {
      const deValidacion = erroresDeValidacion(excepcion)
      setErrores(deValidacion)
      // Si el error es de un campo del paso 1, se vuelve a ese paso para corregirlo
      const camposPaso1 = ['nombre', 'apellidos', 'email', 'telefono', 'password', 'password_confirmation']
      if (Object.keys(deValidacion).some((campo) => camposPaso1.includes(campo))) setPaso(1)
      setError(mensajeDeError(excepcion))
    } finally {
      setCargando(false)
    }
  }

  return (
    <div className="flex min-h-screen">
      <PanelBienvenida
        titulo={
          <>
            Únete a la red que
            <br />
            salva vidas.
          </>
        }
        texto="Tu donación voluntaria marca la diferencia en hospitales y familias del valle cochabambino."
      >
        <div className="space-y-3">
          {[
            [IconId, 'Carnet digital', 'Tu grupo sanguíneo y si puedes donar, siempre a mano.'],
            [IconBell, 'Avisos al instante', 'Te avisamos cuando un hospital cercano necesita tu sangre.'],
            [IconHistory, 'Historial seguro', 'Cada donación queda registrada por el hospital.'],
          ].map(([Icono, titulo, texto]) => {
            const IconoBeneficio = Icono as typeof IconId
            return (
              <div key={titulo as string} className="flex max-w-md items-start gap-3 rounded-2xl bg-white/12 p-3.5">
                <span className="grid h-9 w-9 flex-none place-items-center rounded-xl bg-white/15">
                  <IconoBeneficio size={19} />
                </span>
                <div>
                  <b className="block text-[13px]">{titulo as string}</b>
                  <small className="text-xs text-white/80">{texto as string}</small>
                </div>
              </div>
            )
          })}
        </div>
      </PanelBienvenida>

      <div className="flex flex-1 items-center justify-center bg-white p-8">
        <form onSubmit={paso === 1 ? continuar : registrar} className="w-full max-w-[460px]">
          <div className="flex items-start justify-between">
            <div>
              <h2 className="text-[26px] font-bold">Crea tu cuenta</h2>
              <p className="mt-1 text-texto-suave">
                {perfil === 'donante' ? 'Forma parte de la comunidad donante.' : 'Registra tu hospital en la red.'}
              </p>
            </div>
            <span className="rounded-full bg-marca-suave px-2.5 py-1 text-[11px] font-semibold text-marca">Paso {paso} de 2</span>
          </div>

          {paso === 1 && (
            <>
              <div className="my-5 grid grid-cols-2 gap-1 rounded-xl bg-fondo p-1">
                {(
                  [
                    ['donante', IconHeart, 'Donante'],
                    ['hospital', IconBuildingHospital, 'Hospital'],
                  ] as const
                ).map(([clave, Icono, texto]) => (
                  <button
                    type="button"
                    key={clave}
                    onClick={() => setPerfil(clave)}
                    className={`flex items-center justify-center gap-1.5 rounded-lg py-2 text-[13px] font-semibold transition ${
                      perfil === clave ? 'bg-white text-marca shadow-sm' : 'text-texto-suave'
                    }`}
                  >
                    <Icono size={16} />
                    {texto}
                  </button>
                ))}
              </div>

              <div className="grid grid-cols-2 gap-3.5">
                <Campo etiqueta={perfil === 'donante' ? 'Nombre' : 'Nombre del responsable'} icono={IconUser} placeholder="Valeria" value={datos.nombre} onChange={(e) => cambiar('nombre', e.target.value)} error={err('nombre')} obligatorio />
                {perfil === 'donante' ? (
                  <Campo etiqueta="Apellidos" placeholder="Quiroga Rojas" value={datos.apellidos} onChange={(e) => cambiar('apellidos', e.target.value)} error={err('apellidos')} obligatorio />
                ) : (
                  <Campo etiqueta="Teléfono / WhatsApp" icono={IconPhone} inputMode="numeric" placeholder="70712345" value={datos.telefono} onChange={(e) => cambiar('telefono', e.target.value)} error={err('telefono')} obligatorio />
                )}
                <Campo etiqueta="Correo electrónico" type="email" icono={IconMail} placeholder="nombre@correo.com" value={datos.email} onChange={(e) => cambiar('email', e.target.value)} error={err('email')} obligatorio />
                {perfil === 'donante' ? (
                  <Campo etiqueta="Teléfono / WhatsApp" icono={IconPhone} inputMode="numeric" placeholder="70712345" value={datos.telefono} onChange={(e) => cambiar('telefono', e.target.value)} error={err('telefono')} obligatorio />
                ) : (
                  <div />
                )}
                <CampoClave etiqueta="Contraseña" icono={IconLock} placeholder="Mínimo 8 caracteres" value={datos.password} onChange={(e) => cambiar('password', e.target.value)} error={err('password')} obligatorio autoComplete="new-password" />
                <CampoClave etiqueta="Confirmar contraseña" icono={IconLock} placeholder="Repite tu contraseña" value={datos.password_confirmation} onChange={(e) => cambiar('password_confirmation', e.target.value)} error={err('password_confirmation')} obligatorio autoComplete="new-password" />
              </div>
              <RequisitosClave clave={datos.password} />

              <label className="mt-4 flex items-start gap-2 text-xs text-texto-suave">
                <input type="checkbox" className="mt-0.5 accent-marca" checked={aceptaTerminos} onChange={(e) => setAceptaTerminos(e.target.checked)} />
                <span>
                  Acepto los <b className="text-marca">Términos de servicio</b> y la <b className="text-marca">Política de privacidad</b> de donación
                  voluntaria en Bolivia.
                </span>
              </label>
              {err('terminos') && <p className="mt-1 text-xs text-marca">{err('terminos')}</p>}
            </>
          )}

          {paso === 2 && perfil === 'donante' && (
            <div className="mt-5 space-y-4">
              <div className="grid grid-cols-2 gap-3.5">
                <Campo etiqueta="Cédula de identidad" icono={IconId} inputMode="numeric" placeholder="8765432" value={datos.ci} onChange={(e) => cambiar('ci', e.target.value)} error={err('ci')} obligatorio />
                <Campo etiqueta="Fecha de nacimiento" type="date" value={datos.fecha_nacimiento} onChange={(e) => cambiar('fecha_nacimiento', e.target.value)} error={err('fecha_nacimiento')} obligatorio />
                <Selector etiqueta="Sexo" value={datos.sexo} onChange={(e) => cambiar('sexo', e.target.value)} error={err('sexo')} obligatorio>
                  <option value="">Elige una opción</option>
                  <option value="F">Femenino</option>
                  <option value="M">Masculino</option>
                </Selector>
                <Selector etiqueta="Zona en Cochabamba" value={datos.zona} onChange={(e) => cambiar('zona', e.target.value)}>
                  {ZONAS.map((zona) => (
                    <option key={zona}>{zona}</option>
                  ))}
                </Selector>
              </div>

              <div>
                <span className="mb-1.5 block text-xs font-semibold">
                  Grupo y factor sanguíneo<span className="ml-0.5 text-marca">*</span>
                </span>
                <div className="grid grid-cols-4 gap-2 sm:grid-cols-8">
                  {tipos.data?.map((tipo) => (
                    <button
                      type="button"
                      key={tipo.id}
                      onClick={() => cambiar('tipo_sangre_id', tipo.id)}
                      className={`h-11 rounded-xl border-[1.5px] text-[15px] font-bold transition ${
                        datos.tipo_sangre_id === tipo.id ? 'border-marca bg-marca text-white' : 'border-borde bg-[#f7f7fb] hover:border-marca'
                      }`}
                    >
                      {tipo.codigo.replace('-', '−')}
                    </button>
                  ))}
                </div>
                {err('tipo_sangre_id') && <p className="mt-1 text-xs text-marca">{err('tipo_sangre_id')}</p>}
                <p className="mt-1.5 text-[11px] text-texto-suave">¿No lo sabes? Puedes averiguarlo gratis en el Banco de Sangre antes de registrarte.</p>
              </div>

              <SubirArchivo
                etiqueta="Tu foto (opcional)"
                ayuda="JPG o PNG, hasta 2 MB"
                archivo={foto}
                alElegir={(archivo) => {
                  setFoto(archivo)
                  setErrores({ ...errores, foto: [] })
                }}
                aceptar="image/jpeg,image/png,image/webp"
                icono={IconCamera}
                error={err('foto')}
              />

              <label className="flex items-center gap-2 text-[13px] font-semibold">
                <input type="checkbox" className="accent-marca" checked={yaDono} onChange={(e) => setYaDono(e.target.checked)} />
                Ya doné sangre antes
              </label>
              {yaDono && (
                <div className="grid grid-cols-1 gap-3.5 rounded-xl bg-fondo p-3.5 sm:grid-cols-2">
                  <Campo
                    etiqueta="Fecha de tu última donación"
                    type="date"
                    max={new Date().toISOString().slice(0, 10)}
                    value={datos.fecha_ultima_donacion}
                    onChange={(e) => cambiar('fecha_ultima_donacion', e.target.value)}
                    error={err('fecha_ultima_donacion')}
                    obligatorio
                  />
                  <SubirArchivo
                    etiqueta="Carnet de donante (opcional)"
                    ayuda="Foto o PDF, hasta 4 MB"
                    archivo={carnet}
                    alElegir={(archivo) => {
                      setCarnet(archivo)
                      setErrores({ ...errores, carnet_donante: [] })
                    }}
                    aceptar="image/jpeg,image/png,image/webp,application/pdf"
                    icono={IconFileText}
                    error={err('carnet_donante')}
                  />
                </div>
              )}
            </div>
          )}

          {paso === 2 && perfil === 'hospital' && (
            <div className="mt-5 space-y-3.5">
              <div className="grid grid-cols-2 gap-3.5">
                <Campo etiqueta="Nombre del hospital" icono={IconBuildingHospital} placeholder="Hospital Clínico Viedma" value={datos.razon_social} onChange={(e) => cambiar('razon_social', e.target.value)} error={err('razon_social')} obligatorio />
                <Campo etiqueta="NIT" inputMode="numeric" placeholder="1000000011" value={datos.nit} onChange={(e) => cambiar('nit', e.target.value)} error={err('nit')} obligatorio />
                <Campo etiqueta="Dirección" icono={IconMapPin} placeholder="Av. Aniceto Arce" value={datos.direccion} onChange={(e) => cambiar('direccion', e.target.value)} error={err('direccion')} obligatorio />
                <Selector etiqueta="Zona" value={datos.zona} onChange={(e) => cambiar('zona', e.target.value)}>
                  {ZONAS.map((zona) => (
                    <option key={zona}>{zona}</option>
                  ))}
                </Selector>
                <Campo etiqueta="Teléfono del hospital" icono={IconPhone} inputMode="numeric" placeholder="44533240" value={datos.telefono_contacto} onChange={(e) => cambiar('telefono_contacto', e.target.value)} error={err('telefono_contacto')} obligatorio />
                <Campo etiqueta="WhatsApp (opcional)" inputMode="numeric" placeholder="59170000001" value={datos.whatsapp} onChange={(e) => cambiar('whatsapp', e.target.value)} error={err('whatsapp')} />
              </div>
              <div>
                <span className="mb-1.5 block text-xs font-semibold">
                  Ubicación · toca el mapa para marcarla<span className="ml-0.5 text-marca">*</span>
                </span>
                <MapaElegirPunto
                  punto={ubicacion}
                  alElegir={(punto) => {
                    setUbicacion(punto)
                    setErrores({ ...errores, latitud: [] })
                  }}
                />
                {err('latitud') && <p className="mt-1 text-xs text-marca">{err('latitud')}</p>}
              </div>
              <p className="flex items-center gap-1.5 rounded-xl bg-info-suave px-3 py-2 text-xs text-info">
                <IconShieldCheck size={16} />
                Un administrador de SEDES revisará los datos antes de activar la cuenta.
              </p>
            </div>
          )}

          {error && paso === 2 && (
            <div className="mt-4">
              <MensajeError>{error}</MensajeError>
            </div>
          )}

          <div className="mt-6 flex gap-2.5">
            {paso === 2 && (
              <Boton type="button" variante="secundario" onClick={() => setPaso(1)}>
                <IconArrowLeft size={17} />
                Atrás
              </Boton>
            )}
            <Boton type="submit" variante="principal" disabled={cargando} className="flex-1 !py-3.5 !text-[15px]">
              {paso === 1 ? 'Continuar' : cargando ? 'Registrando...' : perfil === 'donante' ? 'Registrarme en DonaVida' : 'Enviar solicitud'}
              {!cargando && <IconArrowRight size={18} />}
            </Boton>
          </div>

          <p className="mt-5 text-center text-[13px] text-texto-suave">
            ¿Ya tienes una cuenta?{' '}
            <Link to="/login" className="font-semibold text-marca">
              Iniciar sesión
            </Link>
          </p>
        </form>
      </div>
    </div>
  )
}

export default PaginaRegistro
