import { apiClient } from './client'

export async function getTwoFactorQrCode(): Promise<string> {
    const response = await apiClient.get<{ svg: string }>('/user/two-factor-qr-code')
    return response.data.svg
}

export async function getTwoFactorRecoveryCodes(): Promise<string[]> {
    const response = await apiClient.get<{ recovery_codes: unknown }>('/user/two-factor-recovery-codes')
    return normalizeRecoveryCodes(response.data.recovery_codes)
}

export async function enableTwoFactorRequest(): Promise<void> {
    await apiClient.post('/user/two-factor-authentication')
}

export async function confirmTwoFactorRequest(code: string): Promise<void> {
    await apiClient.post('/user/confirmed-two-factor-authentication', { code })
}

export async function regenerateTwoFactorRecoveryCodesRequest(currentPassword: string): Promise<void> {
    await apiClient.post('/user/two-factor-recovery-codes', {
        current_password: currentPassword,
    })
}

export async function disableTwoFactorRequest(currentPassword: string): Promise<void> {
    await apiClient.delete('/user/two-factor-authentication', {
        data: {
            current_password: currentPassword,
        },
    })
}

function normalizeRecoveryCodes(value: unknown): string[] {
    if (Array.isArray(value)) {
        return value.filter((item): item is string => typeof item === 'string')
    }

    if (typeof value === 'string') {
        try {
            const parsed = JSON.parse(value) as unknown
            if (Array.isArray(parsed)) {
                return parsed.filter((item): item is string => typeof item === 'string')
            }
        } catch {
            // Fall back to line-based parsing below.
        }

        return value
            .split(/\r?\n/)
            .map((item) => item.trim())
            .filter((item) => item.length > 0)
    }

    return []
}
