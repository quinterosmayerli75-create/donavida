import { useQuery } from '@tanstack/react-query'
import { formatearFecha, obtenerHistorial, type Insignia } from '../api'

// Historial de donaciones del donante y su insignia "Héroe nivel X".

// Color de la medalla según el nivel (0 = sin insignia)
const COLORES_NIVEL = [
  'bg-gray-100 text-gray-400 ring-gray-200',
  'bg-orange-100 text-orange-700 ring-orange-300',
  'bg-slate-100 text-slate-700 ring-slate-300',
  'bg-yellow-100 text-yellow-700 ring-yellow-400',
  'bg-red-100 text-red-700 ring-red-400',
]

function TarjetaInsignia({ insignia }: { insignia: Insignia }) {
  return (
    <div className="flex items-center gap-4">
      <div
        className={`flex h-20 w-20 shrink-0 flex-col items-center justify-center rounded-full ring-4 ${COLORES_NIVEL[insignia.nivel]}`}
      >
        <span className="text-2xl">🩸</span>
        <span className="text-xs font-bold">Nivel {insignia.nivel}</span>
      </div>
      <div>
        <p className="text-xl font-bold">{insignia.titulo}</p>
        {insignia.nombre && <p className="text-gray-600">{insignia.nombre}</p>}
        <p className="mt-1 text-sm text-gray-500">
          {insignia.siguiente_nivel_en === null
            ? '¡Alcanzaste el nivel máximo!'
            : `Te ${insignia.siguiente_nivel_en === 1 ? 'falta 1 donación' : `faltan ${insignia.siguiente_nivel_en} donaciones`} para el siguiente nivel.`}
        </p>
      </div>
    </div>
  )
}

function HistorialDonaciones() {
  const consulta = useQuery({ queryKey: ['donante', 'historial'], queryFn: obtenerHistorial })

  if (consulta.isLoading) return <div className="rounded-xl bg-white p-5 shadow">Cargando historial...</div>
  if (!consulta.data) return null

  const historial = consulta.data

  return (
    <section className="rounded-xl bg-white p-5 shadow">
      <h2 className="text-lg font-semibold">Mis donaciones</h2>

      <div className="mt-4 flex flex-wrap items-center justify-between gap-4">
        <TarjetaInsignia insignia={historial.insignia} />
        <div className="flex gap-6 text-center">
          <div>
            <p className="text-3xl font-bold text-red-700">{historial.total_donaciones}</p>
            <p className="text-xs text-gray-500">donaciones</p>
          </div>
          <div>
            <p className="text-3xl font-bold text-red-700">{historial.total_ml}</p>
            <p className="text-xs text-gray-500">ml donados</p>
          </div>
        </div>
      </div>

      {historial.donaciones.length === 0 ? (
        <p className="mt-6 rounded bg-red-50 p-4 text-center text-gray-600">
          Aún no tienes donaciones registradas. ¡Tu primera donación te dará la insignia "Héroe nivel 1"!
        </p>
      ) : (
        <ul className="mt-6 divide-y">
          {historial.donaciones.map((donacion) => (
            <li key={donacion.id} className="flex flex-wrap items-center justify-between gap-2 py-3">
              <div>
                <p className="font-medium">{formatearFecha(donacion.fecha_donacion)}</p>
                <p className="text-sm text-gray-600">
                  {[donacion.hospital, donacion.origen].filter(Boolean).join(' · ')}
                </p>
              </div>
              <div className="flex items-center gap-3 text-sm">
                <span>{donacion.volumen_ml} ml</span>
                <span
                  className={`rounded-full px-2 py-0.5 text-xs font-semibold ${
                    donacion.estado === 'valida' ? 'bg-green-100 text-green-700' : 'bg-gray-200 text-gray-500 line-through'
                  }`}
                >
                  {donacion.estado === 'valida' ? 'Válida' : 'Anulada'}
                </span>
              </div>
            </li>
          ))}
        </ul>
      )}
    </section>
  )
}

export default HistorialDonaciones
