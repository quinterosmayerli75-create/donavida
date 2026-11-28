import { MapContainer, Marker, TileLayer, useMapEvents } from 'react-leaflet'
import L from 'leaflet'

// Zonas de Cochabamba para los selectores
export const ZONAS = ['Cercado (Centro)', 'Cala Cala', 'Sarco', 'Queru Queru', 'Tupuraya', 'Muyurina', 'Sacaba', 'Quillacollo', 'Tiquipaya', 'Colcapirhua', 'Vinto']

const CENTRO_COCHABAMBA: [number, number] = [-17.3895, -66.1568]

// Ícono del marcador (el de Leaflet por defecto no carga bien con Vite)
const marcador = L.divIcon({
  className: '',
  html: '<div style="width:22px;height:22px;border-radius:50%;background:#e11d48;border:4px solid #fff;box-shadow:0 0 0 2px #e11d48"></div>',
  iconSize: [22, 22],
  iconAnchor: [11, 11],
})

function EscucharClic({ alElegir }: { alElegir: (punto: [number, number]) => void }) {
  useMapEvents({ click: (evento) => alElegir([evento.latlng.lat, evento.latlng.lng]) })
  return null
}

// Mapa pequeño: al tocarlo se marca un punto (ubicación del hospital)
export function MapaElegirPunto({ punto, alElegir, alto = 'h-44' }: { punto: [number, number] | null; alElegir: (punto: [number, number]) => void; alto?: string }) {
  return (
    <div className={`${alto} overflow-hidden rounded-xl border border-borde`}>
      <MapContainer center={punto ?? CENTRO_COCHABAMBA} zoom={13} className="h-full w-full">
        <TileLayer attribution="&copy; OpenStreetMap" url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png" />
        <EscucharClic alElegir={alElegir} />
        {punto && <Marker position={punto} icon={marcador} />}
      </MapContainer>
    </div>
  )
}
