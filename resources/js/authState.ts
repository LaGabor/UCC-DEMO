import { reactive } from 'vue'
import type { AuthUser } from './types/auth'

export type AuthState = {
    user: AuthUser | null
    initialized: boolean
    loading: boolean
}

export const authState = reactive<AuthState>({
    user: null,
    initialized: false,
    loading: false,
})

export function clearAuthState(): void {
    authState.user = null
    authState.initialized = false
    authState.loading = false
}
