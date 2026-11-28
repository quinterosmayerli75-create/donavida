import { useMutation, useQueryClient } from '@tanstack/react-query'
import { Link } from 'react-router-dom'
import { IconBuildingHospital, IconCheck, IconMapPin } from '@tabler/icons-react'
import { Boton, Etiqueta } from '../../compartido/ui'
import { haceCuanto, mensajeDeError } from '../../compartido/utilidades'
import { desactivarHospital, validarHospital, type Hospital } from './api'

// Piezas que comparten las pantallas del administrador.

// Validar o desactivar un hospital; al terminar se recargan las listas
export function useAccionHospital() {
  const clienteConsultas = useQueryClient()
  return useMutation({
    mutationFn: ({ id, accion }: { id: number; accion: 'validar' | 'desactivar' }) =>
      accion === 'validar' ? validarHospital(id) : desactivarHospital(id),
    onSuccess: () => clienteConsultas.invalidateQueries({ queryKey: ['admin'] }),
    onError: (error) => alert(mensajeDeError(error)),
  })
}

// Fila de un hospital pendiente con su botón "Validar"
export function FilaPendiente({ hospital }: { hospital: Hospital }) {
  const accion = useAccionHospital()
  return (
    <div className="flex items-center gap-3.5 border-t border-borde py-3.5 first:border-t-0 first:pt-0">
      <span className="grid h-11 w-11 flex-none place-items-center rounded-xl bg-fondo">
        <IconBuildingHospital size={20} />
      </span>
      <div className="min-w-0 flex-1">
        <b className="block">{hospital.razon_social}</b>
        <small className="mt-0.5 flex items-center gap-1 text-xs text-texto-suave">
          <IconMapPin size={14} />
          {hospital.zona ?? hospital.direccion} · registrado {haceCuanto(hospital.created_at)}
        </small>
      </div>
      <Etiqueta tono="aviso">Pendiente</Etiqueta>
      <Boton
        variante="exito"
        pequeno
        disabled={accion.isPending}
        onClick={() => accion.mutate({ id: hospital.id, accion: 'validar' })}
      >
        <IconCheck size={15} />
        Validar
      </Boton>
      <Link to="/admin/hospitales">
        <Boton pequeno>Ver datos</Boton>
      </Link>
    </div>
  )
}
