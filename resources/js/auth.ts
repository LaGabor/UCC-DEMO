import { computed, reactive } from 'vue'
import axios from 'axios'

export type AuthUser = {
    id: number
    name: string
    email: string
    role?: string
    preferred_locale?: string
    two_factor_confirmed_at?: string | null
}

type AuthState = {
    user: AuthUser | null
    initialized: boolean
    loading: boolean
}

const state = reactive<AuthState>({
    user: null,
    initialized: false,
    loading: false,
})

async function fetchUser(): Promise<AuthUser | null> {
    try {
        state.loading = true
        const response = await axios.get('/api/user')
        state.user = response.data
        return state.user
    } catch {
        state.user = null
        return null
    } finally {
        state.loading = false
        state.initialized = true
    }
}

async function ensureAuthLoaded(): Promise<void> {

    if (state.initialized) {
        return
    }

    await fetchUser()
}

async function logout(): Promise<void> {
    await axios.post('/logout')
    state.user = null
    state.initialized = true
}

function clearAuth(): void {
    state.user = null
    state.initialized = false
    state.loading = false
}

const isAuthenticated = computed(() => !!state.user)

export function useAuth() {
    return {
        state,
        isAuthenticated,
        fetchUser,
        ensureAuthLoaded,
        logout,
        clearAuth,
    }
}
