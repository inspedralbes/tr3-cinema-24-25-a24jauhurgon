// Servei API — Client HTTP centralitzat per comunicar-se amb el backend Laravel
import axios from 'axios'
import router from '../router'
import { useAuthStore } from '../stores/authStore'

// Crear instància d'axios amb la URL base de l'API
var api = axios.create({
    baseURL: import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api',
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
    },
    withCredentials: true
})

// Interceptor: afegir token d'autenticació a cada petició si existeix
api.interceptors.request.use(function (config) {
    var token = localStorage.getItem('auth_token')
    if (token) {
        config.headers.Authorization = 'Bearer ' + token
    }
    return config
}, function (error) {
    return Promise.reject(error)
})

// Interceptor: gestionar errors globals (401, 403, etc.)
api.interceptors.response.use(
    function (response) {
        return response
    },
    function (error) {
        if (error.response && error.response.status === 401) {
            // Token expirat o invàlid: tancar sessió a través de l'store i redirigir
            const authStore = useAuthStore()
            // Fem clear directe a vegades per evitar loops si el logout del backend també falla amb 401
            authStore.token = null
            authStore.usuari = null
            localStorage.removeItem('auth_token')
            localStorage.removeItem('usuari')

            router.push('/login')
        }
        return Promise.reject(error)
    }
)

export default api
