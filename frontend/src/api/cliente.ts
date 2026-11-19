import axios from 'axios'

// Cliente HTTP único para toda la app: todos los módulos lo importan desde aquí.
const clienteApi = axios.create({
  baseURL: import.meta.env.VITE_API_URL,
  headers: {
    Accept: 'application/json',
  },
})

// Antes de cada petición, si hay un token guardado, se envía en la cabecera Authorization.
clienteApi.interceptors.request.use((configuracion) => {
  const token = localStorage.getItem('token')
  if (token !== null) {
    configuracion.headers.Authorization = `Bearer ${token}`
  }
  return configuracion
})

export default clienteApi
