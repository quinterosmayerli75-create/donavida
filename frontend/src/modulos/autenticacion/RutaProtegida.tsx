import type { ReactNode } from 'react'
import { Navigate } from 'react-router-dom'
import { Cargando } from '../../compartido/ui'
import type { NombreRol } from './api'
import { rutaInicial, useSesion } from './ContextoSesion'

// Módulo 1 (Mayerli): deja pasar solo a usuarios con sesión y con el rol correcto.
// Si el rol no coincide, lo manda a su propio portal.
function RutaProtegida({ rol, children }: { rol: NombreRol; children: ReactNode }) {
  const { usuario, cargando } = useSesion()

  if (cargando) {
    return (
      <div className="grid min-h-screen place-items-center">
        <Cargando texto="Verificando tu sesión..." />
      </div>
    )
  }

  if (usuario === null) return <Navigate to="/login" replace />
  if (usuario.rol.nombre !== rol) return <Navigate to={rutaInicial(usuario.rol.nombre)} replace />

  return <>{children}</>
}

export default RutaProtegida
