import { useEffect, useMemo, useState, type ButtonHTMLAttributes, type InputHTMLAttributes, type ReactNode, type SelectHTMLAttributes } from 'react'
import { IconCircle, IconCircleCheck, IconEye, IconEyeOff, type Icon } from '@tabler/icons-react'
import { requisitosClave } from './validaciones'

// Piezas visuales reutilizables del diseño de DonaVida.

type Tono = 'exito' | 'urgente' | 'neutro' | 'aviso' | 'info'

const tonos: Record<Tono, string> = {
  exito: 'bg-exito-suave text-exito',
  urgente: 'bg-marca-suave text-marca',
  neutro: 'bg-fondo text-texto-suave',
  aviso: 'bg-aviso-suave text-aviso',
  info: 'bg-info-suave text-info',
}

export function Etiqueta({ tono = 'neutro', punto = true, children }: { tono?: Tono; punto?: boolean; children: ReactNode }) {
  return (
    <span className={`inline-flex items-center gap-1.5 whitespace-nowrap rounded-full px-2.5 py-0.5 text-[11px] font-semibold ${tonos[tono]}`}>
      {punto && <span className="h-1.5 w-1.5 rounded-full bg-current" />}
      {children}
    </span>
  )
}

export function Tarjeta({
  titulo,
  subtitulo,
  icono: IconoTitulo,
  extra,
  children,
  className = '',
}: {
  titulo?: string
  subtitulo?: string
  icono?: Icon
  extra?: ReactNode
  children: ReactNode
  className?: string
}) {
  return (
    <section className={`rounded-2xl border border-borde bg-white p-5 ${className}`}>
      {titulo && (
        <div className="mb-4 flex items-center justify-between gap-3">
          <div className="flex items-center gap-2">
            {IconoTitulo && <IconoTitulo size={20} className="text-marca" />}
            <div>
              <h3 className="text-base font-bold">{titulo}</h3>
              {subtitulo && <p className="text-xs text-texto-suave">{subtitulo}</p>}
            </div>
          </div>
          {extra && <div className="flex items-center gap-2">{extra}</div>}
        </div>
      )}
      {children}
    </section>
  )
}

export function Cifra({
  titulo,
  valor,
  detalle,
  icono: IconoCifra,
  color = '#e11d48',
  extra,
}: {
  titulo: string
  valor: ReactNode
  detalle?: string
  icono: Icon
  color?: string
  extra?: ReactNode
}) {
  return (
    <div className="rounded-2xl border border-borde bg-white p-5">
      <div className="flex items-start justify-between text-[13px] text-texto-suave">
        <span>{titulo}</span>
        <span className="grid h-9 w-9 place-items-center rounded-xl" style={{ background: `${color}1A`, color }}>
          <IconoCifra size={19} />
        </span>
      </div>
      <div className="mt-1 flex items-baseline gap-2.5 text-3xl font-bold">
        {valor}
        {extra}
      </div>
      {detalle && <p className="mt-1 text-xs text-texto-suave">{detalle}</p>}
    </div>
  )
}

type Variante = 'principal' | 'secundario' | 'exito' | 'borde'

const variantes: Record<Variante, string> = {
  principal: 'bg-marca text-white hover:bg-marca-oscuro',
  secundario: 'bg-fondo text-texto hover:bg-borde',
  exito: 'bg-exito-suave text-exito hover:bg-[#dcf5e6]',
  borde: 'border border-borde bg-white text-texto hover:bg-fondo',
}

export function Boton({
  variante = 'secundario',
  pequeno = false,
  className = '',
  ...props
}: ButtonHTMLAttributes<HTMLButtonElement> & { variante?: Variante; pequeno?: boolean }) {
  return (
    <button
      {...props}
      className={`inline-flex items-center justify-center gap-1.5 whitespace-nowrap rounded-xl font-semibold transition disabled:cursor-not-allowed disabled:opacity-50 ${
        pequeno ? 'px-3 py-1.5 text-xs' : 'px-4 py-2.5 text-[13px]'
      } ${variantes[variante]} ${className}`}
    />
  )
}

// Cuadro con el grupo sanguíneo: rojo fuerte para Rh negativo, rosa para Rh positivo
export function GrupoSangre({ codigo, tamano = 'md', redondo = false }: { codigo: string; tamano?: 'sm' | 'md' | 'lg'; redondo?: boolean }) {
  const negativo = codigo.includes('-') || codigo.includes('−')
  const medidas = { sm: 'h-8 w-10 text-xs', md: 'h-11 w-11 text-[15px]', lg: 'h-[72px] w-[72px] text-2xl' }[tamano]
  return (
    <span
      className={`grid flex-none place-items-center font-bold ${medidas} ${redondo ? 'rounded-full' : 'rounded-xl'} ${
        negativo ? 'bg-marca text-white' : 'bg-marca-suave text-marca'
      }`}
    >
      {codigo.replace('-', '−')}
    </span>
  )
}

// Texto de la etiqueta con asterisco rojo si el campo es obligatorio
function TextoEtiqueta({ etiqueta, obligatorio }: { etiqueta: string; obligatorio?: boolean }) {
  return (
    <span className="mb-1.5 block text-xs font-semibold text-texto">
      {etiqueta}
      {obligatorio && <span className="ml-0.5 text-marca">*</span>}
    </span>
  )
}

export function Campo({
  etiqueta,
  error,
  icono: IconoCampo,
  obligatorio,
  ayuda,
  ...props
}: InputHTMLAttributes<HTMLInputElement> & { etiqueta: string; error?: string; icono?: Icon; obligatorio?: boolean; ayuda?: string }) {
  return (
    <label className="block">
      <TextoEtiqueta etiqueta={etiqueta} obligatorio={obligatorio} />
      <span className="relative block">
        {IconoCampo && <IconoCampo size={17} className="absolute left-3.5 top-1/2 -translate-y-1/2 text-texto-suave" />}
        <input
          {...props}
          required={obligatorio || props.required}
          className={`h-11 w-full rounded-xl border bg-[#f7f7fb] pr-3.5 text-sm outline-none transition focus:border-marca focus:bg-white ${
            IconoCampo ? 'pl-10' : 'pl-3.5'
          } ${error ? 'border-marca' : 'border-borde'}`}
        />
      </span>
      {error ? <span className="mt-1 block text-xs text-marca">{error}</span> : ayuda && <span className="mt-1 block text-[11px] text-texto-suave">{ayuda}</span>}
    </label>
  )
}

// Campo de contraseña con el "ojito" para mostrarla u ocultarla
export function CampoClave({
  etiqueta,
  error,
  icono: IconoCampo,
  obligatorio,
  ...props
}: Omit<InputHTMLAttributes<HTMLInputElement>, 'type'> & { etiqueta: string; error?: string; icono?: Icon; obligatorio?: boolean }) {
  const [visible, setVisible] = useState(false)
  return (
    <label className="block">
      <TextoEtiqueta etiqueta={etiqueta} obligatorio={obligatorio} />
      <span className="relative block">
        {IconoCampo && <IconoCampo size={17} className="absolute left-3.5 top-1/2 -translate-y-1/2 text-texto-suave" />}
        <input
          {...props}
          type={visible ? 'text' : 'password'}
          required={obligatorio || props.required}
          className={`h-11 w-full rounded-xl border bg-[#f7f7fb] pr-11 text-sm outline-none transition focus:border-marca focus:bg-white ${
            IconoCampo ? 'pl-10' : 'pl-3.5'
          } ${error ? 'border-marca' : 'border-borde'}`}
        />
        <button
          type="button"
          onClick={() => setVisible(!visible)}
          className="absolute right-2 top-1/2 grid h-8 w-8 -translate-y-1/2 place-items-center rounded-lg text-texto-suave hover:bg-borde hover:text-texto"
          aria-label={visible ? 'Ocultar contraseña' : 'Mostrar contraseña'}
          title={visible ? 'Ocultar contraseña' : 'Mostrar contraseña'}
        >
          {visible ? <IconEyeOff size={18} /> : <IconEye size={18} />}
        </button>
      </span>
      {error && <span className="mt-1 block text-xs text-marca">{error}</span>}
    </label>
  )
}

// Lista de requisitos de la contraseña que se marcan en verde al cumplirse
export function RequisitosClave({ clave }: { clave: string }) {
  return (
    <ul className="mt-2 grid grid-cols-2 gap-x-3 gap-y-1 text-[11px]">
      {requisitosClave(clave).map((r) => (
        <li key={r.texto} className={`flex items-center gap-1 ${r.cumple ? 'text-exito' : 'text-texto-suave'}`}>
          {r.cumple ? <IconCircleCheck size={13} /> : <IconCircle size={13} />}
          {r.texto}
        </li>
      ))}
    </ul>
  )
}

// Botón para subir un archivo (foto o documento) con vista previa del nombre o la imagen
export function SubirArchivo({
  etiqueta,
  ayuda,
  archivo,
  alElegir,
  aceptar,
  error,
  icono: IconoArchivo,
  obligatorio,
}: {
  etiqueta: string
  ayuda: string
  archivo: File | null
  alElegir: (archivo: File | null) => void
  aceptar: string
  error?: string
  icono: Icon
  obligatorio?: boolean
}) {
  const vistaPrevia = useMemo(() => (archivo && archivo.type.startsWith('image/') ? URL.createObjectURL(archivo) : null), [archivo])
  useEffect(() => () => {
    if (vistaPrevia) URL.revokeObjectURL(vistaPrevia)
  }, [vistaPrevia])

  return (
    <div>
      <TextoEtiqueta etiqueta={etiqueta} obligatorio={obligatorio} />
      <label
        className={`flex cursor-pointer items-center gap-3 rounded-xl border border-dashed bg-[#f7f7fb] p-3 transition hover:border-marca ${
          error ? 'border-marca' : 'border-borde'
        }`}
      >
        {vistaPrevia ? (
          <img src={vistaPrevia} alt="" className="h-12 w-12 flex-none rounded-xl object-cover" />
        ) : (
          <span className="grid h-12 w-12 flex-none place-items-center rounded-xl bg-marca-suave text-marca">
            <IconoArchivo size={22} />
          </span>
        )}
        <span className="min-w-0 flex-1">
          <b className="block truncate text-[13px]">{archivo ? archivo.name : 'Elegir archivo'}</b>
          <small className="text-[11px] text-texto-suave">{ayuda}</small>
        </span>
        {archivo && (
          <button
            type="button"
            onClick={(e) => {
              e.preventDefault()
              alElegir(null)
            }}
            className="rounded-lg px-2 py-1 text-xs text-texto-suave hover:bg-borde"
          >
            Quitar
          </button>
        )}
        <input type="file" accept={aceptar} className="hidden" onChange={(e) => alElegir(e.target.files?.[0] ?? null)} />
      </label>
      {error && <span className="mt-1 block text-xs text-marca">{error}</span>}
    </div>
  )
}

export function Selector({
  etiqueta,
  error,
  obligatorio,
  children,
  ...props
}: SelectHTMLAttributes<HTMLSelectElement> & { etiqueta: string; error?: string; obligatorio?: boolean }) {
  return (
    <label className="block">
      <TextoEtiqueta etiqueta={etiqueta} obligatorio={obligatorio} />
      <select
        {...props}
        required={obligatorio || props.required}
        className={`h-11 w-full rounded-xl border bg-[#f7f7fb] px-3.5 text-sm outline-none focus:border-marca focus:bg-white ${
          error ? 'border-marca' : 'border-borde'
        }`}
      >
        {children}
      </select>
      {error && <span className="mt-1 block text-xs text-marca">{error}</span>}
    </label>
  )
}

// Botones tipo píldora para filtrar listas
export function Filtros<T extends string>({ opciones, valor, alCambiar }: { opciones: [T, string][]; valor: T; alCambiar: (valor: T) => void }) {
  return (
    <div className="flex flex-wrap gap-1.5">
      {opciones.map(([clave, texto]) => (
        <button
          key={clave}
          onClick={() => alCambiar(clave)}
          className={`rounded-full px-3 py-1.5 text-xs font-medium transition ${
            valor === clave ? 'bg-marca text-white' : 'bg-fondo text-texto-suave hover:bg-borde'
          }`}
        >
          {texto}
        </button>
      ))}
    </div>
  )
}

export function Cargando({ texto = 'Cargando...' }: { texto?: string }) {
  return (
    <div className="flex items-center gap-2 py-10 text-texto-suave">
      <span className="h-4 w-4 animate-spin rounded-full border-2 border-marca border-t-transparent" />
      {texto}
    </div>
  )
}

export function Vacio({ icono: IconoVacio, titulo, texto }: { icono: Icon; titulo: string; texto?: string }) {
  return (
    <div className="flex flex-col items-center py-10 text-center">
      <span className="mb-3 grid h-12 w-12 place-items-center rounded-full bg-fondo text-texto-suave">
        <IconoVacio size={24} />
      </span>
      <p className="font-semibold">{titulo}</p>
      {texto && <p className="mt-1 max-w-xs text-xs text-texto-suave">{texto}</p>}
    </div>
  )
}

export function MensajeError({ children }: { children: ReactNode }) {
  return <div className="rounded-xl bg-marca-suave px-4 py-3 text-[13px] text-marca-oscuro">{children}</div>
}

export function MensajeExito({ children }: { children: ReactNode }) {
  return <div className="rounded-xl bg-exito-suave px-4 py-3 text-[13px] text-exito">{children}</div>
}

// Ventana emergente sencilla (formularios de crear/editar)
export function Ventana({ titulo, alCerrar, children }: { titulo: string; alCerrar: () => void; children: ReactNode }) {
  return (
    <div className="fixed inset-0 z-[1000] grid place-items-center bg-black/40 p-4" onClick={alCerrar}>
      <div className="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl bg-white p-6" onClick={(e) => e.stopPropagation()}>
        <div className="mb-4 flex items-center justify-between">
          <h3 className="text-lg font-bold">{titulo}</h3>
          <button onClick={alCerrar} className="rounded-lg px-2 py-1 text-texto-suave hover:bg-fondo" aria-label="Cerrar">
            ✕
          </button>
        </div>
        {children}
      </div>
    </div>
  )
}

// Encabezado de tabla y filas con el estilo de las maquetas
export function Tabla({ columnas, children }: { columnas: string[]; children: ReactNode }) {
  return (
    <div className="overflow-x-auto">
      <table className="w-full border-collapse">
        <thead>
          <tr>
            {columnas.map((columna, indice) => (
              <th
                key={columna}
                className={`bg-fondo px-3.5 py-3 text-left text-[11px] font-semibold uppercase tracking-wide text-texto-suave ${
                  indice === 0 ? 'rounded-l-xl' : ''
                } ${indice === columnas.length - 1 ? 'rounded-r-xl text-right' : ''}`}
              >
                {columna}
              </th>
            ))}
          </tr>
        </thead>
        <tbody className="[&_td]:border-b [&_td]:border-borde [&_td]:px-3.5 [&_td]:py-3.5 [&_td]:text-[13px] [&_td:last-child]:text-right [&_tr:last-child_td]:border-b-0">
          {children}
        </tbody>
      </table>
    </div>
  )
}
