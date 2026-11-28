import { useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import { IconBuildingHospital, IconClock, IconMapPin, IconSpeakerphone } from '@tabler/icons-react'
import { Cargando, Etiqueta, Filtros, MensajeError, Tarjeta, Vacio } from '../../compartido/ui'
import { diaMes, hora, mensajeDeError } from '../../compartido/utilidades'
import { obtenerCampanas, type CampanaRed } from './api'

type Filtro = 'proximas' | 'pasadas' | 'canceladas'

const estados: Record<CampanaRed['estado'], { texto: string; tono: 'exito' | 'info' | 'neutro' | 'urgente' }> = {
  programada: { texto: 'Programada', tono: 'exito' },
  en_curso: { texto: 'En curso', tono: 'info' },
  finalizada: { texto: 'Finalizada', tono: 'neutro' },
  cancelada: { texto: 'Cancelada', tono: 'urgente' },
}

function TarjetaCampana({ campana }: { campana: CampanaRed }) {
  const { dia, mes } = diaMes(campana.fecha)
  const porcentaje = campana.cupos > 0 ? (campana.cupos_ocupados / campana.cupos) * 100 : 0
  const estado = estados[campana.estado]

  return (
    <div className="rounded-2xl border border-borde bg-white p-5">
      <div className="flex items-start justify-between">
        <span className="flex h-14 w-14 flex-col items-center justify-center rounded-xl bg-marca-suave font-bold leading-tight text-marca">
          <small className="text-[10px]">{mes}</small>
          <span className="text-xl">{dia}</span>
        </span>
        <Etiqueta tono={estado.tono}>{estado.texto}</Etiqueta>
      </div>
      <h3 className="mt-3.5 text-base font-bold">{campana.nombre}</h3>
      <div className="mt-1.5 space-y-1 text-xs text-texto-suave">
        <p className="flex items-center gap-1.5">
          <IconMapPin size={14} />
          {campana.lugar}
        </p>
        <p className="flex items-center gap-1.5">
          <IconClock size={14} />
          {hora(campana.inicio_utc)} – {hora(campana.fin_utc)}
        </p>
        <p className="flex items-center gap-1.5">
          <IconBuildingHospital size={14} />
          {campana.hospital.razon_social}
        </p>
      </div>
      <div className="mb-1.5 mt-3.5 flex justify-between text-xs text-texto-suave">
        <span>{campana.cupos_ocupados} inscritos</span>
        <span>{campana.cupos} cupos</span>
      </div>
      <div className="h-1.5 overflow-hidden rounded-full bg-fondo">
        <div className="h-full rounded-full bg-marca" style={{ width: `${porcentaje}%` }} />
      </div>
    </div>
  )
}

// Administrador › Campañas: jornadas de todos los hospitales de la red.
function PaginaCampanas() {
  const [filtro, setFiltro] = useState<Filtro>('proximas')
  const consulta = useQuery({ queryKey: ['admin', 'campanas'], queryFn: obtenerCampanas })

  if (consulta.isLoading) return <Cargando />
  if (consulta.error) return <MensajeError>{mensajeDeError(consulta.error)}</MensajeError>
  if (!consulta.data) return null

  const visibles = consulta.data
    .filter((c) => {
      if (filtro === 'canceladas') return c.estado === 'cancelada'
      if (filtro === 'pasadas') return c.estado === 'finalizada'
      return c.estado === 'programada' || c.estado === 'en_curso'
    })
    // Próximas: la más cercana primero. Las demás: la más reciente primero.
    .sort((a, b) => (filtro === 'proximas' ? 1 : -1) * a.inicio_utc.localeCompare(b.inicio_utc))

  return (
    <Tarjeta
      titulo="Campañas de la red"
      subtitulo="Organizadas por los hospitales"
      icono={IconSpeakerphone}
      extra={
        <Filtros
          opciones={[
            ['proximas', 'Próximas'],
            ['pasadas', 'Finalizadas'],
            ['canceladas', 'Canceladas'],
          ]}
          valor={filtro}
          alCambiar={setFiltro}
        />
      }
    >
      {visibles.length === 0 ? (
        <Vacio icono={IconSpeakerphone} titulo="No hay campañas en esta lista" />
      ) : (
        <div className="grid grid-cols-1 gap-[18px] md:grid-cols-2 xl:grid-cols-3">
          {visibles.map((campana) => (
            <TarjetaCampana key={campana.id} campana={campana} />
          ))}
        </div>
      )}
    </Tarjeta>
  )
}

export default PaginaCampanas
