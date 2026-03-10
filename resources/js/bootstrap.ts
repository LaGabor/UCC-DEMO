import './echo'
import { apiClient } from './api/client'
import type { AxiosInstance } from 'axios'

declare global {
    interface Window {
        axios: AxiosInstance
    }
}

window.axios = apiClient
