import { Route, Routes } from 'react-router-dom'
import Inicio from './paginas/Inicio'
import PortalDonante from './modulos/donantes/PortalDonante'

// Aquí cada integrante agrega las rutas (páginas) de su módulo.
function App() {
  return (
    <Routes>
      <Route path="/" element={<Inicio />} />
      {/* Módulo 2: Donantes (Maya) */}
      <Route path="/donante" element={<PortalDonante />} />
    </Routes>
  )
}

export default App
