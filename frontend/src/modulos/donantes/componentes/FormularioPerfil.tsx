import { useState, type FormEvent } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import {
  actualizarPerfil,
  erroresDeValidacion,
  mensajeDeError,
  obtenerTiposSangre,
  type CambiosPerfil,
  type PerfilDonante,
} from '../api'

// Formulario para editar los datos del donante.
// Los errores que devuelve Laravel (422) se muestran debajo de cada campo.

interface Formulario {
  nombre: string
  apellidos: string
  telefono: string
  tipo_sangre_id: number
  fecha_nacimiento: string
  sexo: 'F' | 'M'
  peso_kg: string
  zona: string
  direccion: string
  disponible: boolean
}

function desdePerfil(perfil: PerfilDonante): Formulario {
  return {
    nombre: perfil.nombre,
    apellidos: perfil.apellidos,
    telefono: perfil.telefono,
    tipo_sangre_id: perfil.tipo_sangre.id,
    fecha_nacimiento: perfil.fecha_nacimiento,
    sexo: perfil.sexo,
    peso_kg: perfil.peso_kg === null ? '' : String(perfil.peso_kg),
    zona: perfil.zona ?? '',
    direccion: perfil.direccion ?? '',
    disponible: perfil.disponible,
  }
}

function FormularioPerfil({ perfil }: { perfil: PerfilDonante }) {
  const clienteConsultas = useQueryClient()
  const [datos, setDatos] = useState<Formulario>(() => desdePerfil(perfil))
  const tipos = useQuery({ queryKey: ['tipos-sangre'], queryFn: obtenerTiposSangre })

  const guardar = useMutation({
    mutationFn: (cambios: CambiosPerfil) => actualizarPerfil(cambios),
    onSuccess: (perfilActualizado) => {
      clienteConsultas.setQueryData(['donante', 'perfil'], perfilActualizado)
      // la disponibilidad o la fecha pueden cambiar el resultado de "¿puedo donar?"
      clienteConsultas.invalidateQueries({ queryKey: ['donante', 'puedo-donar'] })
      setDatos(desdePerfil(perfilActualizado))
    },
  })

  const errores = erroresDeValidacion(guardar.error)

  function cambiar<Campo extends keyof Formulario>(campo: Campo, valor: Formulario[Campo]) {
    setDatos((anterior) => ({ ...anterior, [campo]: valor }))
  }

  function enviar(evento: FormEvent) {
    evento.preventDefault()
    guardar.mutate({
      nombre: datos.nombre,
      apellidos: datos.apellidos,
      telefono: datos.telefono,
      tipo_sangre_id: datos.tipo_sangre_id,
      fecha_nacimiento: datos.fecha_nacimiento,
      sexo: datos.sexo,
      peso_kg: datos.peso_kg === '' ? null : Number(datos.peso_kg),
      zona: datos.zona === '' ? null : datos.zona,
      direccion: datos.direccion === '' ? null : datos.direccion,
      disponible: datos.disponible,
    })
  }

  const claseCampo = 'mt-1 w-full rounded border px-3 py-2'

  function mostrarError(campo: string) {
    return errores[campo] ? <span className="mt-1 block text-xs text-red-600">{errores[campo][0]}</span> : null
  }

  return (
    <section className="rounded-xl bg-white p-5 shadow">
      <h2 className="text-lg font-semibold">Mis datos</h2>
      <p className="text-sm text-gray-600">
        CI {perfil.ci} · {perfil.email} · {perfil.edad} años
      </p>

      <form onSubmit={enviar} className="mt-4 grid gap-4 sm:grid-cols-2">
        <label className="text-sm">
          Nombre
          <input className={claseCampo} value={datos.nombre} onChange={(e) => cambiar('nombre', e.target.value)} />
          {mostrarError('nombre')}
        </label>

        <label className="text-sm">
          Apellidos
          <input className={claseCampo} value={datos.apellidos} onChange={(e) => cambiar('apellidos', e.target.value)} />
          {mostrarError('apellidos')}
        </label>

        <label className="text-sm">
          Teléfono
          <input className={claseCampo} value={datos.telefono} onChange={(e) => cambiar('telefono', e.target.value)} />
          {mostrarError('telefono')}
        </label>

        <label className="text-sm">
          Tipo de sangre
          <select
            className={claseCampo}
            value={datos.tipo_sangre_id}
            onChange={(e) => cambiar('tipo_sangre_id', Number(e.target.value))}
          >
            {(tipos.data ?? [perfil.tipo_sangre]).map((tipo) => (
              <option key={tipo.id} value={tipo.id}>
                {tipo.codigo}
              </option>
            ))}
          </select>
          {mostrarError('tipo_sangre_id')}
        </label>

        <label className="text-sm">
          Fecha de nacimiento
          <input
            type="date"
            className={claseCampo}
            value={datos.fecha_nacimiento}
            onChange={(e) => cambiar('fecha_nacimiento', e.target.value)}
          />
          {mostrarError('fecha_nacimiento')}
        </label>

        <label className="text-sm">
          Sexo
          <select className={claseCampo} value={datos.sexo} onChange={(e) => cambiar('sexo', e.target.value as 'F' | 'M')}>
            <option value="F">Femenino</option>
            <option value="M">Masculino</option>
          </select>
          {mostrarError('sexo')}
        </label>

        <label className="text-sm">
          Peso (kg)
          <input
            type="number"
            step="0.1"
            className={claseCampo}
            value={datos.peso_kg}
            onChange={(e) => cambiar('peso_kg', e.target.value)}
          />
          {mostrarError('peso_kg')}
        </label>

        <label className="text-sm">
          Zona
          <input className={claseCampo} value={datos.zona} onChange={(e) => cambiar('zona', e.target.value)} />
          {mostrarError('zona')}
        </label>

        <label className="text-sm sm:col-span-2">
          Dirección
          <input className={claseCampo} value={datos.direccion} onChange={(e) => cambiar('direccion', e.target.value)} />
          {mostrarError('direccion')}
        </label>

        <label className="flex items-center gap-3 text-sm sm:col-span-2">
          <input
            type="checkbox"
            className="h-5 w-5 accent-red-600"
            checked={datos.disponible}
            onChange={(e) => cambiar('disponible', e.target.checked)}
          />
          Estoy disponible para recibir solicitudes de donación
        </label>

        <div className="flex items-center gap-3 sm:col-span-2">
          <button
            type="submit"
            disabled={guardar.isPending}
            className="rounded bg-red-600 px-5 py-2 font-semibold text-white hover:bg-red-700 disabled:opacity-50"
          >
            {guardar.isPending ? 'Guardando...' : 'Guardar cambios'}
          </button>
          {guardar.isSuccess && <span className="text-sm text-green-700">Perfil actualizado ✓</span>}
          {guardar.isError && Object.keys(errores).length === 0 && (
            <span className="text-sm text-red-600">{mensajeDeError(guardar.error)}</span>
          )}
        </div>
      </form>
    </section>
  )
}

export default FormularioPerfil
