import { Navigate, Route, Routes } from 'react-router-dom'
import DisenoPanel from './compartido/DisenoPanel'
import PaginaCampanas from './modulos/administrador/PaginaCampanas'
import PaginaHospitales from './modulos/administrador/PaginaHospitales'
import PaginaReportes from './modulos/administrador/PaginaReportes'
import PaginaResumen from './modulos/administrador/PaginaResumen'
import PaginaUsuarios from './modulos/administrador/PaginaUsuarios'
import PaginaLogin from './modulos/autenticacion/PaginaLogin'
import PaginaRegistro from './modulos/autenticacion/PaginaRegistro'
import RutaProtegida from './modulos/autenticacion/RutaProtegida'
import PaginaCampanasDonante from './modulos/donantes/PaginaCampanasDonante'
import PaginaHistorial from './modulos/donantes/PaginaHistorial'
import PaginaInicio from './modulos/donantes/PaginaInicio'
import PaginaMisCitas from './modulos/donantes/PaginaMisCitas'
import PaginaPerfil from './modulos/donantes/PaginaPerfil'
import PaginaSolicitudesDonante from './modulos/donantes/PaginaSolicitudesDonante'
import PaginaCampanasHospital from './modulos/hospitales/PaginaCampanasHospital'
import PaginaCitas from './modulos/hospitales/PaginaCitas'
import PaginaDonantes from './modulos/hospitales/PaginaDonantes'
import PaginaReportesHospital from './modulos/hospitales/PaginaReportesHospital'
import PaginaResumenHospital from './modulos/hospitales/PaginaResumenHospital'
import PaginaSolicitudes from './modulos/hospitales/PaginaSolicitudes'
import Inicio from './paginas/Inicio'

// Rutas de la app. Todas las pantallas ya tienen el diseño; cada integrante conecta la API en las de su rol.
// El menú lateral de cada rol está en src/compartido/menus.ts.
function App() {
  return (
    <Routes>
      {/* Módulo 1: Autenticación (Mayerli) — páginas públicas */}
      <Route path="/" element={<Navigate to="/login" replace />} />
      <Route path="/login" element={<PaginaLogin />} />
      <Route path="/registro" element={<PaginaRegistro />} />
      <Route path="/prueba-conexion" element={<Inicio />} />

      {/* Administrador */}
      <Route
        path="/admin"
        element={
          <RutaProtegida rol="administrador">
            <DisenoPanel />
          </RutaProtegida>
        }
      >
        <Route index element={<PaginaResumen />} />
        <Route path="hospitales" element={<PaginaHospitales />} />
        <Route path="usuarios" element={<PaginaUsuarios />} />
        <Route path="campanas" element={<PaginaCampanas />} />
        <Route path="reportes" element={<PaginaReportes />} />
      </Route>

      {/* Hospital */}
      <Route
        path="/hospital"
        element={
          <RutaProtegida rol="hospital">
            <DisenoPanel />
          </RutaProtegida>
        }
      >
        <Route index element={<PaginaResumenHospital />} />
        <Route path="solicitudes" element={<PaginaSolicitudes />} />
        <Route path="donantes" element={<PaginaDonantes />} />
        <Route path="citas" element={<PaginaCitas />} />
        <Route path="campanas" element={<PaginaCampanasHospital />} />
        <Route path="reportes" element={<PaginaReportesHospital />} />
      </Route>

      {/* Donante */}
      <Route
        path="/donante"
        element={
          <RutaProtegida rol="donante">
            <DisenoPanel />
          </RutaProtegida>
        }
      >
        <Route index element={<PaginaInicio />} />
        <Route path="solicitudes" element={<PaginaSolicitudesDonante />} />
        <Route path="citas" element={<PaginaMisCitas />} />
        <Route path="campanas" element={<PaginaCampanasDonante />} />
        {/* Módulo 2: Donantes (Maya) */}
        <Route path="historial" element={<PaginaHistorial />} />
        <Route path="perfil" element={<PaginaPerfil />} />
      </Route>

      <Route path="*" element={<Navigate to="/login" replace />} />
    </Routes>
  )
}

export default App
