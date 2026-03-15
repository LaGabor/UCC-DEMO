import { apiClient } from './client'
import { UserRole, UserStatus, type Language } from '../types/enums'
import type { AuthUser } from '../types/auth'

export type InvitationPayload = {
    email: string
    expires_at: string
}

export async function getInvitationByToken(token: string): Promise<InvitationPayload> {
    const response = await apiClient.get<InvitationPayload>(
        `/api/public/user-invitations/${encodeURIComponent(token)}`
    )

    return response.data
}

export async function acceptInvitation(
    token: string,
    payload: {
        name: string
        preferred_locale: Language
        password: string
        password_confirmation: string
    }
): Promise<void> {
    await apiClient.patch(
        `/api/public/user-invitations/${encodeURIComponent(token)}/accept`,
        payload
    )
}

export async function createUserInvitation(payload: {
    email: string
    role: UserRole
}): Promise<void> {
    await apiClient.post('/api/admin/user-invitations', payload)
}

export async function fetchAdminEligibility(): Promise<boolean> {
    const response = await apiClient.get<AuthUser>('/api/user')
    const user = response.data as { role?: UserRole; status?: UserStatus }

    return user.role === UserRole.ADMIN && user.status === UserStatus.ACTIVE
}
