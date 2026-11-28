import { Link, NavLink, Outlet, useLocation } from 'react-router-dom'
import { IconCirclePlus, IconDroplet, IconLogout } from '@tabler/icons-react'
import { Boton } from './ui'
import Campanita from '../modulos/autenticacion/Campanita'
import { useSesion } from '../modulos/autenticacion/ContextoSesion'
import { menus } from './menus'

// Módulo 1 (Mayerli): diseño general de los paneles.
// Menú lateral según el rol + barra superior. Cada pantalla se dibuja en <Outlet />.
function DisenoPanel() {
  const { usuario, cerrarSesion } = useSesion()
  const { pathname } = useLocation()

  if (usuario === null) return null

  const menu = menus[usuario.rol.nombre]
  const actual = menu.opciones.find((opcion) => opcion.ruta === pathname) ?? menu.opciones[0]
  const IconoRol = menu.icono
  const IconoPie = menu.pie.icono

  // Datos que se muestran junto al avatar según el rol
  let detalle = 'Administrador'
  if (usuario.donante) detalle = `Donante · ${usuario.donante.tipo_sangre.codigo.replace('-', '−')}`
  if (usuario.hospital) detalle = usuario.hospital.razon_social
  const iniciales = usuario.nombre
    .split(' ')
    .slice(0, 2)
    .map((parte) => parte[0])
    .join('')
    .toUpperCase()

  return (
    <div className="flex min-h-screen">
      <aside className="sticky top-0 flex h-screen w-[250px] flex-none flex-col border-r border-borde bg-white px-3.5 py-5">
        <div className="flex items-center gap-2.5 px-1.5 pb-5">
          <span className="grid h-10 w-10 place-items-center rounded-xl bg-marca text-white">
            <IconDroplet size={22} />
          </span>
          <div>
            <b className="block text-[17px]">DonaVida</b>
            <small className="text-xs text-texto-suave">Cochabamba</small>
          </div>
        </div>

        <div className="mb-5 flex items-center gap-2.5 rounded-xl bg-fondo px-3 py-2.5">
          <span className="grid h-9 w-9 place-items-center rounded-full" style={{ background: `${menu.color}1A`, color: menu.color }}>
            <IconoRol size={18} />
          </span>
          <div>
            <small className="block text-[11px] text-texto-suave">Portal</small>
            <b className="text-[13px]">{menu.nombrePortal}</b>
          </div>
        </div>

        <p className="px-2.5 pb-2 text-[11px] uppercase tracking-wider text-gray-400">Menú</p>
        <nav className="space-y-1">
          {menu.opciones.map((opcion) => {
            const IconoOpcion = opcion.icono
            return (
              <NavLink
                key={opcion.ruta}
                to={opcion.ruta}
                end
                className={({ isActive }) =>
                  `flex items-center gap-3 rounded-xl px-3 py-2.5 font-medium transition ${
                    isActive ? 'bg-marca text-white' : 'text-[#3b4152] hover:bg-fondo'
                  }`
                }
              >
                {({ isActive }) => (
                  <>
                    <IconoOpcion size={20} />
                    {opcion.texto}
                    {opcion.insignia !== undefined && (
                      <span
                        className={`ml-auto rounded-full px-[7px] py-px text-[11px] ${
                          isActive ? 'bg-white text-marca-oscuro' : 'bg-marca-oscuro text-white'
                        }`}
                      >
                        {opcion.insignia}
                      </span>
                    )}
                  </>
                )}
              </NavLink>
            )
          })}
        </nav>

        <div className="mt-auto rounded-2xl bg-marca-suave p-3.5">
          <b className="flex items-center gap-1.5 text-[13px] text-marca-oscuro">
            <IconoPie size={16} />
            {menu.pie.titulo}
          </b>
          <p className="mt-1.5 text-xs leading-relaxed text-texto-suave">{menu.pie.texto}</p>
        </div>
        <button onClick={cerrarSesion} className="flex items-center gap-2 px-2 pt-3 text-[13px] text-texto-suave hover:text-marca">
          <IconLogout size={17} />
          Cerrar sesión
        </button>
      </aside>

      <main className="min-w-0 flex-1">
        <header className="sticky top-0 z-[800] flex h-[72px] items-center gap-4 border-b border-borde bg-white/95 px-7 backdrop-blur">
          <div className="flex-1">
            <h1 className="text-[19px] font-bold">
              {usuario.donante && pathname === '/donante' ? `Hola, ${usuario.nombre.split(' ')[0]}` : actual.texto}
            </h1>
            <p className="text-xs text-texto-suave">{actual.subtitulo}</p>
          </div>
          <Campanita />
          <div className="flex items-center gap-2.5">
            {usuario.donante?.foto_url ? (
              <img src={usuario.donante.foto_url} alt="" className="h-[38px] w-[38px] rounded-full object-cover" />
            ) : (
              <span className="grid h-[38px] w-[38px] place-items-center rounded-full bg-marca text-[13px] font-semibold text-white">
                {iniciales}
              </span>
            )}
            <div className="hidden md:block">
              <b className="block text-[13px]">{usuario.nombre}</b>
              <small className="text-[11px] text-texto-suave">{detalle}</small>
            </div>
          </div>
          {menu.accion && (
            <Link to={menu.accion.ruta} className="hidden sm:block">
              <Boton variante="principal" className="px-[18px] py-[11px]">
                <IconCirclePlus size={17} />
                {menu.accion.texto}
              </Boton>
            </Link>
          )}
        </header>
        <div className="px-7 pb-10 pt-6">
          <Outlet />
        </div>
      </main>
    </div>
  )
}

export default DisenoPanel
