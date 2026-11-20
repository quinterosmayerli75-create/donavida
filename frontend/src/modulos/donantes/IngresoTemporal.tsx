import { useState, type FormEvent } from 'react'
import clienteApi from '../../api/cliente'
import { mensajeDeError } from './api'

// TEMPORAL: mientras el módulo de Mayerli no tenga su pantalla de login.
// Usa su endpoint POST /api/login y guarda el token con la misma clave ("token")
// que lee src/api/cliente.ts. Cuando exista el login real, este archivo se borra.

interface Props {
  alIngresar: () => void
}

function IngresoTemporal({ alIngresar }: Props) {
  const [email, setEmail] = useState('sofia@donavida.test')
  const [password, setPassword] = useState('password123')
  const [error, setError] = useState<string | null>(null)
  const [cargando, setCargando] = useState(false)

  async function ingresar(evento: FormEvent) {
    evento.preventDefault()
    setCargando(true)
    setError(null)
    try {
      const respuesta = await clienteApi.post('/login', { email, password })
      if (respuesta.data.usuario?.rol?.nombre !== 'donante') {
        setError('Esta cuenta no es de donante.')
        return
      }
      localStorage.setItem('token', respuesta.data.token)
      alIngresar()
    } catch (excepcion) {
      setError(mensajeDeError(excepcion))
    } finally {
      setCargando(false)
    }
  }

  return (
    <main className="flex min-h-screen items-center justify-center bg-red-50 p-4">
      <form onSubmit={ingresar} className="w-full max-w-sm space-y-4 rounded-xl bg-white p-6 shadow">
        <h1 className="text-2xl font-bold text-red-700">Portal del donante</h1>
        <p className="rounded bg-amber-50 p-2 text-xs text-amber-800">
          Ingreso temporal de pruebas (ana, luis o sofia @donavida.test).
        </p>

        <label className="block text-sm">
          Correo
          <input
            type="email"
            value={email}
            onChange={(e) => setEmail(e.target.value)}
            className="mt-1 w-full rounded border px-3 py-2"
          />
        </label>

        <label className="block text-sm">
          Contraseña
          <input
            type="password"
            value={password}
            onChange={(e) => setPassword(e.target.value)}
            className="mt-1 w-full rounded border px-3 py-2"
          />
        </label>

        {error && <p className="text-sm text-red-600">{error}</p>}

        <button
          type="submit"
          disabled={cargando}
          className="w-full rounded bg-red-600 py-2 font-semibold text-white hover:bg-red-700 disabled:opacity-50"
        >
          {cargando ? 'Ingresando...' : 'Ingresar'}
        </button>
      </form>
    </main>
  )
}

export default IngresoTemporal
