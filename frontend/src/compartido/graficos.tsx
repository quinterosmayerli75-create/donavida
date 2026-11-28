// Gráficos sencillos que comparten varias pantallas.

// Barras de donaciones por grupo sanguíneo (rojo fuerte = Rh negativo)
export function BarrasGrupo({ datos }: { datos: { tipo_sangre: string; total: number }[] }) {
  const maximo = Math.max(1, ...datos.map((d) => d.total))
  return (
    <div>
      <div className="flex h-48 items-end gap-3 border-b border-borde px-1.5">
        {datos.map((d) => (
          <div key={d.tipo_sangre} className="flex flex-1 flex-col items-center justify-end">
            <span className="mb-1 text-[11px] font-semibold">{d.total}</span>
            <div
              className={`w-full rounded-t-lg ${d.tipo_sangre.includes('-') ? 'bg-marca' : 'bg-[#FBC6D0]'}`}
              style={{ height: `${Math.max(2, (d.total / maximo) * 150)}px` }}
            />
          </div>
        ))}
      </div>
      <div className="flex gap-3 px-1.5 pt-1.5">
        {datos.map((d) => (
          <span key={d.tipo_sangre} className="flex-1 text-center text-xs text-texto-suave">
            {d.tipo_sangre.replace('-', '−')}
          </span>
        ))}
      </div>
      <div className="mt-3 flex gap-4 text-xs text-texto-suave">
        <span className="flex items-center gap-1.5">
          <i className="h-2.5 w-2.5 rounded-sm bg-marca" /> Rh negativo
        </span>
        <span className="flex items-center gap-1.5">
          <i className="h-2.5 w-2.5 rounded-sm bg-[#FBC6D0]" /> Rh positivo
        </span>
      </div>
    </div>
  )
}
