import { useQuery } from '@tanstack/react-query'
import { Link } from 'react-router-dom'
import {
  IconActivity,
  IconBuildingHospital,
  IconChartBar,
  IconCircleCheck,
  IconDroplet,
  IconHeartHandshake,
  IconMapPin,
  IconSpeakerphone,
  IconUserPlus,
  IconUsers,
} from '@tabler/icons-react'
import { BarrasGrupo } from '../../compartido/graficos'
import { CajaFecha, Detalle, Fila } from '../../compartido/maqueta'
import { Boton, Cargando, Cifra, Etiqueta, MensajeError, Tarjeta, Vacio } from '../../compartido/ui'
import { diaMes, haceCuanto, mensajeDeError } from '../../compartido/utilidades'
import { obtenerCampanas, obtenerHospitales, obtenerIndicadores, obtenerUsuarios } from './api'
import { FilaPendiente } from './componentes'

const GRUPOS = ['O+', 'O-', 'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-']

// Administrador › Resumen: lo más importante de toda la red (datos reales de la API).
function PaginaResumen() {
  const indicadores = useQuery({ queryKey: ['admin', 'indicadores'], queryFn: obtenerIndicadores })
  const hospitales = useQuery({ queryKey: ['admin', 'hospitales'], queryFn: obtenerHospitales })
  const usuarios = useQuery({ queryKey: ['admin', 'usuarios'], queryFn: obtenerUsuarios })
  const campanas = useQuery({ queryKey: ['admin', 'campanas'], queryFn: obtenerCampanas })
  const consultas = [indicadores, hospitales, usuarios, campanas]

  if (consultas.some((c) => c.isLoading)) return <Cargando />
  const error = consultas.find((c) => c.error)?.error
  if (error) return <MensajeError>{mensajeDeError(error)}</MensajeError>
  if (!indicadores.data || !hospitales.data || !usuarios.data || !campanas.data) return null

  const datos = indicadores.data
  // Pendiente = no validado y no desactivado por el administrador
  const pendientes = hospitales.data.filter((h) => !h.verificado && h.usuario.estado !== 'inactivo')
  const validados = hospitales.data.filter((h) => h.verificado).length
  const donantes = usuarios.data.usuarios.filter((u) => u.rol === 'donante')
  const mesActual = new Date().toISOString().slice(0, 7)
  const nuevosEsteMes = donantes.filter((u) => u.created_at.startsWith(mesActual)).length
  const citas = datos.citas_por_estado
  const citasCerradas = citas.asistio + citas.no_asistio
  const asistencia = citasCerradas > 0 ? Math.round((citas.asistio / citasCerradas) * 100) : null

  // Donantes registrados por grupo sanguíneo
  const porGrupo = GRUPOS.map((codigo) => ({ tipo_sangre: codigo, total: donantes.filter((u) => u.tipo_sangre === codigo).length }))

  // Actividad reciente: las últimas cuentas creadas
  const actividad = usuarios.data.usuarios
    .filter((u) => u.rol !== 'administrador')
    .slice(0, 3)
    .map((u) =>
      u.rol === 'donante'
        ? { icono: IconUserPlus, color: '#16A34A', texto: `Nuevo donante: ${u.nombre}${u.tipo_sangre ? ` (${u.tipo_sangre.replace('-', '−')})` : ''}`, cuando: u.created_at }
        : { icono: IconBuildingHospital, color: '#2563EB', texto: `Nuevo hospital: ${u.nombre}`, cuando: u.created_at },
    )

  const proximas = campanas.data
    .filter((c) => c.estado === 'programada' || c.estado === 'en_curso')
    .sort((a, b) => a.inicio_utc.localeCompare(b.inicio_utc))
    .slice(0, 2)

  return (
    <div className="space-y-[18px]">
      <div className="grid grid-cols-1 gap-[18px] sm:grid-cols-2 xl:grid-cols-4">
        <Cifra
          titulo="Donantes registrados"
          valor={usuarios.data.totales.donante}
          extra={<Etiqueta tono="exito">+{nuevosEsteMes} este mes</Etiqueta>}
          detalle="Activos en la plataforma"
          icono={IconUsers}
        />
        <Cifra
          titulo="Hospitales en la red"
          valor={validados}
          extra={pendientes.length > 0 && <Etiqueta tono="aviso">{pendientes.length} por validar</Etiqueta>}
          detalle="Conectados a DonaVida"
          icono={IconBuildingHospital}
          color="#2563EB"
        />
        <Cifra titulo="Solicitudes activas" valor={datos.solicitudes_por_estado.abierta} detalle="En todos los hospitales" icono={IconDroplet} />
        <Cifra
          titulo="Donaciones registradas"
          valor={datos.donaciones_validas}
          extra={asistencia !== null && <Etiqueta tono="exito">{asistencia}% asistencia</Etiqueta>}
          detalle="Registradas por hospitales"
          icono={IconHeartHandshake}
          color="#16A34A"
        />
      </div>

      <div className="grid grid-cols-1 gap-[18px] lg:grid-cols-[1.4fr_1fr]">
        <Tarjeta titulo="Hospitales por validar" subtitulo="Revisa sus datos antes de activarlos" icono={IconBuildingHospital}>
          {pendientes.length === 0 ? (
            <Vacio icono={IconCircleCheck} titulo="Todo al día" texto="No hay hospitales esperando validación." />
          ) : (
            pendientes.map((hospital) => <FilaPendiente key={hospital.id} hospital={hospital} />)
          )}
        </Tarjeta>

        <Tarjeta titulo="Actividad reciente" icono={IconActivity}>
          {actividad.map(({ icono: IconoActividad, color, texto, cuando }) => (
            <Fila key={texto}>
              <span className="grid h-[38px] w-[38px] flex-none place-items-center rounded-full" style={{ background: `${color}14`, color }}>
                <IconoActividad size={19} />
              </span>
              <div className="min-w-0 flex-1">
                <b className="block text-[13px]">{texto}</b>
                <small className="text-xs text-texto-suave">{haceCuanto(cuando)}</small>
              </div>
            </Fila>
          ))}
        </Tarjeta>
      </div>

      <div className="grid grid-cols-1 gap-[18px] lg:grid-cols-2">
        <Tarjeta titulo="Donantes por grupo sanguíneo" subtitulo="Total registrados en la red" icono={IconChartBar}>
          <BarrasGrupo datos={porGrupo} />
        </Tarjeta>

        <Tarjeta
          titulo="Campañas próximas"
          icono={IconSpeakerphone}
          extra={
            <Link to="/admin/campanas">
              <Boton variante="borde" pequeno>
                Ver todas
              </Boton>
            </Link>
          }
        >
          {proximas.length === 0 ? (
            <Vacio icono={IconSpeakerphone} titulo="No hay campañas próximas" />
          ) : (
            proximas.map((c) => {
              const { dia, mes } = diaMes(c.fecha)
              return (
                <Fila key={c.id}>
                  <CajaFecha mes={mes} dia={dia} />
                  <div className="min-w-0 flex-1">
                    <b className="block">{c.nombre}</b>
                    <Detalle icono={IconMapPin}>
                      {c.lugar} · {c.hospital.razon_social}
                    </Detalle>
                  </div>
                  <Etiqueta tono="exito">{c.estado === 'en_curso' ? 'En curso' : 'Aprobada'}</Etiqueta>
                </Fila>
              )
            })
          )}
        </Tarjeta>
      </div>
    </div>
  )
}

export default PaginaResumen
