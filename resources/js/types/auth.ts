import { Language, UserRole, UserStatus } from './enums'

export type AuthUser = {
    id: number
    name: string
    email: string
    role?: UserRole
    preferred_locale?: Language
    two_factor_confirmed_at?: string | null
    two_factor_secret?: string | null
    status?: UserStatus
}
