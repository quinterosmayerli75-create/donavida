import { useState } from 'react'
import { IconArmchair, IconCalendarEvent, IconCircleCheck, IconNavigation } from '@tabler/icons-react'
import { citasHospitalEjemplo } from '../../compartido/datosEjemplo'
import { TablaCitasHospital } from '../../compartido/maqueta'
import { Cifra } from '../../compartido/ui'

// Hospital › Citas y donaciones (diseño de la maqueta).
// DATOS DE EJEMPLO: conectar con GET /hospital/citas, PATCH /hospital/citas/{id}/asistencia,
// POST /hospital/citas/{id}/donacion y GET /hospital/donaciones (obtenerCitasHospital ya está en ./api.ts).
function PaginaCitas() {
  const [filtro, setFiltro] = useState('todos')

  return (
    <div className="space-y-[18px]">
      <div className="grid grid-cols-1 gap-[18px] sm:grid-cols-2 xl:grid-cols-4">
        <Cifra titulo="Agendadas hoy" valor="12" detalle="Citas confirmadas" icono={IconCalendarEvent} color="#2563EB" />
        <Cifra titulo="En camino" valor="2" detalle="Donantes llegando" icono={IconNavigation} color="#B45309" />
        <Cifra titulo="En sala" valor="2" detalle="Esperando extracción" icono={IconArmchair} />
        <Cifra titulo="Donaron" valor="8" detalle="Registradas hoy" icono={IconCircleCheck} color="#16A34A" />
      </div>
      <TablaCitasHospital titulo="Citas de hoy" filas={citasHospitalEjemplo} filtro={filtro} alFiltrar={setFiltro} />
    </div>
  )
}

export default PaginaCitas
