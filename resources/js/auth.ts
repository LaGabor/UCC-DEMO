import { computed } from 'vue'
import type { AuthUser } from './types/auth'
import { UserRole } from './types/enums'
import { fetchCurrentUser, logoutRequest } from './api/auth'
import { authState, clearAuthState } from './authState'

async function fetchUser(): Promise<AuthUser | null> {
    try {
        authState.loading = true
        authState.user = await fetchCurrentUser()
        return authState.user
    } catch {
        authState.user = null
        return null
    } finally {
        authState.loading = false
        authState.initialized = true
    }
}

async function ensureAuthLoaded(): Promise<void> {

    if (authState.initialized) {
        return
    }

    await fetchUser()
}

async function logout(): Promise<void> {
    await logoutRequest()
    authState.user = null
    authState.initialized = true
}

function clearAuth(): void {
    clearAuthState()
}

const isAuthenticated = computed(() => !!authState.user)
const isAdmin = computed(() => authState.user?.role === UserRole.ADMIN)

export function useAuth() {
    return {
        state: authState,
        isAuthenticated,
        isAdmin,
        fetchUser,
        ensureAuthLoaded,
        logout,
        clearAuth,
    }
}
