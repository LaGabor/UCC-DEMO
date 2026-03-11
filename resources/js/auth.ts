import { computed } from 'vue'
import type { AuthUser } from './types/auth'
import { Language, UserRole } from './types/enums'
import { fetchCurrentUser, logoutRequest, updatePreferredLocaleRequest } from './api/auth'
import { authState, clearAuthState } from './authState'
import { setAppLocale } from './i18n'

async function fetchUser(): Promise<AuthUser | null> {
    try {
        authState.loading = true
        authState.user = await fetchCurrentUser()
        const preferredLocale = authState.user?.preferred_locale
        if (preferredLocale === Language.HU || preferredLocale === Language.EN) {
            setAppLocale(preferredLocale)
        }
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

async function updatePreferredLocale(language: Language): Promise<void> {
    await updatePreferredLocaleRequest(language)
    setAppLocale(language)

    if (authState.user) {
        authState.user.preferred_locale = language
    }
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
        updatePreferredLocale,
        clearAuth,
    }
}
