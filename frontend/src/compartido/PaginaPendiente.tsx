import { useLocation } from 'react-router-dom'
import { IconTool } from '@tabler/icons-react'
import { useSesion } from '../modulos/autenticacion/ContextoSesion'
import { menus } from './menus'
import { Etiqueta, Tarjeta } from './ui'

// Lugar reservado para una pantalla que todavía hace otro integrante.
// Para reemplazarla: crear la página en src/modulos/<modulo>/ y cambiar la ruta en App.tsx.
function PaginaPendiente() {
  const { usuario } = useSesion()
  const { pathname } = useLocation()
  if (usuario === null) return null

  const opcion = menus[usuario.rol.nombre].opciones.find((item) => item.ruta === pathname)

  return (
    <Tarjeta>
      <div className="flex flex-col items-center py-10 text-center">
        <span className="mb-4 grid h-14 w-14 place-items-center rounded-2xl bg-marca-suave text-marca">
          <IconTool size={28} />
        </span>
        <Etiqueta tono="aviso">En construcción</Etiqueta>
        <h2 className="mt-3 text-xl font-bold">{opcion?.texto ?? 'Pantalla'}</h2>
        <p className="mt-1 max-w-md text-texto-suave">
          Esta pantalla la arma el integrante de este módulo siguiendo la guía <code>docs/DISENO.md</code>.
        </p>
        {opcion?.api && (
          <div className="mt-6 w-full max-w-md rounded-xl bg-fondo p-4 text-left">
            <p className="mb-2 text-xs font-semibold text-texto-suave">Rutas de la API que usa</p>
            {opcion.api.map((ruta) => (
              <code key={ruta} className="block py-0.5 text-xs">
                {ruta}
              </code>
            ))}
          </div>
        )}
      </div>
    </Tarjeta>
  )
}

export default PaginaPendiente
