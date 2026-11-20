import { useQuery } from '@tanstack/react-query'
import { consultarPuedoDonar, formatearFecha } from '../api'

// Muestra en verde o en ámbar si el donante puede donar hoy (regla de los 3 meses).
function TarjetaPuedoDonar() {
  const consulta = useQuery({ queryKey: ['donante', 'puedo-donar'], queryFn: consultarPuedoDonar })

  if (consulta.isLoading) return <div className="rounded-xl bg-white p-5 shadow">Consultando...</div>
  if (!consulta.data) return null

  const estado = consulta.data

  return (
    <section
      className={`rounded-xl p-5 shadow ${
        estado.puede_donar ? 'bg-green-50 ring-1 ring-green-300' : 'bg-amber-50 ring-1 ring-amber-300'
      }`}
    >
      <h2 className="text-sm font-semibold uppercase tracking-wide text-gray-500">¿Puedo donar hoy?</h2>
      <p className={`mt-1 text-3xl font-bold ${estado.puede_donar ? 'text-green-700' : 'text-amber-700'}`}>
        {estado.puede_donar ? 'Sí' : 'Todavía no'}
      </p>
      <p className="mt-2 text-gray-700">{estado.mensaje}</p>

      <dl className="mt-3 grid grid-cols-2 gap-2 text-sm">
        <div>
          <dt className="text-gray-500">Última donación</dt>
          <dd className="font-medium">
            {estado.fecha_ultima_donacion ? formatearFecha(estado.fecha_ultima_donacion) : 'Ninguna'}
          </dd>
        </div>
        {!estado.puede_donar && estado.proxima_fecha_para_donar && (
          <div>
            <dt className="text-gray-500">Podrás donar desde</dt>
            <dd className="font-medium">
              {formatearFecha(estado.proxima_fecha_para_donar)} ({estado.dias_restantes} días)
            </dd>
          </div>
        )}
      </dl>
      <p className="mt-3 text-xs text-gray-500">
        Deben pasar al menos {estado.meses_entre_donaciones} meses entre una donación y otra.
      </p>
    </section>
  )
}

export default TarjetaPuedoDonar
