import { useState } from 'react'
import { useMutation, useQueryClient } from '@tanstack/react-query'
import { CircleMarker, MapContainer, Popup, TileLayer, useMapEvents } from 'react-leaflet'
import { actualizarPerfil, mensajeDeError, type PerfilDonante } from '../api'

// Mapa de Leaflet: el donante hace clic donde vive y guarda esa ubicación.
// Con ella, los hospitales encuentran a los donantes más cercanos.

const CENTRO_COCHABAMBA: [number, number] = [-17.3935, -66.157]

interface Posicion {
  latitud: number
  longitud: number
}

// Componente auxiliar: escucha los clics sobre el mapa.
function CapturarClic({ alHacerClic }: { alHacerClic: (posicion: Posicion) => void }) {
  useMapEvents({
    click(evento) {
      alHacerClic({ latitud: evento.latlng.lat, longitud: evento.latlng.lng })
    },
  })
  return null
}

function MapaUbicacion({ perfil }: { perfil: PerfilDonante }) {
  const clienteConsultas = useQueryClient()
  const ubicacionGuardada: Posicion | null =
    perfil.latitud !== null && perfil.longitud !== null
      ? { latitud: perfil.latitud, longitud: perfil.longitud }
      : null

  const [nuevaUbicacion, setNuevaUbicacion] = useState<Posicion | null>(null)
  const marcador = nuevaUbicacion ?? ubicacionGuardada

  const guardar = useMutation({
    mutationFn: (posicion: Posicion) =>
      actualizarPerfil({
        // Se redondea a 7 decimales, igual que la columna decimal(10,7) de la base
        latitud: Number(posicion.latitud.toFixed(7)),
        longitud: Number(posicion.longitud.toFixed(7)),
      }),
    onSuccess: (perfilActualizado) => {
      clienteConsultas.setQueryData(['donante', 'perfil'], perfilActualizado)
      setNuevaUbicacion(null)
    },
  })

  return (
    <section className="rounded-xl bg-white p-5 shadow">
      <h2 className="text-lg font-semibold">Mi ubicación</h2>
      <p className="text-sm text-gray-600">Haz clic en el mapa para marcar dónde vives y luego guarda.</p>

      <div className="mt-3 h-80 overflow-hidden rounded-lg">
        <MapContainer
          center={marcador ? [marcador.latitud, marcador.longitud] : CENTRO_COCHABAMBA}
          zoom={14}
          className="h-full w-full"
        >
          <TileLayer
            attribution='&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
            url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
          />
          <CapturarClic alHacerClic={setNuevaUbicacion} />
          {marcador && (
            <CircleMarker
              center={[marcador.latitud, marcador.longitud]}
              radius={10}
              pathOptions={{ color: '#b91c1c', fillColor: '#ef4444', fillOpacity: 0.8 }}
            >
              <Popup>{nuevaUbicacion ? 'Nueva ubicación (sin guardar)' : 'Tu ubicación guardada'}</Popup>
            </CircleMarker>
          )}
        </MapContainer>
      </div>

      <div className="mt-3 flex flex-wrap items-center gap-3">
        <span className="text-sm text-gray-600">
          {marcador
            ? `Lat ${marcador.latitud.toFixed(5)}, Lng ${marcador.longitud.toFixed(5)}`
            : 'Aún no marcaste tu ubicación.'}
        </span>
        {nuevaUbicacion && (
          <>
            <button
              onClick={() => guardar.mutate(nuevaUbicacion)}
              disabled={guardar.isPending}
              className="rounded bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700 disabled:opacity-50"
            >
              {guardar.isPending ? 'Guardando...' : 'Guardar ubicación'}
            </button>
            <button onClick={() => setNuevaUbicacion(null)} className="text-sm text-gray-500 underline">
              Cancelar
            </button>
          </>
        )}
        {guardar.isSuccess && !nuevaUbicacion && <span className="text-sm text-green-700">Ubicación guardada ✓</span>}
        {guardar.isError && <span className="text-sm text-red-600">{mensajeDeError(guardar.error)}</span>}
      </div>
    </section>
  )
}

export default MapaUbicacion
