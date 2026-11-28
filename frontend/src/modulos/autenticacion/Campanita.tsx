import { useEffect, useRef, useState } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { IconBell, IconBellOff } from '@tabler/icons-react'
import { haceCuanto } from '../../compartido/utilidades'
import * as api from './api'

// Módulo 1 (Mayerli): campanita de avisos con el globito de no leídas.
// Por ahora pregunta a la API cada 30 segundos; luego se cambia por Laravel Echo (tiempo real).
function Campanita() {
  const clienteConsultas = useQueryClient()
  const [abierta, setAbierta] = useState(false)
  const contenedor = useRef<HTMLDivElement>(null)

  const noLeidas = useQuery({ queryKey: ['notificaciones', 'no-leidas'], queryFn: api.contarNoLeidas, refetchInterval: 30000 })
  const lista = useQuery({ queryKey: ['notificaciones', 'lista'], queryFn: api.listarNotificaciones, enabled: abierta })

  const actualizar = () => clienteConsultas.invalidateQueries({ queryKey: ['notificaciones'] })
  const leerUna = useMutation({ mutationFn: api.marcarLeida, onSuccess: actualizar })
  const leerTodas = useMutation({ mutationFn: api.marcarTodasLeidas, onSuccess: actualizar })

  // Cerrar el panel al hacer clic fuera de él
  useEffect(() => {
    function alHacerClic(evento: MouseEvent) {
      if (contenedor.current && !contenedor.current.contains(evento.target as Node)) setAbierta(false)
    }
    document.addEventListener('mousedown', alHacerClic)
    return () => document.removeEventListener('mousedown', alHacerClic)
  }, [])

  const cantidad = noLeidas.data ?? 0

  return (
    <div className="relative" ref={contenedor}>
      <button
        onClick={() => setAbierta(!abierta)}
        className="relative grid h-10 w-10 place-items-center rounded-full border border-borde text-texto hover:bg-fondo"
        aria-label="Notificaciones"
      >
        <IconBell size={19} />
        {cantidad > 0 && (
          <span className="absolute -right-1 -top-1 grid h-5 min-w-5 place-items-center rounded-full border-2 border-white bg-marca px-1 text-[10px] font-bold text-white">
            {cantidad > 9 ? '9+' : cantidad}
          </span>
        )}
      </button>

      {abierta && (
        <div className="absolute right-0 top-12 z-[900] w-80 overflow-hidden rounded-2xl border border-borde bg-white shadow-xl">
          <div className="flex items-center justify-between border-b border-borde px-4 py-3">
            <b>Notificaciones</b>
            {cantidad > 0 && (
              <button onClick={() => leerTodas.mutate()} className="text-xs font-semibold text-marca">
                Marcar todas como leídas
              </button>
            )}
          </div>
          <div className="max-h-96 overflow-y-auto">
            {lista.isLoading && <p className="p-4 text-texto-suave">Cargando...</p>}
            {lista.data?.length === 0 && (
              <div className="flex flex-col items-center gap-2 p-8 text-texto-suave">
                <IconBellOff size={26} />
                No tienes avisos todavía
              </div>
            )}
            {lista.data?.map((aviso) => (
              <button
                key={aviso.id}
                onClick={() => !aviso.leida && leerUna.mutate(aviso.id)}
                className={`flex w-full gap-3 border-b border-borde px-4 py-3 text-left last:border-b-0 hover:bg-fondo ${aviso.leida ? '' : 'bg-marca-suave/50'}`}
              >
                <span className={`mt-1.5 h-2 w-2 flex-none rounded-full ${aviso.leida ? 'bg-transparent' : 'bg-marca'}`} />
                <span>
                  <b className="block text-[13px]">{aviso.titulo}</b>
                  <span className="block text-xs text-texto-suave">{aviso.mensaje}</span>
                  <span className="mt-1 block text-[11px] text-texto-suave">{haceCuanto(aviso.created_at)}</span>
                </span>
              </button>
            ))}
          </div>
        </div>
      )}
    </div>
  )
}

export default Campanita
