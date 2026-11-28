import { IconCalendarCheck, IconDroplet, IconHeart } from '@tabler/icons-react'
import { historialEjemplo } from '../../compartido/datosEjemplo'
import { TablaHistorial } from '../../compartido/maqueta'
import { Cifra } from '../../compartido/ui'

// Donante › Historial (diseño de la maqueta).
// DATOS DE EJEMPLO: Maya conecta aquí GET /donante/historial y GET /donante/puedo-donar
// (su lógica ya existe en ./api.ts y ./componentes/HistorialDonaciones.tsx).
function PaginaHistorial() {
  return (
    <div className="space-y-[18px]">
      <div className="grid grid-cols-1 gap-[18px] sm:grid-cols-3">
        <Cifra titulo="Donaciones" valor="3" detalle="Desde 2024" icono={IconDroplet} />
        <Cifra titulo="Total donado" valor="1.350 ml" detalle="Sangre y plaquetas" icono={IconHeart} />
        <Cifra titulo="Próxima fecha" valor="Ya puedes" detalle="Pasaron más de 90 días" icono={IconCalendarCheck} color="#16A34A" />
      </div>
      <TablaHistorial filas={historialEjemplo} totalMl="1.350 ml" />
    </div>
  )
}

export default PaginaHistorial
