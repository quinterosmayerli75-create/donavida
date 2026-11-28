import { isAxiosError } from 'axios'

// Funciones pequeñas que usan todos los módulos.

const ZONA_BOLIVIA = 'America/La_Paz'

// La API guarda fechas en UTC; aquí se muestran en hora de Bolivia.
export function fechaCorta(fecha: string | null | undefined): string {
  if (!fecha) return '—'
  return new Date(fecha).toLocaleDateString('es-BO', { timeZone: ZONA_BOLIVIA, day: '2-digit', month: 'short', year: 'numeric' })
}

export function hora(fecha: string | null | undefined): string {
  if (!fecha) return '—'
  return new Date(fecha).toLocaleTimeString('es-BO', { timeZone: ZONA_BOLIVIA, hour: '2-digit', minute: '2-digit' })
}

export function fechaHora(fecha: string | null | undefined): string {
  if (!fecha) return '—'
  return `${fechaCorta(fecha)}, ${hora(fecha)}`
}

// "hace 14 min", "hace 3 h", "hace 2 días"
export function haceCuanto(fecha: string): string {
  const minutos = Math.round((Date.now() - new Date(fecha).getTime()) / 60000)
  if (minutos < 1) return 'ahora'
  if (minutos < 60) return `hace ${minutos} min`
  const horas = Math.round(minutos / 60)
  if (horas < 24) return `hace ${horas} h`
  return `hace ${Math.round(horas / 24)} días`
}

// Día y mes de una fecha "AAAA-MM-DD..." sin moverla de zona (para campañas)
export function diaMes(fecha: string): { dia: string; mes: string } {
  const [anio, mes, dia] = fecha.slice(0, 10).split('-')
  const nombreMes = new Date(Number(anio), Number(mes) - 1, 1).toLocaleDateString('es-BO', { month: 'short' })
  return { dia, mes: nombreMes.replace('.', '').toUpperCase() }
}

export function mensajeDeError(error: unknown): string {
  if (isAxiosError(error)) {
    const datos = error.response?.data
    if (datos?.errors) {
      const primero = Object.values(datos.errors as Record<string, string[]>)[0]
      if (primero?.[0]) return primero[0]
    }
    return datos?.mensaje ?? datos?.message ?? 'No se pudo conectar con el servidor.'
  }
  return 'Ocurrió un error inesperado.'
}

export type ErroresCampos = Record<string, string[]>

export function erroresDeValidacion(error: unknown): ErroresCampos {
  if (isAxiosError(error) && error.response?.status === 422) {
    return error.response.data.errors ?? {}
  }
  return {}
}

// Día "AAAA-MM-DD" en hora de Bolivia (para saber si una cita es de hoy)
export function diaBolivia(fecha: string | Date = new Date()): string {
  return new Date(fecha).toLocaleDateString('en-CA', { timeZone: ZONA_BOLIVIA })
}
