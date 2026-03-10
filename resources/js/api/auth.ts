import { apiClient } from './client'
import type { AuthUser } from '../types/auth'

export async function fetchCurrentUser(): Promise<AuthUser> {
    const response = await apiClient.get<AuthUser>('/api/user')
    return response.data
}

export async function logoutRequest(): Promise<void> {
    await apiClient.post('/logout')
}

export async function getCsrfCookie(): Promise<void> {
    await apiClient.get('/sanctum/csrf-cookie')
}

export async function loginRequest(payload: {
    email: string
    password: string
    remember: boolean
}): Promise<void> {
    await apiClient.post('/login', payload)
}

export async function twoFactorChallengeRequest(payload: {
    code?: string
    recovery_code?: string
}): Promise<void> {
    await apiClient.post('/two-factor-challenge', payload)
}
