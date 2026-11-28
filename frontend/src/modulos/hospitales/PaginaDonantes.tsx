import { useState } from 'react'
import { IconMap2, IconUsers } from '@tabler/icons-react'
import { puntosRadarEjemplo } from '../../compartido/datosEjemplo'
import { GrupoTabla, Iniciales, RadarDonantes } from '../../compartido/maqueta'
import { Boton, Etiqueta, Filtros, Tabla, Tarjeta } from '../../compartido/ui'

// Hospital › Donantes (diseño de la maqueta).
// DATOS DE EJEMPLO: conectar con GET /hospital/solicitudes/{id}/donantes-compatibles.
const ejemplo = [
  { nombre: 'Carlos Mendoza', grupo: 'O-', distancia: '1.8 km', ultima: 'hace 5 meses', estado: 'Apto' },
  { nombre: 'Andrea Zubieta', grupo: 'O-', distancia: '3.2 km', ultima: 'hace 4 meses', estado: 'En camino' },
  { nombre: 'Rodrigo Paz', grupo: 'O-', distancia: '4.1 km', ultima: 'hace 6 meses', estado: 'Apto' },
  { nombre: 'Luis Vargas', grupo: 'O-', distancia: '6.0 km', ultima: 'hace 1 mes', estado: 'Debe esperar' },
]

function PaginaDonantes() {
  const [distancia, setDistancia] = useState('5')

  return (
    <div className="grid grid-cols-1 gap-[18px] xl:grid-cols-[1.6fr_1fr]">
      <Tarjeta
        titulo="Donantes compatibles · solicitud O−"
        subtitulo="Ordenados por cercanía"
        icono={IconUsers}
        extra={
          <Filtros
            opciones={[
              ['5', '≤ 5 km'],
              ['10', '≤ 10 km'],
            ]}
            valor={distancia}
            alCambiar={setDistancia}
          />
        }
      >
        <Tabla columnas={['Donante', 'Grupo', 'Distancia', 'Última donación', 'Estado', 'Acción']}>
          {ejemplo.map((d) => (
            <tr key={d.nombre}>
              <td>
                <div className="flex items-center gap-2.5">
                  <Iniciales nombre={d.nombre} />
                  <b>{d.nombre}</b>
                </div>
              </td>
              <td>
                <GrupoTabla codigo={d.grupo} />
              </td>
              <td>{d.distancia}</td>
              <td>{d.ultima}</td>
              <td>
                {d.estado === 'Apto' ? (
                  <Etiqueta tono="exito">Apto</Etiqueta>
                ) : d.estado === 'En camino' ? (
                  <Etiqueta tono="info">En camino</Etiqueta>
                ) : (
                  <Etiqueta>{d.estado}</Etiqueta>
                )}
              </td>
              <td>
                {d.estado === 'Apto' ? (
                  <Boton variante="principal" pequeno>
                    Avisar
                  </Boton>
                ) : d.estado === 'En camino' ? (
                  <Boton pequeno>Llamar</Boton>
                ) : (
                  '—'
                )}
              </td>
            </tr>
          ))}
        </Tabla>
      </Tarjeta>

      <Tarjeta titulo="Mapa" icono={IconMap2}>
        <RadarDonantes puntos={puntosRadarEjemplo} />
      </Tarjeta>
    </div>
  )
}

export default PaginaDonantes
