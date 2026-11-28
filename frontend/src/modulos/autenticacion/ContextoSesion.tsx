import { createContext, useContext, useEffect, useState, type ReactNode } from 'react'
import { useQueryClient } from '@tanstack/react-query'
import * as api from './api'

// Módulo 1 (Mayerli): guarda quién inició sesión y lo comparte con toda la app.
// Cualquier pantalla puede usar: const { usuario, cerrarSesion } = useSesion()

interface ValorSesion {
  usuario: api.Usuario | null
  cargando: boolean
  guardarSesion: (token: string, usuario: api.Usuario) => void
  cerrarSesion: () => Promise<void>
}

const ContextoSesion = createContext<ValorSesion | null>(null)

export function ProveedorSesion({ children }: { children: ReactNode }) {
  const clienteConsultas = useQueryClient()
  const [usuario, setUsuario] = useState<api.Usuario | null>(null)
  const [cargando, setCargando] = useState(() => localStorage.getItem('token') !== null)

  // Al abrir la página con un token guardado, se pregunta a la API quién es el usuario.
  useEffect(() => {
    if (localStorage.getItem('token') === null) return
    api
      .obtenerUsuarioActual()
      .then(setUsuario)
      .catch(() => localStorage.removeItem('token'))
      .finally(() => setCargando(false))
  }, [])

  function guardarSesion(token: string, usuarioNuevo: api.Usuario) {
    localStorage.setItem('token', token)
    clienteConsultas.clear()
    setUsuario(usuarioNuevo)
  }

  async function cerrarSesion() {
    try {
      await api.cerrarSesion()
    } finally {
      localStorage.removeItem('token')
      clienteConsultas.clear()
      setUsuario(null)
    }
  }

  return (
    <ContextoSesion.Provider value={{ usuario, cargando, guardarSesion, cerrarSesion }}>{children}</ContextoSesion.Provider>
  )
}

export function useSesion(): ValorSesion {
  const valor = useContext(ContextoSesion)
  if (valor === null) throw new Error('useSesion debe usarse dentro de <ProveedorSesion>')
  return valor
}

// Ruta inicial de cada rol después de iniciar sesión
export function rutaInicial(rol: api.NombreRol): string {
  return { administrador: '/admin', hospital: '/hospital', donante: '/donante' }[rol]
}
