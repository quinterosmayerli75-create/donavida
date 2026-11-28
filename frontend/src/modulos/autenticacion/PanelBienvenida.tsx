import type { ReactNode } from 'react'
import { IconDroplet } from '@tabler/icons-react'

// Módulo 1 (Mayerli): panel rojo de la izquierda que comparten el login y el registro.
function PanelBienvenida({ titulo, texto, children }: { titulo: ReactNode; texto: string; children?: ReactNode }) {
  return (
    <div className="relative hidden flex-[1.05] flex-col overflow-hidden bg-gradient-to-br from-[#9f0f28] to-marca px-12 py-11 text-white lg:flex">
      <span className="absolute -right-36 -top-28 h-[460px] w-[460px] rounded-full bg-white/[0.06]" />
      <span className="absolute -bottom-32 -left-24 h-80 w-80 rounded-full bg-white/[0.05]" />

      <div className="relative flex items-center gap-2.5">
        <span className="grid h-10 w-10 place-items-center rounded-xl bg-white text-marca">
          <IconDroplet size={22} />
        </span>
        <div>
          <b className="block text-xl">DonaVida</b>
          <small className="text-xs text-white/80">Red de donantes · Cochabamba</small>
        </div>
      </div>

      <div className="relative my-auto py-10">
        <span className="mb-4 inline-flex items-center gap-1.5 rounded-full bg-white/15 px-3 py-1 text-[11px] font-semibold tracking-wide">
          <span className="h-1.5 w-1.5 rounded-full bg-[#86efac]" />
          COCHABAMBA ACTIVA
        </span>
        <h1 className="text-[40px] font-bold leading-tight">{titulo}</h1>
        <p className="mt-3 max-w-md text-[15px] leading-relaxed text-white/85">{texto}</p>
        {children && <div className="mt-8">{children}</div>}
      </div>

      <p className="relative text-xs text-white/70">Con el respaldo de SEDES Cochabamba</p>
    </div>
  )
}

export default PanelBienvenida
