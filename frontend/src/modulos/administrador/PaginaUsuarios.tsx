import { useState } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { IconBuildingHospital, IconHeart, IconPencil, IconShieldCheck, IconUserPlus, IconUsers } from '@tabler/icons-react'
import { BarraFiltros, SelectorFiltro } from '../../compartido/maqueta'
import { VentanaDonante, VentanaHospital } from './Formularios'
import { useSesion } from '../autenticacion/ContextoSesion'
import { Boton, Cargando, Cifra, Etiqueta, Filtros, GrupoSangre, MensajeError, Tabla, Tarjeta, Vacio } from '../../compartido/ui'
import { fechaCorta, mensajeDeError } from '../../compartido/utilidades'
import { cambiarEstadoUsuario, obtenerHospitales, obtenerUsuarios, type RolUsuario, type UsuarioRed } from './api'

type Filtro = 'todos' | RolUsuario

const textoRol: Record<RolUsuario, string> = { donante: 'Donante', hospital: 'Hospital', administrador: 'Admin' }
const tonoRol = { donante: 'urgente', hospital: 'info', administrador: 'exito' } as const

// Iniciales para el circulito: "Ana Rojas" → "AR"
function iniciales(nombre: string): string {
  return nombre
    .split(' ')
    .filter(Boolean)
    .slice(0, 2)
    .map((parte) => parte[0].toUpperCase())
    .join('')
}

function EstadoUsuario({ estado }: { estado: UsuarioRed['estado'] }) {
  if (estado === 'activo') return <Etiqueta tono="exito">Activo</Etiqueta>
  if (estado === 'pendiente') return <Etiqueta tono="aviso">Pendiente</Etiqueta>
  return <Etiqueta>Suspendido</Etiqueta>
}

// Administrador › Usuarios: todas las cuentas de la red.
function PaginaUsuarios() {
  const [filtro, setFiltro] = useState<Filtro>('todos')
  const [buscar, setBuscar] = useState('')
  const [estado, setEstado] = useState('')
  const [grupo, setGrupo] = useState('')
  // null = ventana cerrada; 0 = donante nuevo; otro número = id del donante a editar
  const [editarDonante, setEditarDonante] = useState<number | null>(null)
  const [editarHospital, setEditarHospital] = useState<number | null>(null)
  const hospitales = useQuery({ queryKey: ['admin', 'hospitales'], queryFn: obtenerHospitales })
  const { usuario: yo } = useSesion()
  const clienteConsultas = useQueryClient()
  const consulta = useQuery({ queryKey: ['admin', 'usuarios'], queryFn: obtenerUsuarios })
  const cambiar = useMutation({
    mutationFn: ({ id, estado }: { id: number; estado: 'activo' | 'inactivo' }) => cambiarEstadoUsuario(id, estado),
    onSuccess: () => clienteConsultas.invalidateQueries({ queryKey: ['admin'] }),
    onError: (error) => alert(mensajeDeError(error)),
  })

  if (consulta.isLoading) return <Cargando />
  if (consulta.error) return <MensajeError>{mensajeDeError(consulta.error)}</MensajeError>
  if (!consulta.data) return null

  const { totales, usuarios } = consulta.data
  const texto = buscar.trim().toLowerCase()
  const visibles = usuarios.filter(
    (u) =>
      (filtro === 'todos' || u.rol === filtro) &&
      (estado === '' || u.estado === estado) &&
      (grupo === '' || u.tipo_sangre === grupo) &&
      (texto === '' || u.nombre.toLowerCase().includes(texto) || u.email.toLowerCase().includes(texto)),
  )

  function accion(u: UsuarioRed) {
    const editar =
      u.donante_id !== null ? (
        <Boton pequeno onClick={() => setEditarDonante(u.donante_id)}>
          <IconPencil size={14} />
          Editar
        </Boton>
      ) : u.hospital_id !== null ? (
        <Boton pequeno onClick={() => setEditarHospital(u.hospital_id)}>
          <IconPencil size={14} />
          Editar
        </Boton>
      ) : null

    // Los hospitales se validan o desactivan en la pantalla Hospitales; uno mismo no se puede suspender
    let estadoBoton = null
    if (u.rol !== 'hospital' && u.id !== yo?.id) {
      estadoBoton =
        u.estado === 'inactivo' ? (
          <Boton variante="exito" pequeno disabled={cambiar.isPending} onClick={() => cambiar.mutate({ id: u.id, estado: 'activo' })}>
            Reactivar
          </Boton>
        ) : (
          <Boton
            variante="borde"
            pequeno
            disabled={cambiar.isPending}
            onClick={() => {
              if (confirm(`¿Suspender la cuenta de ${u.nombre}? Ya no podrá iniciar sesión.`)) {
                cambiar.mutate({ id: u.id, estado: 'inactivo' })
              }
            }}
          >
            Suspender
          </Boton>
        )
    }

    if (!editar && !estadoBoton) return <span className="text-texto-suave">—</span>
    return (
      <div className="flex justify-end gap-1.5">
        {editar}
        {estadoBoton}
      </div>
    )
  }

  const hospitalAEditar = hospitales.data?.find((h) => h.id === editarHospital) ?? null

  return (
    <div className="space-y-[18px]">
      <div className="grid grid-cols-1 gap-[18px] sm:grid-cols-3">
        <Cifra titulo="Donantes" valor={totales.donante} detalle="Cuentas de donante" icono={IconHeart} />
        <Cifra titulo="Hospitales" valor={totales.hospital} detalle="Cuentas de hospital" icono={IconBuildingHospital} color="#2563EB" />
        <Cifra titulo="Administradores" valor={totales.administrador} detalle="Equipo SEDES" icono={IconShieldCheck} color="#16A34A" />
      </div>

      <Tarjeta
        titulo="Todos los usuarios"
        icono={IconUsers}
        extra={
          <Filtros
            opciones={[
              ['todos', 'Todos'],
              ['donante', 'Donantes'],
              ['hospital', 'Hospitales'],
              ['administrador', 'Admin'],
            ]}
            valor={filtro}
            alCambiar={setFiltro}
          />
        }
      >
        <BarraFiltros buscar={buscar} alBuscar={setBuscar} textoBuscar="Buscar por nombre o correo">
          <SelectorFiltro valor={estado} alCambiar={setEstado}>
            <option value="">Todos los estados</option>
            <option value="activo">Activos</option>
            <option value="pendiente">Pendientes</option>
            <option value="inactivo">Suspendidos</option>
          </SelectorFiltro>
          <SelectorFiltro valor={grupo} alCambiar={setGrupo}>
            <option value="">Todos los grupos</option>
            {['O+', 'O-', 'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-'].map((g) => (
              <option key={g} value={g}>
                {g.replace('-', '−')}
              </option>
            ))}
          </SelectorFiltro>
          <Boton variante="principal" onClick={() => setEditarDonante(0)}>
            <IconUserPlus size={16} />
            Nuevo donante
          </Boton>
        </BarraFiltros>
        <p className="mb-2 text-xs text-texto-suave">
          {visibles.length} de {usuarios.length} usuarios
        </p>
        {visibles.length === 0 ? (
          <Vacio icono={IconUsers} titulo="No hay usuarios con estos filtros" />
        ) : (
          <Tabla columnas={['Usuario', 'Rol', 'Grupo', 'Registro', 'Estado', 'Acciones']}>
            {visibles.map((u) => (
              <tr key={u.id}>
                <td>
                  <div className="flex items-center gap-2.5">
                    {u.foto_url ? (
                      <img src={u.foto_url} alt="" className="h-9 w-9 flex-none rounded-full object-cover" />
                    ) : (
                      <span className="grid h-9 w-9 flex-none place-items-center rounded-full bg-fondo text-[13px] font-semibold">
                        {iniciales(u.nombre)}
                      </span>
                    )}
                    <div>
                      <b>{u.nombre}</b>
                      <small className="mt-0.5 block text-xs text-texto-suave">{u.email}</small>
                    </div>
                  </div>
                </td>
                <td>
                  <Etiqueta tono={tonoRol[u.rol]}>{textoRol[u.rol]}</Etiqueta>
                </td>
                <td>{u.tipo_sangre ? <GrupoSangre codigo={u.tipo_sangre} tamano="sm" /> : '—'}</td>
                <td>{fechaCorta(u.created_at)}</td>
                <td>
                  <EstadoUsuario estado={u.estado} />
                </td>
                <td>{accion(u)}</td>
              </tr>
            ))}
          </Tabla>
        )}
      </Tarjeta>

      {editarDonante !== null && <VentanaDonante donanteId={editarDonante === 0 ? null : editarDonante} alCerrar={() => setEditarDonante(null)} />}
      {hospitalAEditar && <VentanaHospital hospital={hospitalAEditar} alCerrar={() => setEditarHospital(null)} />}
    </div>
  )
}

export default PaginaUsuarios
