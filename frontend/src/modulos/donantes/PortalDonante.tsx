import { useState } from 'react'
import { useQuery, useQueryClient } from '@tanstack/react-query'
import { isAxiosError } from 'axios'
import clienteApi from '../../api/cliente'
import { obtenerPerfil } from './api'
import IngresoTemporal from './IngresoTemporal'
import FormularioPerfil from './componentes/FormularioPerfil'
import HistorialDonaciones from './componentes/HistorialDonaciones'
import MapaUbicacion from './componentes/MapaUbicacion'
import TarjetaPuedoDonar from './componentes/TarjetaPuedoDonar'

// Módulo 2: Donantes (Maya)
// Página principal del donante: ¿puedo donar?, perfil, mapa e historial.

type Pestana = 'perfil' | 'historial'

function PortalDonante() {
  const clienteConsultas = useQueryClient()
  const [hayToken, setHayToken] = useState(() => localStorage.getItem('token') !== null)
  const [pestana, setPestana] = useState<Pestana>('perfil')

  const perfil = useQuery({
    queryKey: ['donante', 'perfil'],
    queryFn: obtenerPerfil,
    enabled: hayToken,
    retry: false,
  })

  async function cerrarSesion() {
    try {
      await clienteApi.post('/logout')
    } finally {
      localStorage.removeItem('token')
      clienteConsultas.removeQueries({ queryKey: ['donante'] })
      setHayToken(false)
    }
  }

  // Sin token, o con un token vencido/de otro rol (401 o 403): pedir ingreso.
  const sinPermiso =
    isAxiosError(perfil.error) && (perfil.error.response?.status === 401 || perfil.error.response?.status === 403)

  if (!hayToken || sinPermiso) {
    return (
      <IngresoTemporal
        alIngresar={() => {
          // resetQueries borra los datos anteriores y vuelve a pedirlos con el token nuevo
          clienteConsultas.resetQueries({ queryKey: ['donante'] })
          setHayToken(true)
        }}
      />
    )
  }

  if (perfil.isLoading) return <p className="p-8">Cargando tu perfil...</p>
  if (perfil.isError || !perfil.data) {
    return <p className="p-8 text-red-600">No se pudo cargar tu perfil. ¿Está corriendo el backend?</p>
  }

  const datos = perfil.data

  return (
    <main className="min-h-screen bg-red-50">
      <header className="bg-red-700 text-white">
        <div className="mx-auto flex max-w-5xl items-center justify-between p-4">
          <div>
            <p className="text-sm opacity-80">DonaVida · Portal del donante</p>
            <h1 className="text-2xl font-bold">
              Hola, {datos.nombre}{' '}
              <span className="ml-2 rounded bg-white px-2 py-0.5 text-lg text-red-700">{datos.tipo_sangre.codigo}</span>
            </h1>
          </div>
          <button onClick={cerrarSesion} className="rounded border border-white/60 px-3 py-1 text-sm hover:bg-white/10">
            Cerrar sesión
          </button>
        </div>
      </header>

      <div className="mx-auto max-w-5xl space-y-6 p-4">
        <TarjetaPuedoDonar />

        <nav className="flex gap-2">
          {(['perfil', 'historial'] as const).map((opcion) => (
            <button
              key={opcion}
              onClick={() => setPestana(opcion)}
              className={`rounded-full px-4 py-2 text-sm font-semibold ${
                pestana === opcion ? 'bg-red-600 text-white' : 'bg-white text-gray-700 hover:bg-red-100'
              }`}
            >
              {opcion === 'perfil' ? 'Mi perfil' : 'Historial e insignias'}
            </button>
          ))}
        </nav>

        {pestana === 'perfil' ? (
          <>
            <FormularioPerfil perfil={datos} />
            <MapaUbicacion perfil={datos} />
          </>
        ) : (
          <HistorialDonaciones />
        )}
      </div>
    </main>
  )
}

export default PortalDonante
