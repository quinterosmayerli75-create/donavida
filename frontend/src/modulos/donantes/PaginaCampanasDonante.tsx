import { campanasEjemplo } from '../../compartido/datosEjemplo'
import { TarjetasCampanas } from '../../compartido/maqueta'

// Donante › Campañas (diseño de la maqueta).
// DATOS DE EJEMPLO: conectar con GET /donante/campanas y "Inscribirme" con POST /donante/citas
// (obtenerCampanasDonante ya está en ./apiPortal.ts).
function PaginaCampanasDonante() {
  return <TarjetasCampanas campanas={campanasEjemplo} modo="donante" />
}

export default PaginaCampanasDonante
