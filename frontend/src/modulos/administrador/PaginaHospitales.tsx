import { useState } from 'react'
import { useSearchParams } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import { IconBuildingHospital, IconCirclePlus, IconPencil } from '@tabler/icons-react'
import { BarraFiltros, SelectorFiltro } from '../../compartido/maqueta'
import { MapaElegirPunto } from '../../compartido/MapaElegirPunto'
import { Boton, Cargando, Etiqueta, Filtros, MensajeError, Tabla, Tarjeta, Vacio } from '../../compartido/ui'
import { fechaCorta, mensajeDeError } from '../../compartido/utilidades'
import { obtenerHospitales, type Hospital } from './api'
import { useAccionHospital } from './componentes'
import { VentanaHospital } from './Formularios'

type Filtro = 'todos' | 'validados' | 'pendientes' | 'desactivados'

function estadoHospital(h: Hospital): Exclude<Filtro, 'todos'> {
  if (h.verificado) return 'validados'
  return h.usuario.estado === 'inactivo' ? 'desactivados' : 'pendientes'
}

// Administrador › Hospitales: crear, editar, validar o desactivar hospitales de la red.
function PaginaHospitales() {
  const [filtro, setFiltro] = useState<Filtro>('todos')
  const [buscar, setBuscar] = useState('')
  const [zona, setZona] = useState('')
  const [parametros, setParametros] = useSearchParams()
  const [editar, setEditar] = useState<Hospital | null>(null)
  const [verUbicacion, setVerUbicacion] = useState<number | null>(null)
  const hospitales = useQuery({ queryKey: ['admin', 'hospitales'], queryFn: obtenerHospitales })
  const accion = useAccionHospital()

  // El botón rojo "Nuevo hospital" de la barra superior abre esta página con ?nuevo=1
  const creando = parametros.get('nuevo') === '1'
  const cerrarNuevo = () => setParametros({}, { replace: true })

  if (hospitales.isLoading) return <Cargando />
  if (hospitales.error) return <MensajeError>{mensajeDeError(hospitales.error)}</MensajeError>
  if (!hospitales.data) return null

  const todos = hospitales.data
  const contar = (estado: Exclude<Filtro, 'todos'>) => todos.filter((h) => estadoHospital(h) === estado).length
  const zonas = [...new Set(todos.map((h) => h.zona).filter((z): z is string => z !== null))].sort()
  const texto = buscar.trim().toLowerCase()
  const visibles = todos.filter(
    (h) =>
      (filtro === 'todos' || estadoHospital(h) === filtro) &&
      (zona === '' || h.zona === zona) &&
      (texto === '' || h.razon_social.toLowerCase().includes(texto) || h.nit.includes(texto) || h.usuario.email.toLowerCase().includes(texto)),
  )

  return (
    <>
      <Tarjeta
        titulo="Hospitales de la red"
        subtitulo={`${contar('validados')} validados · ${contar('pendientes')} pendientes · ${contar('desactivados')} desactivados`}
        icono={IconBuildingHospital}
        extra={
          <Filtros
            opciones={[
              ['todos', 'Todos'],
              ['validados', 'Validados'],
              ['pendientes', 'Pendientes'],
              ['desactivados', 'Desactivados'],
            ]}
            valor={filtro}
            alCambiar={setFiltro}
          />
        }
      >
        <BarraFiltros buscar={buscar} alBuscar={setBuscar} textoBuscar="Buscar por nombre, NIT o correo">
          <SelectorFiltro valor={zona} alCambiar={setZona}>
            <option value="">Todas las zonas</option>
            {zonas.map((z) => (
              <option key={z}>{z}</option>
            ))}
          </SelectorFiltro>
          <Boton variante="principal" onClick={() => setParametros({ nuevo: '1' })}>
            <IconCirclePlus size={16} />
            Nuevo hospital
          </Boton>
        </BarraFiltros>

        {visibles.length === 0 ? (
          <Vacio icono={IconBuildingHospital} titulo="No hay hospitales con estos filtros" />
        ) : (
          <Tabla columnas={['Hospital', 'Zona', 'Contacto', 'Registro', 'Estado', 'Acciones']}>
            {visibles.map((h) => {
              const estado = estadoHospital(h)
              return (
                <tr key={h.id}>
                  <td>
                    <b>{h.razon_social}</b>
                    <small className="mt-0.5 block text-xs text-texto-suave">NIT {h.nit}</small>
                    {verUbicacion === h.id && (
                      <div className="mt-2 w-72">
                        <MapaElegirPunto punto={[Number(h.latitud), Number(h.longitud)]} alElegir={() => {}} alto="h-36" />
                      </div>
                    )}
                  </td>
                  <td>
                    {h.zona ?? '—'}
                    <button onClick={() => setVerUbicacion(verUbicacion === h.id ? null : h.id)} className="mt-0.5 block text-xs font-semibold text-marca">
                      {verUbicacion === h.id ? 'Ocultar mapa' : 'Ver en mapa'}
                    </button>
                  </td>
                  <td>
                    {h.telefono_contacto}
                    <small className="mt-0.5 block text-xs text-texto-suave">{h.usuario.email}</small>
                  </td>
                  <td>{fechaCorta(h.created_at)}</td>
                  <td>
                    {estado === 'validados' ? (
                      <Etiqueta tono="exito">Validado</Etiqueta>
                    ) : estado === 'desactivados' ? (
                      <Etiqueta>Desactivado</Etiqueta>
                    ) : (
                      <Etiqueta tono="aviso">Pendiente</Etiqueta>
                    )}
                  </td>
                  <td>
                    <div className="flex justify-end gap-1.5">
                      <Boton pequeno onClick={() => setEditar(h)}>
                        <IconPencil size={14} />
                        Editar
                      </Boton>
                      {h.verificado ? (
                        <Boton
                          variante="borde"
                          pequeno
                          disabled={accion.isPending}
                          onClick={() => {
                            if (confirm(`¿Desactivar ${h.razon_social}? Ya no podrá iniciar sesión.`)) {
                              accion.mutate({ id: h.id, accion: 'desactivar' })
                            }
                          }}
                        >
                          Desactivar
                        </Boton>
                      ) : (
                        <Boton variante="exito" pequeno disabled={accion.isPending} onClick={() => accion.mutate({ id: h.id, accion: 'validar' })}>
                          {estado === 'desactivados' ? 'Reactivar' : 'Validar'}
                        </Boton>
                      )}
                    </div>
                  </td>
                </tr>
              )
            })}
          </Tabla>
        )}
      </Tarjeta>

      {creando && <VentanaHospital hospital={null} alCerrar={cerrarNuevo} />}
      {editar && <VentanaHospital hospital={editar} alCerrar={() => setEditar(null)} />}
    </>
  )
}

export default PaginaHospitales
