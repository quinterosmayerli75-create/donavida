import { useState, type FormEvent } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { IconBuildingHospital, IconId, IconLock, IconMail, IconMapPin, IconPhone, IconUser } from '@tabler/icons-react'
import { MapaElegirPunto, ZONAS } from '../../compartido/MapaElegirPunto'
import { Boton, Campo, CampoClave, Cargando, MensajeError, RequisitosClave, Selector, Ventana } from '../../compartido/ui'
import { erroresDeValidacion, mensajeDeError, type ErroresCampos } from '../../compartido/utilidades'
import { soloLetras, soloNumeros, validarCuenta } from '../../compartido/validaciones'
import { obtenerTiposSangre } from '../autenticacion/api'
import { guardarDonante, guardarHospital, obtenerDonante, type DatosDonanteAdmin, type DatosHospitalAdmin, type Hospital } from './api'

// Administrador: ventanas para crear y editar donantes y hospitales.

// Al escribir: nombres solo letras; teléfonos, carnet y NIT solo números
function limpiar(campo: string, valor: string): string {
  if (campo === 'nombre' || campo === 'apellidos') return soloLetras(valor)
  if (campo === 'telefono' || campo === 'telefono_contacto' || campo === 'whatsapp') return soloNumeros(valor, 15)
  if (campo === 'ci') return soloNumeros(valor, 12)
  if (campo === 'nit') return soloNumeros(valor, 20)
  return valor
}

function Botones({ cargando, alCerrar, nuevo }: { cargando: boolean; alCerrar: () => void; nuevo: boolean }) {
  return (
    <div className="mt-5 flex justify-end gap-2.5">
      <Boton type="button" onClick={alCerrar}>
        Cancelar
      </Boton>
      <Boton type="submit" variante="principal" disabled={cargando}>
        {cargando ? 'Guardando...' : nuevo ? 'Crear' : 'Guardar cambios'}
      </Boton>
    </div>
  )
}

function Clave({ nuevo, datos, cambiar, err }: { nuevo: boolean; datos: { password: string; password_confirmation: string }; cambiar: (campo: 'password' | 'password_confirmation', valor: string) => void; err: (campo: string) => string | undefined }) {
  return (
    <div className="sm:col-span-2">
      <div className="grid grid-cols-1 gap-3.5 sm:grid-cols-2">
        <CampoClave etiqueta={nuevo ? 'Contraseña' : 'Contraseña nueva (opcional)'} icono={IconLock} value={datos.password} onChange={(e) => cambiar('password', e.target.value)} error={err('password')} obligatorio={nuevo} autoComplete="new-password" />
        <CampoClave etiqueta="Confirmar contraseña" icono={IconLock} value={datos.password_confirmation} onChange={(e) => cambiar('password_confirmation', e.target.value)} error={err('password_confirmation')} obligatorio={nuevo} autoComplete="new-password" />
      </div>
      {(nuevo || datos.password !== '') && <RequisitosClave clave={datos.password} />}
      {!nuevo && <p className="mt-1 text-[11px] text-texto-suave">Déjala vacía para no cambiarla.</p>}
    </div>
  )
}

// ---------- Donante ----------

const donanteVacio: DatosDonanteAdmin = {
  nombre: '',
  apellidos: '',
  email: '',
  telefono: '',
  ci: '',
  fecha_nacimiento: '',
  sexo: '',
  tipo_sangre_id: 0,
  zona: ZONAS[0],
  fecha_ultima_donacion: '',
  password: '',
  password_confirmation: '',
}

export function VentanaDonante({ donanteId, alCerrar }: { donanteId: number | null; alCerrar: () => void }) {
  const existente = useQuery({ queryKey: ['admin', 'donante', donanteId], queryFn: () => obtenerDonante(donanteId!), enabled: donanteId !== null })
  const titulo = donanteId === null ? 'Nuevo donante' : 'Editar donante'

  return (
    <Ventana titulo={titulo} alCerrar={alCerrar}>
      {donanteId !== null && existente.isLoading ? (
        <Cargando />
      ) : existente.error ? (
        <MensajeError>{mensajeDeError(existente.error)}</MensajeError>
      ) : (
        <FormularioDonante
          donanteId={donanteId}
          inicial={
            existente.data
              ? {
                  ...donanteVacio,
                  ...existente.data,
                  telefono: existente.data.telefono ?? '',
                  zona: existente.data.zona ?? '',
                  fecha_ultima_donacion: existente.data.fecha_ultima_donacion ?? '',
                }
              : donanteVacio
          }
          alCerrar={alCerrar}
        />
      )}
    </Ventana>
  )
}

function FormularioDonante({ donanteId, inicial, alCerrar }: { donanteId: number | null; inicial: DatosDonanteAdmin; alCerrar: () => void }) {
  const nuevo = donanteId === null
  const [datos, setDatos] = useState(inicial)
  const [errores, setErrores] = useState<ErroresCampos>({})
  const tipos = useQuery({ queryKey: ['tipos-sangre'], queryFn: obtenerTiposSangre })
  const clienteConsultas = useQueryClient()
  const guardar = useMutation({
    mutationFn: () => guardarDonante(donanteId, datos),
    onSuccess: () => {
      clienteConsultas.invalidateQueries({ queryKey: ['admin'] })
      alCerrar()
    },
    onError: (error) => setErrores(erroresDeValidacion(error)),
  })

  const err = (campo: string) => errores[campo]?.[0]
  function cambiar(campo: keyof DatosDonanteAdmin, valor: string | number) {
    setDatos({ ...datos, [campo]: typeof valor === 'string' ? limpiar(campo, valor) : valor })
    setErrores({ ...errores, [campo]: [] })
  }

  function enviar(evento: FormEvent) {
    evento.preventDefault()
    const nuevos = validarCuenta({ ...datos, passwordOpcional: !nuevo })
    if (!datos.tipo_sangre_id) nuevos.tipo_sangre_id = ['Elige el grupo sanguíneo.']
    setErrores(nuevos)
    if (Object.keys(nuevos).length === 0) guardar.mutate()
  }

  return (
    <form onSubmit={enviar}>
      <div className="grid grid-cols-1 gap-3.5 sm:grid-cols-2">
        <Campo etiqueta="Nombre" icono={IconUser} value={datos.nombre} onChange={(e) => cambiar('nombre', e.target.value)} error={err('nombre')} obligatorio />
        <Campo etiqueta="Apellidos" value={datos.apellidos} onChange={(e) => cambiar('apellidos', e.target.value)} error={err('apellidos')} obligatorio />
        <Campo etiqueta="Correo" type="email" icono={IconMail} value={datos.email} onChange={(e) => cambiar('email', e.target.value)} error={err('email')} obligatorio />
        <Campo etiqueta="Teléfono" icono={IconPhone} inputMode="numeric" value={datos.telefono} onChange={(e) => cambiar('telefono', e.target.value)} error={err('telefono')} obligatorio />
        <Campo etiqueta="Carnet de identidad" icono={IconId} inputMode="numeric" value={datos.ci} onChange={(e) => cambiar('ci', e.target.value)} error={err('ci')} obligatorio />
        <Campo etiqueta="Fecha de nacimiento" type="date" value={datos.fecha_nacimiento} onChange={(e) => cambiar('fecha_nacimiento', e.target.value)} error={err('fecha_nacimiento')} obligatorio />
        <Selector etiqueta="Sexo" value={datos.sexo} onChange={(e) => cambiar('sexo', e.target.value)} error={err('sexo')} obligatorio>
          <option value="">Elige una opción</option>
          <option value="F">Femenino</option>
          <option value="M">Masculino</option>
        </Selector>
        <Selector etiqueta="Grupo sanguíneo" value={datos.tipo_sangre_id} onChange={(e) => cambiar('tipo_sangre_id', Number(e.target.value))} error={err('tipo_sangre_id')} obligatorio>
          <option value={0}>Elige el grupo</option>
          {tipos.data?.map((tipo) => (
            <option key={tipo.id} value={tipo.id}>
              {tipo.codigo.replace('-', '−')}
            </option>
          ))}
        </Selector>
        <Selector etiqueta="Zona" value={datos.zona} onChange={(e) => cambiar('zona', e.target.value)}>
          <option value="">Sin zona</option>
          {ZONAS.map((zona) => (
            <option key={zona}>{zona}</option>
          ))}
        </Selector>
        <Campo etiqueta="Última donación" type="date" max={new Date().toISOString().slice(0, 10)} value={datos.fecha_ultima_donacion} onChange={(e) => cambiar('fecha_ultima_donacion', e.target.value)} error={err('fecha_ultima_donacion')} />
        <Clave nuevo={nuevo} datos={datos} cambiar={cambiar} err={err} />
      </div>
      {guardar.error && Object.keys(errores).length === 0 && (
        <div className="mt-4">
          <MensajeError>{mensajeDeError(guardar.error)}</MensajeError>
        </div>
      )}
      <Botones cargando={guardar.isPending} alCerrar={alCerrar} nuevo={nuevo} />
    </form>
  )
}

// ---------- Hospital ----------

const hospitalVacio: DatosHospitalAdmin = {
  nombre: '',
  email: '',
  telefono: '',
  razon_social: '',
  nit: '',
  direccion: '',
  zona: ZONAS[0],
  telefono_contacto: '',
  whatsapp: '',
  latitud: null,
  longitud: null,
  password: '',
  password_confirmation: '',
}

function datosDeHospital(h: Hospital): DatosHospitalAdmin {
  return {
    ...hospitalVacio,
    nombre: h.usuario.nombre,
    email: h.usuario.email,
    telefono: h.usuario.telefono ?? '',
    razon_social: h.razon_social,
    nit: h.nit,
    direccion: h.direccion,
    zona: h.zona ?? '',
    telefono_contacto: h.telefono_contacto,
    whatsapp: h.whatsapp ?? '',
    latitud: Number(h.latitud),
    longitud: Number(h.longitud),
  }
}

export function VentanaHospital({ hospital, alCerrar }: { hospital: Hospital | null; alCerrar: () => void }) {
  const nuevo = hospital === null
  const [datos, setDatos] = useState(hospital ? datosDeHospital(hospital) : hospitalVacio)
  const [errores, setErrores] = useState<ErroresCampos>({})
  const clienteConsultas = useQueryClient()
  const guardar = useMutation({
    mutationFn: () => guardarHospital(hospital?.id ?? null, datos),
    onSuccess: () => {
      clienteConsultas.invalidateQueries({ queryKey: ['admin'] })
      alCerrar()
    },
    onError: (error) => setErrores(erroresDeValidacion(error)),
  })

  const err = (campo: string) => errores[campo]?.[0]
  function cambiar(campo: keyof DatosHospitalAdmin, valor: string) {
    setDatos({ ...datos, [campo]: limpiar(campo, valor) })
    setErrores({ ...errores, [campo]: [] })
  }

  function enviar(evento: FormEvent) {
    evento.preventDefault()
    const nuevos = validarCuenta({ ...datos, passwordOpcional: !nuevo })
    if (datos.latitud === null) nuevos.latitud = ['Marca la ubicación del hospital en el mapa.']
    setErrores(nuevos)
    if (Object.keys(nuevos).length === 0) guardar.mutate()
  }

  return (
    <Ventana titulo={nuevo ? 'Nuevo hospital' : 'Editar hospital'} alCerrar={alCerrar}>
      <form onSubmit={enviar}>
        <div className="grid grid-cols-1 gap-3.5 sm:grid-cols-2">
          <Campo etiqueta="Nombre del hospital" icono={IconBuildingHospital} value={datos.razon_social} onChange={(e) => cambiar('razon_social', e.target.value)} error={err('razon_social')} obligatorio />
          <Campo etiqueta="NIT" inputMode="numeric" value={datos.nit} onChange={(e) => cambiar('nit', e.target.value)} error={err('nit')} obligatorio />
          <Campo etiqueta="Dirección" icono={IconMapPin} value={datos.direccion} onChange={(e) => cambiar('direccion', e.target.value)} error={err('direccion')} obligatorio />
          <Selector etiqueta="Zona" value={datos.zona} onChange={(e) => cambiar('zona', e.target.value)}>
            <option value="">Sin zona</option>
            {ZONAS.map((zona) => (
              <option key={zona}>{zona}</option>
            ))}
          </Selector>
          <Campo etiqueta="Teléfono del hospital" icono={IconPhone} inputMode="numeric" value={datos.telefono_contacto} onChange={(e) => cambiar('telefono_contacto', e.target.value)} error={err('telefono_contacto')} obligatorio />
          <Campo etiqueta="WhatsApp" inputMode="numeric" value={datos.whatsapp} onChange={(e) => cambiar('whatsapp', e.target.value)} error={err('whatsapp')} />
          <Campo etiqueta="Nombre del responsable" icono={IconUser} value={datos.nombre} onChange={(e) => cambiar('nombre', e.target.value)} error={err('nombre')} obligatorio />
          <Campo etiqueta="Teléfono del responsable" icono={IconPhone} inputMode="numeric" value={datos.telefono} onChange={(e) => cambiar('telefono', e.target.value)} error={err('telefono')} obligatorio />
          <div className="sm:col-span-2">
            <Campo etiqueta="Correo para iniciar sesión" type="email" icono={IconMail} value={datos.email} onChange={(e) => cambiar('email', e.target.value)} error={err('email')} obligatorio />
          </div>
          <div className="sm:col-span-2">
            <span className="mb-1.5 block text-xs font-semibold">
              Ubicación · toca el mapa para marcarla<span className="ml-0.5 text-marca">*</span>
            </span>
            <MapaElegirPunto
              punto={datos.latitud !== null && datos.longitud !== null ? [datos.latitud, datos.longitud] : null}
              alElegir={([latitud, longitud]) => {
                setDatos({ ...datos, latitud, longitud })
                setErrores({ ...errores, latitud: [] })
              }}
              alto="h-40"
            />
            {err('latitud') && <p className="mt-1 text-xs text-marca">{err('latitud')}</p>}
          </div>
          <Clave nuevo={nuevo} datos={datos} cambiar={cambiar} err={err} />
        </div>
        {nuevo && <p className="mt-3 text-[11px] text-texto-suave">Un hospital creado por el administrador queda validado y activo.</p>}
        {guardar.error && Object.keys(errores).length === 0 && (
          <div className="mt-4">
            <MensajeError>{mensajeDeError(guardar.error)}</MensajeError>
          </div>
        )}
        <Botones cargando={guardar.isPending} alCerrar={alCerrar} nuevo={nuevo} />
      </form>
    </Ventana>
  )
}
