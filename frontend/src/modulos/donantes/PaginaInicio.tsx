import { IconBuildingHospital, IconCalendarEvent, IconCalendarRepeat, IconCircleCheck, IconClock, IconHeartHandshake, IconMapPin, IconNavigation } from '@tabler/icons-react'
import { historialEjemplo } from '../../compartido/datosEjemplo'
import { CarnetDonante, TablaHistorial } from '../../compartido/maqueta'
import { Boton, Etiqueta, GrupoSangre, Tarjeta } from '../../compartido/ui'

// Donante › Inicio (diseño de la maqueta).
// DATOS DE EJEMPLO: conectar con GET /donante/perfil, /donante/puedo-donar, /donante/solicitudes,
// /donante/citas, /donante/campanas y /donante/historial (funciones listas en ./api.ts y ./apiPortal.ts).
const ejemplo = {
  nombre: 'Alejandro Morales Villarroel',
  grupo: 'O-',
  zona: 'Cala Cala, Cercado',
  ultimaDonacion: 'Última donación hace 112 días',
  carnet: { titular: 'A. Morales V.', grupo: 'O Rh(−) · Universal', vidas: 9, donaciones: 3 },
  cita: { cuando: 'Hoy, 16:30', turno: 'Turno #14', hospital: 'Hospital Clínico Viedma', lugar: 'Unidad de Hemoterapia · Bloque Norte P1' },
  urgente: { hospital: 'Hospital Clínico Viedma', motivo: 'Traumatología · paciente en cirugía', distancia: 'a 3.1 km', unidades: 2, grupo: 'O−' },
  puntos: [
    { distancia: '1.8 km', cupos: '4 cupos', nombre: 'Banco Regional de Sangre', direccion: 'Av. Juan de la Rosa', horario: 'Abierto hasta 19:00' },
    { distancia: '2.4 km', cupos: '12 cupos', nombre: 'Hemobús Plaza Colón', direccion: 'Campaña móvil', horario: 'Hasta 18:00' },
  ],
}

function PaginaInicio() {
  return (
    <div className="space-y-[18px]">
      <Tarjeta>
        <div className="flex flex-wrap items-center gap-[22px]">
          <GrupoSangre codigo={ejemplo.grupo} tamano="lg" />
          <div className="min-w-0 flex-1">
            <h2 className="text-[22px] font-bold">{ejemplo.nombre}</h2>
            <div className="mt-1.5 flex flex-wrap items-center gap-2.5">
              <Etiqueta tono="urgente">Donante universal</Etiqueta>
              <small className="flex items-center gap-1 text-xs text-texto-suave">
                <IconMapPin size={14} />
                {ejemplo.zona}
              </small>
            </div>
          </div>
          <div className="flex items-center gap-3 rounded-2xl bg-exito-suave px-[18px] py-3.5">
            <IconCircleCheck size={28} className="text-exito" />
            <div>
              <b className="text-exito">Puedes donar</b>
              <small className="block text-xs text-texto-suave">{ejemplo.ultimaDonacion}</small>
            </div>
          </div>
        </div>
      </Tarjeta>

      <div className="grid grid-cols-1 gap-[18px] lg:grid-cols-[1fr_1.2fr]">
        <div className="grid content-start gap-[18px]">
          <CarnetDonante {...ejemplo.carnet} anio={new Date().getFullYear()} />

          <Tarjeta titulo="Tu próxima cita" icono={IconCalendarEvent} extra={<Etiqueta tono="exito">Confirmada</Etiqueta>}>
            <div className="rounded-2xl bg-fondo p-4">
              <div className="flex justify-between">
                <b className="text-[22px]">{ejemplo.cita.cuando}</b>
                <small className="text-xs text-texto-suave">{ejemplo.cita.turno}</small>
              </div>
              <div className="mt-1.5 font-semibold">{ejemplo.cita.hospital}</div>
              <small className="text-xs text-texto-suave">{ejemplo.cita.lugar}</small>
            </div>
            <div className="mt-3 grid grid-cols-2 gap-2.5">
              <Boton>
                <IconNavigation size={16} />
                Ver ruta
              </Boton>
              <Boton>
                <IconCalendarRepeat size={16} />
                Reprogramar
              </Boton>
            </div>
          </Tarjeta>
        </div>

        <div className="grid content-start gap-[18px]">
          <div className="rounded-r-2xl border border-l-4 border-borde border-l-marca bg-white p-[22px]">
            <div className="flex items-center justify-between">
              <Etiqueta tono="urgente">Urgente</Etiqueta>
              <Etiqueta punto={false}>
                <IconNavigation size={12} /> {ejemplo.urgente.distancia}
              </Etiqueta>
            </div>
            <div className="mt-3 flex items-center justify-between gap-3">
              <div>
                <h2 className="text-[22px] font-bold">{ejemplo.urgente.hospital}</h2>
                <small className="text-xs text-texto-suave">{ejemplo.urgente.motivo}</small>
              </div>
              <div className="rounded-xl bg-marca-suave px-3.5 py-2 text-center text-marca">
                <b className="text-[22px]">{ejemplo.urgente.unidades}</b>
                <small className="block text-[11px]">unidades {ejemplo.urgente.grupo}</small>
              </div>
            </div>
            <p className="mb-4 mt-3 text-[13px] leading-relaxed text-texto-suave">Tu sangre es compatible. Si aceptas, te damos un turno sin espera.</p>
            <div className="flex gap-2.5">
              <Boton variante="principal" className="flex-1 py-3">
                <IconHeartHandshake size={17} />
                Aceptar y agendar
              </Boton>
              <Boton>Ver detalle</Boton>
            </div>
          </div>

          <Tarjeta titulo="Puntos de donación cerca" subtitulo="Hoy" icono={IconBuildingHospital}>
            <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
              {ejemplo.puntos.map((p) => (
                <div key={p.nombre} className="rounded-2xl bg-fondo p-3.5">
                  <div className="flex justify-between text-xs">
                    <span className="rounded-full bg-white px-2.5 py-0.5 font-semibold text-texto-suave">{p.distancia}</span>
                    <span className="font-semibold text-exito">{p.cupos}</span>
                  </div>
                  <b className="mt-2.5 block">{p.nombre}</b>
                  <small className="block text-xs text-texto-suave">{p.direccion}</small>
                  <small className="mb-3 mt-2 flex items-center gap-1 text-xs text-texto-suave">
                    <IconClock size={14} />
                    {p.horario}
                  </small>
                  <Boton variante="borde" pequeno className="w-full">
                    Agendar turno
                  </Boton>
                </div>
              ))}
            </div>
          </Tarjeta>
        </div>
      </div>

      <TablaHistorial filas={historialEjemplo} totalMl="1.350 ml" />
    </div>
  )
}

export default PaginaInicio
