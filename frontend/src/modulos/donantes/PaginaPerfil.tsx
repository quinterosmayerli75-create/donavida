import { useState } from 'react'
import { IconAdjustments, IconUser } from '@tabler/icons-react'
import { Fila, Interruptor } from '../../compartido/maqueta'
import { Boton, Campo, Etiqueta, Tarjeta } from '../../compartido/ui'

// Donante › Mi perfil (diseño de la maqueta).
// DATOS DE EJEMPLO: Maya conecta aquí GET y PUT /donante/perfil
// (su lógica ya existe en ./api.ts y ./componentes/FormularioPerfil.tsx, MapaUbicacion.tsx y TarjetaPuedoDonar.tsx).
const ejemplo = [
  ['Nombre completo', 'Alejandro Morales Villarroel'],
  ['Carnet de identidad', '6429182 CBBA'],
  ['Teléfono', '7xx xxx xx'],
  ['Correo', 'alejandro@correo.com'],
  ['Grupo sanguíneo', 'O−'],
  ['Zona', 'Cala Cala, Cercado'],
]

function PaginaPerfil() {
  const [disponible, setDisponible] = useState(true)
  const [avisosCorreo, setAvisosCorreo] = useState(true)

  return (
    <div className="grid grid-cols-1 gap-[18px] lg:grid-cols-[1.4fr_1fr]">
      <Tarjeta titulo="Datos personales" icono={IconUser}>
        <div className="grid grid-cols-1 gap-3.5 sm:grid-cols-2">
          {ejemplo.map(([etiqueta, valor]) => (
            <Campo key={etiqueta} etiqueta={etiqueta} defaultValue={valor} />
          ))}
        </div>
        <div className="mt-[18px] text-right">
          <Boton variante="principal">Guardar cambios</Boton>
        </div>
      </Tarjeta>

      <Tarjeta titulo="Disponibilidad" icono={IconAdjustments}>
        <Fila>
          <div className="flex-1">
            <b className="block">Disponible para donar</b>
            <small className="text-xs text-texto-suave">Recibirás avisos de urgencias</small>
          </div>
          <Interruptor encendido={disponible} alCambiar={setDisponible} />
        </Fila>
        <Fila>
          <div className="flex-1">
            <b className="block">Distancia máxima</b>
            <small className="text-xs text-texto-suave">Hospitales a menos de 10 km</small>
          </div>
          <Etiqueta punto={false}>10 km</Etiqueta>
        </Fila>
        <Fila>
          <div className="flex-1">
            <b className="block">Avisos por correo</b>
            <small className="text-xs text-texto-suave">Además de la app</small>
          </div>
          <Interruptor encendido={avisosCorreo} alCambiar={setAvisosCorreo} />
        </Fila>
      </Tarjeta>
    </div>
  )
}

export default PaginaPerfil
