import { useQuery } from '@tanstack/react-query'
import clienteApi from '../api/cliente'

interface TipoSangre {
  id: number
  codigo: string
  puede_recibir_de: string[]
}

// Página de prueba: confirma que el frontend se conecta con la API de Laravel.
function Inicio() {
  const consultaTipos = useQuery({
    queryKey: ['tipos-sangre'],
    queryFn: async () => {
      const respuesta = await clienteApi.get<TipoSangre[]>('/tipos-sangre')
      return respuesta.data
    },
  })

  return (
    <main className="min-h-screen bg-red-50 p-8">
      <h1 className="text-3xl font-bold text-red-700">DonaVida</h1>
      <p className="mt-2 text-gray-700">Red de donantes de sangre – Cochabamba</p>

      <section className="mt-8">
        <h2 className="text-xl font-semibold">Prueba de conexión con la API</h2>

        {consultaTipos.isLoading && <p>Cargando...</p>}

        {consultaTipos.isError && (
          <p className="text-red-600">
            No se pudo conectar. ¿Está corriendo <code>php artisan serve</code>?
          </p>
        )}

        {consultaTipos.data && (
          <ul className="mt-4 grid grid-cols-2 gap-2 md:grid-cols-4">
            {consultaTipos.data.map((tipo) => (
              <li key={tipo.id} className="rounded bg-white p-3 shadow">
                <strong className="text-red-700">{tipo.codigo}</strong>
                <span className="block text-sm text-gray-600">
                  Recibe de: {tipo.puede_recibir_de.join(', ')}
                </span>
              </li>
            ))}
          </ul>
        )}
      </section>
    </main>
  )
}

export default Inicio
