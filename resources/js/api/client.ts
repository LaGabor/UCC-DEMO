import axios, { type AxiosInstance } from 'axios'
import { clearAuthState } from '../authState'

export function isGuestPath(pathname: string): boolean {
    if (pathname === '/login' || pathname === '/forgot-password' || pathname === '/two-factor-challenge') {
        return true
    }

    return pathname.startsWith('/password-reset/') || pathname.startsWith('/invitations/accept/')
}

export const apiClient: AxiosInstance = axios.create({
    headers: {
        'X-Requested-With': 'XMLHttpRequest',
    },
    withCredentials: true,
    withXSRFToken: true,
})

apiClient.interceptors.response.use(
    (response) => response,
    (error) => {
        const status = error?.response?.status

        if (status === 401 || status === 419) {
            clearAuthState()

            if (!isGuestPath(window.location.pathname)) {
                window.location.href = '/login'
            }
        }

        return Promise.reject(error)
    }
)
