import { useState } from 'react'
import { IconActivity, IconAlertTriangle, IconArrowRight, IconCircleCheck, IconDroplet, IconMapPin } from '@tabler/icons-react'
import { Cuadro, Detalle, Fila, Progreso } from '../../compartido/maqueta'
import { Boton, Cifra, Etiqueta, Filtros, Tarjeta } from '../../compartido/ui'

// Hospital › Solicitudes (diseño de la maqueta).
// DATOS DE EJEMPLO: conectar con GET/POST/PUT/DELETE /hospital/solicitudes
// (obtenerSolicitudesHospital ya está en ./api.ts).
const ejemplo = [
  { grupo: 'O−', titulo: 'Cirugía de urgencia', lugar: 'Pabellón Central · 4 unidades', estado: 'Urgente', avance: '2 de 4', porcentaje: 50 },
  { grupo: 'B−', titulo: 'Atención materna', lugar: 'Maternidad · 2 unidades', estado: 'Urgente', avance: '1 de 2', porcentaje: 50 },
  { grupo: 'O+', titulo: 'Jornada voluntaria', lugar: 'Pabellón Central · 6 unidades', estado: 'Normal', avance: '3 de 6', porcentaje: 50 },
  { grupo: 'AB+', titulo: 'Traumatología', lugar: 'Temporal · 2 unidades', estado: 'Completada', avance: '2 de 2', porcentaje: 100 },
]

const tonos = { Urgente: 'urgente', Normal: 'neutro', Completada: 'exito' } as const

function PaginaSolicitudes() {
  const [filtro, setFiltro] = useState('todas')
  const visibles = ejemplo.filter((s) => (filtro === 'urgentes' ? s.estado === 'Urgente' : filtro === 'completadas' ? s.estado === 'Completada' : true))

  return (
    <div className="space-y-[18px]">
      <div className="grid grid-cols-1 gap-[18px] sm:grid-cols-2 xl:grid-cols-4">
        <Cifra titulo="Activas" valor="4" detalle="En curso" icono={IconDroplet} />
        <Cifra titulo="Urgentes" valor="2" detalle="Prioridad alta" icono={IconAlertTriangle} />
        <Cifra titulo="Unidades pedidas" valor="15" detalle="En solicitudes abiertas" icono={IconActivity} color="#2563EB" />
        <Cifra titulo="Completadas" valor="12" detalle="Este mes" icono={IconCircleCheck} color="#16A34A" />
      </div>

      <Tarjeta
        titulo="Solicitudes en curso"
        icono={IconDroplet}
        extra={
          <Filtros
            opciones={[
              ['todas', 'Todas'],
              ['urgentes', 'Urgentes'],
              ['completadas', 'Completadas'],
            ]}
            valor={filtro}
            alCambiar={setFiltro}
          />
        }
      >
        {visibles.map((s) => (
          <Fila key={s.titulo}>
            <Cuadro tono={s.estado === 'Urgente' ? 'solido' : 'suave'} redondo>
              {s.grupo}
            </Cuadro>
            <div className="min-w-0 flex-1">
              <b className="block">{s.titulo}</b>
              <Detalle icono={IconMapPin}>{s.lugar}</Detalle>
            </div>
            <div className="text-right text-xs text-texto-suave">
              {s.avance} donantes
              <br />
              <Progreso porcentaje={s.porcentaje} />
            </div>
            <Etiqueta tono={tonos[s.estado as keyof typeof tonos]}>{s.estado}</Etiqueta>
            <Boton variante="borde" pequeno>
              {s.estado === 'Completada' ? 'Ver detalle' : 'Ver donantes'} <IconArrowRight size={14} />
            </Boton>
          </Fila>
        ))}
      </Tarjeta>
    </div>
  )
}

export default PaginaSolicitudes
