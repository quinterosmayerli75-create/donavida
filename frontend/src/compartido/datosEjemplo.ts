import type { CampanaTarjeta, FilaCitaHospital, FilaHistorial, PuntoRadar } from './maqueta'

// DATOS DE EJEMPLO copiados de docs/diseno/maquetas.html.
// Sirven para que cada pantalla se vea igual al diseño mientras no está conectada a la API.
// Cada integrante, al hacer la lógica de su rol, reemplaza estos datos por los de la API
// (las rutas de cada pantalla están en src/compartido/menus.ts) y borra lo que ya no use.

export const campanasEjemplo: CampanaTarjeta[] = [
  { mes: 'DIC', dia: '05', nombre: 'Dona sangre, comparte vida', lugar: 'Plaza de las Banderas', hospital: 'Hosp. Clínico Viedma', horario: '08:00 – 14:00', inscritos: 18, cupos: 30 },
  { mes: 'DIC', dia: '12', nombre: 'Una gota de ti, una vida para otro', lugar: 'Campus UMSS', hospital: 'Hospital del Norte', horario: '09:00 – 16:00', inscritos: 6, cupos: 50 },
  { mes: 'DIC', dia: '20', nombre: 'Navidad solidaria', lugar: 'Plaza 14 de Septiembre', hospital: 'Hosp. Clínico Viedma', horario: '08:00 – 13:00', inscritos: 2, cupos: 40 },
]

export const citasHospitalEjemplo: FilaCitaHospital[] = [
  { paciente: 'PAC-84192', servicio: 'Quirófano · Viedma', grupo: 'O-', donante: 'Carlos Mendoza', detalleDonante: '2.4 km · en camino', hora: '10:30', estado: 'En camino' },
  { paciente: 'PAC-84180', servicio: 'Maternidad', grupo: 'B-', donante: 'Mariana Soliz', detalleDonante: 'En sala', hora: '11:15', estado: 'En triaje' },
  { paciente: 'PAC-84095', servicio: 'Hosp. del Norte', grupo: 'AB+', donante: 'Diego Camacho', detalleDonante: '450 ml', hora: '09:00', estado: 'Donó' },
  { paciente: 'PAC-84012', servicio: 'Quirófano · Viedma', grupo: 'O+', donante: 'Andrea Zubieta', detalleDonante: '1.8 km · avisada', hora: '11:45', estado: 'Sin respuesta' },
]

export const puntosRadarEjemplo: PuntoRadar[] = [
  { x: 150, y: 96, grupo: 'O-', urgente: true },
  { x: 140, y: 150, grupo: 'O-', urgente: true },
  { x: 262, y: 92, grupo: 'O+', urgente: false },
  { x: 290, y: 140, grupo: 'AB+', urgente: false },
  { x: 250, y: 190, grupo: 'B-', urgente: true },
  { x: 170, y: 200, grupo: 'A-', urgente: false, apto: false },
]

export const historialEjemplo: FilaHistorial[] = [
  { fecha: '14 nov 2024', hospital: 'Hospital Clínico Viedma', servicio: 'Banco de Sangre', tipo: 'Sangre total', cantidad: '450 ml', atendio: 'Dra. Marcela Terán' },
  { fecha: '10 may 2024', hospital: 'Banco Regional de Sangre', servicio: 'Unidad Sarco', tipo: 'Plaquetas', cantidad: '1 dosis', atendio: 'Lic. J. Balderrama' },
  { fecha: '12 ene 2024', hospital: 'Hospital Materno Germán Urquidi', servicio: 'Servicio Transfusional', tipo: 'Sangre total', cantidad: '450 ml', atendio: 'Dra. P. Guzmán' },
]

// Reportes del hospital: unidades pedidas por grupo este mes
export const necesidadPorGrupoEjemplo = [
  { tipo_sangre: 'O+', total: 6 },
  { tipo_sangre: 'O-', total: 4.5 },
  { tipo_sangre: 'A+', total: 3 },
  { tipo_sangre: 'A-', total: 1 },
  { tipo_sangre: 'B+', total: 1 },
  { tipo_sangre: 'B-', total: 2.5 },
  { tipo_sangre: 'AB+', total: 2.5 },
  { tipo_sangre: 'AB-', total: 0.5 },
]
