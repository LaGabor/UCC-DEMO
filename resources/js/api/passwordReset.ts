import { apiClient } from './client'

export type PasswordResetPayload = {
    email: string
    expires_at: string
}

export async function createPasswordResetRequest(email: string): Promise<void> {
    await apiClient.post('/api/public/password-reset-requests', { email })
}

export async function getPasswordResetByToken(token: string): Promise<PasswordResetPayload> {
    const response = await apiClient.get<PasswordResetPayload>(
        `/api/public/password-reset-requests/${encodeURIComponent(token)}`
    )

    return response.data
}

export async function completePasswordReset(
    token: string,
    payload: {
        password: string
        password_confirmation: string
    }
): Promise<void> {
    await apiClient.post(
        `/api/public/password-reset-requests/${encodeURIComponent(token)}/complete`,
        payload
    )
}
