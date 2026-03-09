import axios from 'axios'
import './echo'
import { useAuth } from '@/auth'

declare global {
    interface Window {
        axios: typeof axios
    }
}

window.axios = axios

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest'
window.axios.defaults.withCredentials = true
window.axios.defaults.withXSRFToken = true

window.axios.interceptors.response.use(
    (response) => response,
    async (error) => {
        const status = error?.response?.status

        if (status === 401 || status === 419) {
            const auth = useAuth()
            auth.useAuth().clearAuth()

            if (window.location.pathname !== '/login') {
                window.location.href = '/login'
            }
        }

        return Promise.reject(error)
    }
)
