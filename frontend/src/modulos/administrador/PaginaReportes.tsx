import { useQuery } from '@tanstack/react-query'
import { IconAlertTriangle, IconCalendarEvent, IconChartBar, IconChartPie, IconCircleCheck, IconDroplet, IconHeart } from '@tabler/icons-react'
import { Cargando, Cifra, Etiqueta, MensajeError, Tarjeta } from '../../compartido/ui'
import { mensajeDeError } from '../../compartido/utilidades'
import { obtenerIndicadores } from './api'
import { BarrasGrupo } from '../../compartido/graficos'

function ListaColores({ filas }: { filas: (readonly [string, number, string])[] }) {
  return (
    <div>
      {filas.map(([texto, total, color]) => (
        <div key={texto} className="flex items-center gap-3 border-t border-borde py-3.5 first:border-t-0 first:pt-0">
          <span className="h-3 w-3 rounded" style={{ background: color }} />
          <span className="flex-1 font-medium">{texto}</span>
          <b>{total}</b>
        </div>
      ))}
    </div>
  )
}

// Administrador › Reportes: indicadores de toda la red.
function PaginaReportes() {
  const consulta = useQuery({ queryKey: ['admin', 'indicadores'], queryFn: obtenerIndicadores })

  if (consulta.isLoading) return <Cargando />
  if (consulta.error) return <MensajeError>{mensajeDeError(consulta.error)}</MensajeError>
  if (!consulta.data) return null

  const datos = consulta.data
  const s = datos.solicitudes_por_estado
  const c = datos.citas_por_estado
  const totalSolicitudes = s.abierta + s.cubierta + s.cerrada + s.cancelada
  const atendidas = s.cubierta + s.cerrada
  const porcentajeAtendidas = totalSolicitudes > 0 ? Math.round((atendidas / totalSolicitudes) * 100) : null
  const citasCerradas = c.asistio + c.no_asistio
  const asistencia = citasCerradas > 0 ? Math.round((c.asistio / citasCerradas) * 100) : null

  // Grupos Rh negativo sin ninguna donación: conviene hacer una campaña
  const faltan = datos.donaciones_por_tipo_sangre.filter((g) => g.tipo_sangre.includes('-') && g.total === 0).map((g) => g.tipo_sangre.replace('-', '−'))

  return (
    <div className="space-y-[18px]">
      <div className="grid grid-cols-1 gap-[18px] sm:grid-cols-2 xl:grid-cols-4">
        <Cifra titulo="Solicitudes" valor={totalSolicitudes} detalle="Publicadas en la red" icono={IconDroplet} />
        <Cifra
          titulo="Atendidas"
          valor={atendidas}
          extra={porcentajeAtendidas !== null && <Etiqueta tono="exito">{porcentajeAtendidas}%</Etiqueta>}
          detalle="Cubiertas o cerradas"
          icono={IconCircleCheck}
          color="#16A34A"
        />
        <Cifra
          titulo="Asistencia a citas"
          valor={asistencia !== null ? `${asistencia}%` : '—'}
          detalle={`${c.asistio} de ${citasCerradas} donantes vinieron`}
          icono={IconCalendarEvent}
          color="#2563EB"
        />
        <Cifra
          titulo="Sangre recibida"
          valor={`${datos.volumen_total_ml.toLocaleString('es-BO')} ml`}
          detalle={`${datos.donaciones_validas} donaciones válidas`}
          icono={IconHeart}
        />
      </div>

      <div className="grid grid-cols-1 gap-[18px] lg:grid-cols-[1.3fr_1fr]">
        <Tarjeta titulo="Donaciones por grupo sanguíneo" subtitulo="Registradas en toda la red" icono={IconChartBar}>
          <BarrasGrupo datos={datos.donaciones_por_tipo_sangre} />
          {faltan.length > 0 && (
            <div className="mt-4 flex items-center gap-2.5 rounded-xl bg-marca-suave px-3.5 py-3 text-[13px] text-marca-oscuro">
              <IconAlertTriangle size={18} />
              <span>
                <b>Faltan {faltan.join(', ')}.</b> Conviene hacer una campaña.
              </span>
            </div>
          )}
        </Tarjeta>

        <div className="grid content-start gap-[18px]">
          <Tarjeta titulo="Solicitudes por estado" icono={IconChartPie}>
            <ListaColores
              filas={[
                ['Abiertas', s.abierta, '#2563EB'],
                ['Cubiertas', s.cubierta, '#16A34A'],
                ['Cerradas', s.cerrada, '#6B7280'],
                ['Canceladas', s.cancelada, '#9CA3AF'],
              ]}
            />
          </Tarjeta>
          <Tarjeta titulo="Citas por estado" icono={IconCalendarEvent}>
            <ListaColores
              filas={[
                ['Por atender', c.pendiente + c.confirmada, '#2563EB'],
                ['Asistieron', c.asistio, '#16A34A'],
                ['No asistieron', c.no_asistio, '#B45309'],
                ['Canceladas', c.cancelada, '#9CA3AF'],
              ]}
            />
          </Tarjeta>
        </div>
      </div>
    </div>
  )
}

export default PaginaReportes
