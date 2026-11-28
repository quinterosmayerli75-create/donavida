import { campanasEjemplo } from '../../compartido/datosEjemplo'
import { TarjetasCampanas } from '../../compartido/maqueta'

// Hospital › Campañas (diseño de la maqueta).
// DATOS DE EJEMPLO: conectar con GET/POST/PUT/DELETE /hospital/campanas.
function PaginaCampanasHospital() {
  return <TarjetasCampanas campanas={campanasEjemplo} modo="hospital" />
}

export default PaginaCampanasHospital
