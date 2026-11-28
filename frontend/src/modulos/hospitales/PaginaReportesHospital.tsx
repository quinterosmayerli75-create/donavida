import { IconAlertTriangle, IconChartBar, IconChartPie, IconCircleCheck, IconClock, IconDownload, IconDroplet, IconUserPlus } from '@tabler/icons-react'
import { necesidadPorGrupoEjemplo } from '../../compartido/datosEjemplo'
import { BarrasGrupo } from '../../compartido/graficos'
import { Fila } from '../../compartido/maqueta'
import { Boton, Cifra, Etiqueta, Tarjeta } from '../../compartido/ui'

// Hospital › Reportes (diseño de la maqueta).
// DATOS DE EJEMPLO: conectar con GET /hospital/indicadores (obtenerIndicadoresHospital ya está en ./api.ts).
const porEstado = [
  ['Completadas', 15, '#16A34A'],
  ['En curso', 4, '#2563EB'],
  ['Urgentes sin cubrir', 2, '#E11D48'],
  ['Canceladas', 1, '#9CA3AF'],
] as const

function PaginaReportesHospital() {
  return (
    <div className="space-y-[18px]">
      <div className="grid grid-cols-1 gap-[18px] sm:grid-cols-2 xl:grid-cols-4">
        <Cifra titulo="Solicitudes del mes" valor="18" detalle="Publicadas" icono={IconDroplet} />
        <Cifra titulo="Atendidas" valor="15" extra={<Etiqueta tono="exito">83%</Etiqueta>} detalle="Con todas sus unidades" icono={IconCircleCheck} color="#16A34A" />
        <Cifra titulo="Tiempo de respuesta" valor="42 min" detalle="Del aviso a la cita" icono={IconClock} color="#2563EB" />
        <Cifra titulo="Donantes nuevos" valor="42" detalle="Este mes" icono={IconUserPlus} />
      </div>

      <div className="grid grid-cols-1 gap-[18px] lg:grid-cols-[1.3fr_1fr]">
        <Tarjeta
          titulo="Necesidad por grupo sanguíneo"
          subtitulo="Unidades pedidas este mes"
          icono={IconChartBar}
          extra={
            <Boton variante="borde" pequeno>
              <IconDownload size={14} />
              Exportar
            </Boton>
          }
        >
          <BarrasGrupo datos={necesidadPorGrupoEjemplo} />
          <div className="mt-[18px] flex items-center gap-2.5 rounded-xl bg-marca-suave px-3.5 py-3 text-[13px] text-marca-oscuro">
            <IconAlertTriangle size={18} />
            <span>
              <b>Faltan O− y B−.</b> Conviene hacer una campaña.
            </span>
          </div>
        </Tarjeta>

        <Tarjeta titulo="Solicitudes por estado" icono={IconChartPie}>
          {porEstado.map(([texto, total, color]) => (
            <Fila key={texto}>
              <span className="h-3 w-3 rounded" style={{ background: color }} />
              <span className="flex-1 font-medium">{texto}</span>
              <b>{total}</b>
            </Fila>
          ))}
        </Tarjeta>
      </div>
    </div>
  )
}

export default PaginaReportesHospital
