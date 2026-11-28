// Validaciones de formularios (las mismas reglas que el backend en app/Support/Reglas.php).

// Deja solo letras (con tildes y ñ), espacios, apóstrofo, punto y guion: se usa al escribir nombres
export function soloLetras(texto: string): string {
  return texto.replace(/[^\p{L}\s'.-]/gu, '')
}

// Deja solo números: se usa al escribir teléfonos, carnet y NIT
export function soloNumeros(texto: string, maximo = 15): string {
  return texto.replace(/\D/g, '').slice(0, maximo)
}

export function requisitosClave(clave: string) {
  return [
    { texto: 'Mínimo 8 caracteres', cumple: clave.length >= 8 },
    { texto: 'Una mayúscula', cumple: /[A-Z]/.test(clave) },
    { texto: 'Una minúscula', cumple: /[a-z]/.test(clave) },
    { texto: 'Un número', cumple: /\d/.test(clave) },
    { texto: 'Un símbolo (#, !, @...)', cumple: /[^A-Za-z\d]/.test(clave) },
  ]
}

export function claveSegura(clave: string): boolean {
  return requisitosClave(clave).every((r) => r.cumple)
}

// Errores de los campos de una cuenta (nombre, teléfono, contraseña); vacío si todo está bien
export function validarCuenta(datos: {
  nombre: string
  apellidos?: string
  telefono: string
  password: string
  password_confirmation: string
  passwordOpcional?: boolean
}): Record<string, string[]> {
  const errores: Record<string, string[]> = {}
  if (datos.nombre.trim().length < 2) errores.nombre = ['Escribe el nombre (solo letras).']
  if (datos.apellidos !== undefined && datos.apellidos.trim().length < 2) errores.apellidos = ['Escribe los apellidos (solo letras).']
  if (datos.telefono.length < 7) errores.telefono = ['El teléfono debe tener entre 7 y 15 números.']
  const sinClave = datos.passwordOpcional && datos.password === ''
  if (!sinClave && !claveSegura(datos.password)) errores.password = ['La contraseña no cumple todos los requisitos.']
  if (!sinClave && datos.password !== datos.password_confirmation) errores.password_confirmation = ['Las contraseñas no coinciden.']
  return errores
}
