import { useState } from 'react'
import { IconBuildingHospital, IconDroplet, IconNavigation } from '@tabler/icons-react'
import { Cuadro, Fila } from '../../compartido/maqueta'
import { Boton, Etiqueta, Filtros, Tarjeta } from '../../compartido/ui'

// Donante › Solicitudes (diseño de la maqueta).
// DATOS DE EJEMPLO: conectar con GET /donante/solicitudes y "Aceptar" con POST /donante/citas
// (obtenerSolicitudesDonante ya está en ./apiPortal.ts).
const ejemplo = [
  { hospital: 'Hospital Clínico Viedma', detalle: 'Cirugía de urgencia · 2 unidades', distancia: '3.1 km', urgente: true },
  { hospital: 'Hospital Materno Germán Urquidi', detalle: 'Atención materna · 1 unidad', distancia: '5.4 km', urgente: true },
  { hospital: 'Hospital del Norte', detalle: 'Jornada voluntaria · 6 unidades', distancia: '8.0 km', urgente: false },
]

function PaginaSolicitudesDonante() {
  const [filtro, setFiltro] = useState('todas')
  const visibles = filtro === 'urgentes' ? ejemplo.filter((s) => s.urgente) : ejemplo

  return (
    <Tarjeta
      titulo="Hospitales que necesitan tu ayuda"
      subtitulo="Solo ves las que puedes atender"
      icono={IconDroplet}
      extra={
        <Filtros
          opciones={[
            ['todas', 'Todas'],
            ['urgentes', 'Urgentes'],
            ['cerca', 'Cerca'],
          ]}
          valor={filtro}
          alCambiar={setFiltro}
        />
      }
    >
      {visibles.map((s) => (
        <Fila key={s.hospital}>
          <Cuadro tono={s.urgente ? 'solido' : 'neutro'}>
            <IconBuildingHospital size={20} />
          </Cuadro>
          <div className="min-w-0 flex-1">
            <b className="block">{s.hospital}</b>
            <small className="mt-0.5 block text-xs text-texto-suave">{s.detalle}</small>
          </div>
          <Etiqueta punto={false}>
            <IconNavigation size={12} /> {s.distancia}
          </Etiqueta>
          {s.urgente ? <Etiqueta tono="urgente">Urgente</Etiqueta> : <Etiqueta>Normal</Etiqueta>}
          <Boton variante={s.urgente ? 'principal' : 'borde'} pequeno>
            Aceptar
          </Boton>
        </Fila>
      ))}
    </Tarjeta>
  )
}

export default PaginaSolicitudesDonante
