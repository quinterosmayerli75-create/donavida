import { IconCalendar, IconCalendarEvent, IconClock, IconHistory, IconId, IconToolsKitchen2 } from '@tabler/icons-react'
import { Cuadro, Fila } from '../../compartido/maqueta'
import { Boton, Etiqueta, Tarjeta } from '../../compartido/ui'

// Donante › Mis citas (diseño de la maqueta).
// DATOS DE EJEMPLO: conectar con GET /donante/citas y "Cancelar" con PATCH /donante/citas/{id}/cancelar
// (obtenerCitasDonante ya está en ./apiPortal.ts).
const ejemplo = {
  proxima: { cuando: 'Hoy, 16:30', hospital: 'Hospital Clínico Viedma', lugar: 'Unidad de Hemoterapia · Bloque Norte P1 · Turno #14' },
  anteriores: [
    { fecha: '14 nov 2024', hospital: 'Hospital Clínico Viedma', estado: 'Donó' },
    { fecha: '10 may 2024', hospital: 'Banco Regional de Sangre', estado: 'Donó' },
    { fecha: '02 mar 2024', hospital: 'Hospital del Norte', estado: 'No asistió' },
  ],
}

const consejos = [
  { icono: IconClock, texto: 'Llega 15 minutos antes' },
  { icono: IconId, texto: 'Lleva tu carnet de identidad' },
  { icono: IconToolsKitchen2, texto: 'Come algo liviano antes' },
]

function PaginaMisCitas() {
  return (
    <div className="grid grid-cols-1 gap-[18px] lg:grid-cols-2">
      <Tarjeta titulo="Próxima cita" icono={IconCalendarEvent} extra={<Etiqueta tono="exito">Confirmada</Etiqueta>}>
        <div className="rounded-2xl bg-fondo p-[18px]">
          <b className="text-2xl">{ejemplo.proxima.cuando}</b>
          <div className="mt-1.5 font-semibold">{ejemplo.proxima.hospital}</div>
          <small className="text-xs text-texto-suave">{ejemplo.proxima.lugar}</small>
        </div>
        <div className="mt-3.5">
          {consejos.map(({ icono: IconoConsejo, texto }) => (
            <Fila key={texto}>
              <Cuadro redondo className="h-[34px] w-[34px]">
                <IconoConsejo size={17} />
              </Cuadro>
              <b className="text-[13px] font-medium">{texto}</b>
            </Fila>
          ))}
        </div>
        <div className="mt-3 grid grid-cols-3 gap-2.5">
          <Boton>Ver ruta</Boton>
          <Boton>Reprogramar</Boton>
          <Boton variante="borde" className="text-marca">
            Cancelar
          </Boton>
        </div>
      </Tarjeta>

      <Tarjeta titulo="Citas anteriores" icono={IconHistory}>
        {ejemplo.anteriores.map((c) => (
          <Fila key={c.fecha}>
            <Cuadro tono="neutro" redondo>
              <IconCalendar size={19} />
            </Cuadro>
            <div className="min-w-0 flex-1">
              <b className="block">{c.hospital}</b>
              <small className="mt-0.5 block text-xs text-texto-suave">{c.fecha}</small>
            </div>
            {c.estado === 'Donó' ? <Etiqueta tono="exito">Donó</Etiqueta> : <Etiqueta>{c.estado}</Etiqueta>}
          </Fila>
        ))}
      </Tarjeta>
    </div>
  )
}

export default PaginaMisCitas
