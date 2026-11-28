import { useState } from 'react'
import { IconAlertTriangle, IconCalendarEvent, IconCircleCheck, IconDroplet, IconRadar2, IconUserCheck } from '@tabler/icons-react'
import { citasHospitalEjemplo, puntosRadarEjemplo } from '../../compartido/datosEjemplo'
import { Cuadro, Fila, RadarDonantes, SelectorGrupos, TablaCitasHospital, type GrupoDisponible } from '../../compartido/maqueta'
import { Boton, Cifra, Etiqueta, Filtros, Tarjeta } from '../../compartido/ui'

// Hospital › Resumen (diseño de la maqueta).
// DATOS DE EJEMPLO: conectar con GET /hospital/indicadores, /hospital/solicitudes, /hospital/citas
// y /hospital/solicitudes/{id}/donantes-compatibles (funciones en ./api.ts).
const grupos: GrupoDisponible[] = [
  { codigo: 'O+', disponibles: 16, estado: 'Estable' },
  { codigo: 'O-', disponibles: 4, estado: 'Crítico' },
  { codigo: 'A+', disponibles: 11, estado: 'Estable' },
  { codigo: 'A-', disponibles: 3, estado: 'Bajo' },
  { codigo: 'B+', disponibles: 7, estado: 'Normal' },
  { codigo: 'B-', disponibles: 2, estado: 'Crítico' },
  { codigo: 'AB+', disponibles: 5, estado: 'Normal' },
  { codigo: 'AB-', disponibles: 1, estado: 'Escaso' },
]

const urgentes = [
  { grupo: 'O−', unidades: '2 u', titulo: 'Cirugía de urgencia', detalle: 'Trauma · hace 14 min', estado: '3 donantes en camino', ok: true, accion: 'Buscar donantes' },
  { grupo: 'B−', unidades: '1 u', titulo: 'Atención materna', detalle: 'Hemorragia · hace 28 min', estado: '1 donante confirmado', ok: true, accion: 'Avisar de nuevo' },
  { grupo: 'A−', unidades: '2 u', titulo: 'Traumatología', detalle: 'Hace 42 min', estado: '14 donantes avisados', ok: false, accion: 'Avisar de nuevo' },
]

function PaginaResumenHospital() {
  const [grupo, setGrupo] = useState('O-')
  const [distancia, setDistancia] = useState('5')
  const [filtroCitas, setFiltroCitas] = useState('todos')

  return (
    <div className="space-y-[18px]">
      <div className="grid grid-cols-1 gap-[18px] sm:grid-cols-2 xl:grid-cols-4">
        <Cifra titulo="Urgencias activas" valor="3" extra={<Etiqueta tono="urgente">O−, B−</Etiqueta>} detalle="Necesitan donantes ya" icono={IconAlertTriangle} />
        <Cifra titulo="Donantes aptos" valor="48" extra={<Etiqueta tono="exito">Cerca</Etiqueta>} detalle="Pasaron los 3 meses" icono={IconUserCheck} color="#16A34A" />
        <Cifra titulo="Citas para hoy" valor="12" detalle="Donantes que vendrán" icono={IconCalendarEvent} color="#2563EB" />
        <Cifra titulo="Donaciones hoy" valor="8" detalle="Bolsas recibidas" icono={IconDroplet} />
      </div>

      <div className="grid grid-cols-1 gap-[18px] lg:grid-cols-[1.5fr_1fr]">
        <div className="grid content-start gap-[18px]">
          <Tarjeta titulo="Selector rápido" subtitulo="Elige un grupo para buscar donantes" icono={IconDroplet}>
            <SelectorGrupos grupos={grupos} elegido={grupo} alElegir={setGrupo} />
            <div className="mt-3.5 flex flex-wrap items-center gap-[18px] text-[13px] text-texto-suave">
              <span>Distancia:</span>
              <Filtros
                opciones={[
                  ['5', '≤ 5 km'],
                  ['10', '≤ 10 km'],
                  ['valle', 'Todo el valle'],
                ]}
                valor={distancia}
                alCambiar={setDistancia}
              />
            </div>
          </Tarjeta>

          <Tarjeta titulo="Solicitudes urgentes" icono={IconAlertTriangle} extra={<Etiqueta tono="urgente">3 pendientes</Etiqueta>}>
            {urgentes.map((s) => (
              <Fila key={s.titulo}>
                <Cuadro tono={s.grupo === 'O−' ? 'solido' : 'suave'} className="flex-col leading-tight">
                  {s.grupo}
                  <small className="text-[10px] font-medium">{s.unidades}</small>
                </Cuadro>
                <div className="min-w-0 flex-1">
                  <b className="block">{s.titulo}</b>
                  <small className="mt-0.5 block text-xs text-texto-suave">{s.detalle}</small>
                  <small className={`mt-0.5 flex items-center gap-1 text-xs ${s.ok ? 'text-exito' : 'text-aviso'}`}>
                    <IconCircleCheck size={14} />
                    {s.estado}
                  </small>
                </div>
                <Boton variante={s.accion === 'Buscar donantes' ? 'principal' : 'borde'} pequeno>
                  {s.accion}
                </Boton>
              </Fila>
            ))}
          </Tarjeta>
        </div>

        <Tarjeta titulo="Donantes cerca" subtitulo="Valle Central · Cochabamba" icono={IconRadar2} extra={<Etiqueta tono="exito">En vivo</Etiqueta>}>
          <RadarDonantes puntos={puntosRadarEjemplo} />
          <div className="mt-3.5 flex items-center justify-between text-[13px]">
            <b>48 donantes aptos</b>
            <span className="flex gap-3 text-xs">
              <span className="text-marca">● Urgente</span>
              <span className="text-exito">● Apto</span>
            </span>
          </div>
        </Tarjeta>
      </div>

      <TablaCitasHospital titulo="Citas y donaciones de hoy" filas={citasHospitalEjemplo} filtro={filtroCitas} alFiltrar={setFiltroCitas} />
    </div>
  )
}

export default PaginaResumenHospital
