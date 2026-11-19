import { Route, Routes } from 'react-router-dom'
import Inicio from './paginas/Inicio'

// Aquí cada integrante agrega las rutas (páginas) de su módulo.
function App() {
  return (
    <Routes>
      <Route path="/" element={<Inicio />} />
    </Routes>
  )
}

export default App
