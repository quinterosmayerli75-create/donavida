import type { ReactNode } from 'react'
import {
  IconBuildingHospital,
  IconClock,
  IconDownload,
  IconHistory,
  IconMapPin,
  IconQrcode,
  IconSearch,
  IconShieldCheck,
  IconHeart,
  type Icon,
} from '@tabler/icons-react'
import { Boton, Etiqueta, Filtros, GrupoSangre, Tabla, Tarjeta } from './ui'

// Piezas visuales de las maquetas (docs/diseno/maquetas.html) que se repiten en varias pantallas.
// Reciben los datos por props: cada integrante les pasa los datos de la API.

// ---------- Piezas pequeñas ----------

// Fila de una lista con línea divisoria (como .lista>div en la maqueta)
export function Fila({ children, className = '' }: { children: ReactNode; className?: string }) {
  return <div className={`flex items-center gap-3.5 border-t border-borde py-3.5 first:border-t-0 first:pt-0 ${className}`}>{children}</div>
}

// Cuadro de color con un ícono o texto (como .g en la maqueta)
export function Cuadro({
  children,
  tono = 'suave',
  redondo = false,
  className = '',
}: {
  children: ReactNode
  tono?: 'suave' | 'solido' | 'neutro'
  redondo?: boolean
  className?: string
}) {
  const colores = { suave: 'bg-marca-suave text-marca', solido: 'bg-marca text-white', neutro: 'bg-fondo text-texto' }[tono]
  return (
    <span className={`grid h-11 w-11 flex-none place-items-center text-[15px] font-bold ${redondo ? 'rounded-full' : 'rounded-xl'} ${colores} ${className}`}>
      {children}
    </span>
  )
}

// Cuadro con el mes y el día (campañas)
export function CajaFecha({ mes, dia, tamano = 'md' }: { mes: string; dia: string; tamano?: 'md' | 'lg' }) {
  return (
    <span
      className={`flex flex-none flex-col items-center justify-center rounded-xl bg-marca-suave font-bold leading-tight text-marca ${
        tamano === 'lg' ? 'h-14 w-14 text-xl' : 'h-[50px] w-[50px] text-[15px]'
      }`}
    >
      <small className="text-[10px]">{mes}</small>
      {dia}
    </span>
  )
}

// Circulito con las iniciales de una persona
export function Iniciales({ nombre }: { nombre: string }) {
  const letras = nombre
    .replace(/^(Dr|Dra|Lic)\.\s*/, '')
    .split(' ')
    .filter(Boolean)
    .slice(0, 2)
    .map((parte) => parte[0].toUpperCase())
    .join('')
  return <span className="grid h-9 w-9 flex-none place-items-center rounded-full bg-fondo text-[13px] font-semibold">{letras}</span>
}

// Barra de progreso (verde por defecto)
export function Progreso({ porcentaje, color = 'bg-exito', ancho = 'w-[90px]' }: { porcentaje: number; color?: string; ancho?: string }) {
  return (
    <span className={`inline-block h-1.5 overflow-hidden rounded-full bg-fondo align-middle ${ancho}`}>
      <span className={`block h-full rounded-full ${color}`} style={{ width: `${Math.min(100, porcentaje)}%` }} />
    </span>
  )
}

// Interruptor de encendido / apagado
export function Interruptor({ encendido, alCambiar }: { encendido: boolean; alCambiar?: (valor: boolean) => void }) {
  return (
    <button
      type="button"
      onClick={() => alCambiar?.(!encendido)}
      className={`relative h-6 w-[42px] flex-none rounded-full transition ${encendido ? 'bg-exito' : 'bg-gray-300'}`}
      aria-pressed={encendido}
    >
      <span className={`absolute top-[3px] h-[18px] w-[18px] rounded-full bg-white transition-all ${encendido ? 'right-[3px]' : 'left-[3px]'}`} />
    </button>
  )
}

// Grupo sanguíneo pequeño dentro de una tabla
export function GrupoTabla({ codigo }: { codigo: string }) {
  return <GrupoSangre codigo={codigo} tamano="sm" />
}

// Línea con ícono pequeño y texto gris (dirección, hora, hospital...)
export function Detalle({ icono: IconoDetalle, children }: { icono: Icon; children: ReactNode }) {
  return (
    <small className="mt-1 flex items-center gap-1.5 text-xs text-texto-suave">
      <IconoDetalle size={14} />
      {children}
    </small>
  )
}

// ---------- Selector de grupo sanguíneo (Hospital › Resumen) ----------

export interface GrupoDisponible {
  codigo: string
  disponibles: number
  estado: 'Estable' | 'Crítico' | 'Bajo' | 'Normal' | 'Escaso'
}

export function SelectorGrupos({ grupos, elegido, alElegir }: { grupos: GrupoDisponible[]; elegido: string; alElegir: (codigo: string) => void }) {
  return (
    <div className="grid grid-cols-4 gap-2 sm:grid-cols-8">
      {grupos.map((g) => {
        const activo = g.codigo === elegido
        const critico = g.estado === 'Crítico'
        return (
          <button
            key={g.codigo}
            onClick={() => alElegir(g.codigo)}
            className={`rounded-xl px-1 py-2.5 text-center transition ${
              activo ? 'bg-marca text-white' : critico ? 'bg-marca-suave text-marca' : 'bg-fondo hover:bg-borde'
            }`}
          >
            <b className="block text-base">{g.codigo.replace('-', '−')}</b>
            <span className={`text-[11px] ${activo ? 'text-white' : critico ? 'text-marca' : 'text-texto-suave'}`}>{g.disponibles} disp.</span>
          </button>
        )
      })}
    </div>
  )
}

// ---------- Radar de donantes cerca (Hospital › Resumen y Donantes) ----------

export interface PuntoRadar {
  x: number
  y: number
  grupo: string
  urgente: boolean
  apto?: boolean
}

export function RadarDonantes({ puntos }: { puntos: PuntoRadar[] }) {
  return (
    <svg viewBox="0 0 400 260" className="w-full rounded-2xl bg-fondo">
      <circle cx="200" cy="130" r="110" fill="none" stroke="#E5E7EB" />
      <circle cx="200" cy="130" r="72" fill="none" stroke="#E5E7EB" />
      <circle cx="200" cy="130" r="36" fill="#FDEEF1" stroke="#F9D3DA" />
      <line x1="200" y1="10" x2="200" y2="250" stroke="#E5E7EB" />
      <line x1="60" y1="130" x2="340" y2="130" stroke="#E5E7EB" />
      <text x="232" y="98" fontSize="10" fill="#9CA3AF">2 km</text>
      <text x="262" y="66" fontSize="10" fill="#9CA3AF">5 km</text>
      <text x="296" y="34" fontSize="10" fill="#9CA3AF">10 km</text>
      <circle cx="200" cy="130" r="16" fill="#E11D48" />
      <text x="200" y="135" fontSize="14" fill="#fff" textAnchor="middle" fontWeight="700">+</text>
      {puntos.map((p, i) => {
        const color = p.urgente ? '#E11D48' : p.apto === false ? '#64748B' : '#16A34A'
        return (
          <g key={i}>
            <circle cx={p.x} cy={p.y} r="13" fill="#fff" stroke={color} strokeWidth="2" />
            <text x={p.x} y={p.y + 4} fontSize="9" fill={color} textAnchor="middle" fontWeight="700">
              {p.grupo.replace('-', '−')}
            </text>
          </g>
        )
      })}
      <text x="16" y="24" fontSize="11" fill="#6B7280">Tiquipaya</text>
      <text x="384" y="24" fontSize="11" fill="#6B7280" textAnchor="end">Sacaba</text>
      <text x="16" y="248" fontSize="11" fill="#6B7280">Quillacollo</text>
      <text x="384" y="248" fontSize="11" fill="#6B7280" textAnchor="end">Cercado Sur</text>
    </svg>
  )
}

// ---------- Tabla de citas del hospital (Resumen y Citas y donaciones) ----------

export type EstadoCitaHospital = 'En camino' | 'En triaje' | 'Donó' | 'Sin respuesta'

export interface FilaCitaHospital {
  paciente: string
  servicio: string
  grupo: string
  donante: string
  detalleDonante: string
  hora: string
  estado: EstadoCitaHospital
}

const estiloCita: Record<EstadoCitaHospital, { tono: 'info' | 'aviso' | 'exito' | 'urgente'; accion: string; variante: 'principal' | 'exito' | 'secundario' | 'borde' }> = {
  'En camino': { tono: 'info', accion: 'Confirmar llegada', variante: 'principal' },
  'En triaje': { tono: 'aviso', accion: 'Registrar donación', variante: 'exito' },
  Donó: { tono: 'exito', accion: 'Ver ficha', variante: 'secundario' },
  'Sin respuesta': { tono: 'urgente', accion: 'Avisar de nuevo', variante: 'borde' },
}

export function TablaCitasHospital({
  titulo,
  filas,
  filtro,
  alFiltrar,
}: {
  titulo: string
  filas: FilaCitaHospital[]
  filtro: string
  alFiltrar: (valor: string) => void
}) {
  const visibles = filtro === 'todos' ? filas : filas.filter((f) => f.estado === filtro)
  return (
    <Tarjeta
      titulo={titulo}
      subtitulo="Del aviso a la donación"
      icono={IconHistory}
      extra={
        <Filtros
          opciones={[
            ['todos', 'Todos'],
            ['En camino', 'En camino'],
            ['En triaje', 'En triaje'],
            ['Donó', 'Donaron'],
          ]}
          valor={filtro}
          alCambiar={alFiltrar}
        />
      }
    >
      <Tabla columnas={['Paciente', 'Grupo', 'Donante', 'Hora', 'Estado', 'Acción']}>
        {visibles.map((f) => {
          const estilo = estiloCita[f.estado]
          return (
            <tr key={f.paciente}>
              <td>
                <b>{f.paciente}</b>
                <small className="mt-0.5 block text-xs text-texto-suave">{f.servicio}</small>
              </td>
              <td>
                <GrupoTabla codigo={f.grupo} />
              </td>
              <td>
                <b>{f.donante}</b>
                <small className="mt-0.5 block text-xs text-texto-suave">{f.detalleDonante}</small>
              </td>
              <td>{f.hora}</td>
              <td>
                <Etiqueta tono={estilo.tono}>{f.estado}</Etiqueta>
              </td>
              <td>
                <Boton variante={estilo.variante} pequeno>
                  {estilo.accion}
                </Boton>
              </td>
            </tr>
          )
        })}
      </Tabla>
    </Tarjeta>
  )
}

// ---------- Tarjetas de campañas (Administrador, Hospital y Donante) ----------

export interface CampanaTarjeta {
  mes: string
  dia: string
  nombre: string
  lugar: string
  hospital: string
  horario: string
  inscritos: number
  cupos: number
}

export function TarjetasCampanas({ campanas, modo }: { campanas: CampanaTarjeta[]; modo: 'administrador' | 'hospital' | 'donante' }) {
  return (
    <div className="grid grid-cols-1 gap-[18px] md:grid-cols-2 xl:grid-cols-3">
      {campanas.map((c) => (
        <div key={c.nombre} className="rounded-2xl border border-borde bg-white p-5">
          <div className="flex items-start justify-between">
            <CajaFecha mes={c.mes} dia={c.dia} tamano="lg" />
            {modo === 'donante' || modo === 'hospital' ? (
              <Etiqueta tono="exito">{c.cupos - c.inscritos} cupos</Etiqueta>
            ) : (
              <Etiqueta tono="exito">Aprobada</Etiqueta>
            )}
          </div>
          <h3 className="mt-3.5 text-base font-bold">{c.nombre}</h3>
          <Detalle icono={IconMapPin}>{c.lugar}</Detalle>
          <Detalle icono={IconClock}>{c.horario}</Detalle>
          <Detalle icono={IconBuildingHospital}>{c.hospital}</Detalle>
          <div className="mb-1.5 mt-3.5 flex justify-between text-xs text-texto-suave">
            <span>{c.inscritos} inscritos</span>
            <span>{c.cupos} cupos</span>
          </div>
          <Progreso porcentaje={(c.inscritos / c.cupos) * 100} color="bg-marca" ancho="w-full" />
          <Boton variante={modo === 'donante' ? 'principal' : 'secundario'} className="mt-3.5 w-full">
            {modo === 'donante' ? 'Inscribirme' : modo === 'administrador' ? 'Revisar' : 'Ver inscritos'}
          </Boton>
        </div>
      ))}
    </div>
  )
}

// ---------- Historial de donaciones (Donante › Inicio e Historial) ----------

export interface FilaHistorial {
  fecha: string
  hospital: string
  servicio: string
  tipo: 'Sangre total' | 'Plaquetas'
  cantidad: string
  atendio: string
}

export function TablaHistorial({ filas, totalMl }: { filas: FilaHistorial[]; totalMl: string }) {
  return (
    <Tarjeta titulo="Historial de donaciones" subtitulo="Registradas por cada hospital" icono={IconHistory} extra={<Etiqueta punto={false}>Total: {totalMl}</Etiqueta>}>
      <Tabla columnas={['Fecha', 'Hospital', 'Tipo', 'Atendió', 'Certificado']}>
        {filas.map((f) => (
          <tr key={f.fecha + f.hospital}>
            <td>{f.fecha}</td>
            <td>
              <b>{f.hospital}</b>
              <small className="mt-0.5 block text-xs text-texto-suave">{f.servicio}</small>
            </td>
            <td>
              <span className="inline-flex items-center gap-1.5">
                <Etiqueta tono={f.tipo === 'Plaquetas' ? 'info' : 'urgente'}>{f.tipo}</Etiqueta>
                {f.cantidad}
              </span>
            </td>
            <td>{f.atendio}</td>
            <td>
              <a className="inline-flex cursor-pointer items-center gap-1 text-xs font-semibold text-marca">
                <IconDownload size={14} /> PDF
              </a>
            </td>
          </tr>
        ))}
      </Tabla>
    </Tarjeta>
  )
}

// ---------- Carnet de donante (Donante › Inicio) ----------

export function CarnetDonante({
  titular,
  grupo,
  anio,
  vidas,
  donaciones,
}: {
  titular: string
  grupo: string
  anio: number
  vidas: number
  donaciones: number
}) {
  return (
    <div className="relative overflow-hidden rounded-[20px] bg-gradient-to-br from-marca-oscuro to-marca p-[26px] text-white">
      <div className="absolute -bottom-[110px] -right-20 h-[260px] w-[260px] rounded-full bg-white/[0.07]" />
      <div className="relative flex justify-between text-xs font-semibold tracking-wider">
        <span className="flex items-center gap-1.5">
          <IconShieldCheck size={16} /> CARNET DE DONANTE
        </span>
        <span className="rounded-full bg-white/20 px-2.5 py-0.5">{anio}</span>
      </div>
      <div className="relative mt-8 flex items-end justify-between">
        <div>
          <small className="text-xs opacity-80">Titular</small>
          <div className="text-[26px] font-bold">{titular}</div>
          <div className="opacity-90">{grupo}</div>
        </div>
        <span className="grid h-24 w-24 place-items-center rounded-xl bg-white p-2.5">
          <IconQrcode size={72} className="text-texto" />
        </span>
      </div>
      <div className="relative mt-[22px] flex justify-between border-t border-white/25 pt-3.5 text-[13px]">
        <span className="flex items-center gap-1.5">
          <IconHeart size={16} /> {vidas} vidas ayudadas
        </span>
        <span>{donaciones} donaciones</span>
      </div>
    </div>
  )
}

// ---------- Barra de filtros: buscador + selectores + botón (listas del administrador y de los demás roles) ----------

export function BarraFiltros({
  buscar,
  alBuscar,
  textoBuscar,
  children,
}: {
  buscar: string
  alBuscar: (texto: string) => void
  textoBuscar: string
  children?: ReactNode
}) {
  return (
    <div className="mb-4 flex flex-wrap items-center gap-2.5">
      <label className="relative min-w-[220px] flex-1">
        <IconSearch size={17} className="absolute left-3.5 top-1/2 -translate-y-1/2 text-texto-suave" />
        <input
          value={buscar}
          onChange={(e) => alBuscar(e.target.value)}
          placeholder={textoBuscar}
          className="h-10 w-full rounded-xl border border-borde bg-[#f7f7fb] pl-10 pr-3.5 text-sm outline-none transition focus:border-marca focus:bg-white"
        />
      </label>
      {children}
    </div>
  )
}

export function SelectorFiltro({ valor, alCambiar, children }: { valor: string; alCambiar: (valor: string) => void; children: ReactNode }) {
  return (
    <select
      value={valor}
      onChange={(e) => alCambiar(e.target.value)}
      className="h-10 rounded-xl border border-borde bg-[#f7f7fb] px-3 text-[13px] outline-none focus:border-marca"
    >
      {children}
    </select>
  )
}
