import { createRouter, createWebHistory, type RouteRecordRaw } from 'vue-router'
import LoginPage from '../pages/LoginPage.vue'
import HomePage from '../pages/HomePage.vue'
import TwoFactorChallengePage from '../pages/TwoFactorChallengePage.vue'
import TwoFactorSettingsPage from '../pages/TwoFactorSettingsPage.vue'
import ForgotPasswordPage from '../pages/ForgotPasswordPage.vue'
import PasswordResetPage from '../pages/PasswordResetPage.vue'
import AcceptInvitationPage from '../pages/AcceptInvitationPage.vue'
import InviteUserPage from '../pages/InviteUserPage.vue'
import EventsPage from '../pages/EventsPage.vue'
import EventFormPage from '../pages/EventFormPage.vue'
import AgentMonitorPage from '../pages/AgentMonitorPage.vue'
import ConversationHistoryPage from '../pages/ConversationHistoryPage.vue'
import NotFoundPage from '../pages/NotFoundPage.vue'
import ForbiddenPage from '../pages/ForbiddenPage.vue'
import { getEvent } from '../api/events'
import { useAuth } from '../auth'
import { UserRole } from '../types/enums'
import type { EventItem } from '../types/events'

export const ROUTE_NAMES = {
    HOME: 'home',
    LOGIN:'login',
    FORGOT_PASSWORD: 'forgot-password',
    PASSWORD_RESET: 'password-reset',
    INVITATION_ACCEPT: 'invitation-accept',
    INVITE_USER: 'invite-user',
    EVENTS: 'events',
    EVENT_CREATE: 'event-create',
    EVENT_EDIT: 'event-edit',
    AGENT_MONITOR: 'agent-monitor',
    CONVERSATION_HISTORY: 'conversation-history',
    TWO_FACTOR_SETTINGS: 'two-factor-settings',
    TWO_FACTOR_CHALLENGE: 'two-factor-challenge',
    NOT_FOUND: 'not-found',
    FORBIDDEN: 'forbidden',
}

export const appRoutes: RouteRecordRaw[] = [
    {
        path: '/',
        redirect: '/home',
    },
    {
        path: '/login',
        name: ROUTE_NAMES.LOGIN,
        component: LoginPage,
        meta: {
            layout: 'guest',
            guestOnly: true,
        },
    },
    {
        path: '/forgot-password',
        name: ROUTE_NAMES.FORGOT_PASSWORD,
        component: ForgotPasswordPage,
        meta: {
            layout: 'guest',
            guestOnly: true,
        },
    },
    {
        path: '/password-reset/:token',
        name: ROUTE_NAMES.PASSWORD_RESET,
        component: PasswordResetPage,
        meta: {
            layout: 'guest',
            guestOnly: true,
        },
    },
    {
        path: '/invitations/accept/:token',
        name: ROUTE_NAMES.INVITATION_ACCEPT,
        component: AcceptInvitationPage,
        meta: {
            layout: 'guest',
            guestOnly: true,
        },
    },
    {
        path: '/home',
        name: ROUTE_NAMES.HOME,
        component: HomePage,
        meta: {
            layout: 'app',
            requiresAuth: true,
            showInSidebar: true,
            navLabelKey: 'navigation.home',
            navOrder: 1,
            iconClass: 'bi bi-house-door',
        },
    },
    {
        path: '/events',
        name: ROUTE_NAMES.EVENTS,
        component: EventsPage,
        meta: {
            layout: 'app',
            requiresAuth: true,
            showInSidebar: true,
            navLabelKey: 'navigation.events',
            navOrder: 2,
            iconClass: 'bi bi-calendar-event',
        },
    },
    {
        path: '/events/new',
        name: ROUTE_NAMES.EVENT_CREATE,
        component: EventFormPage,
        meta: {
            layout: 'app',
            requiresAuth: true,
        },
    },
    {
        path: '/events/:id/edit',
        name: ROUTE_NAMES.EVENT_EDIT,
        component: EventFormPage,
        props: (route) => ({
            initialEvent: (route.meta as { fetchedEvent?: EventItem }).fetchedEvent ?? null,
        }),
        meta: {
            layout: 'app',
            requiresAuth: true,
        },
        beforeEnter: async (to, _from, next) => {
            const id = Number(to.params.id)
            if (!Number.isInteger(id) || id <= 0) {
                next({ path: '/404' })
                return
            }
            try {
                const event = await getEvent(id)
                ;(to.meta as { fetchedEvent?: EventItem }).fetchedEvent = event
                next()
            } catch (err: unknown) {
                const status = (err as { response?: { status?: number } })?.response?.status
                if (status === 403) next({ path: '/403' })
                else if (status === 404) next({ path: '/404' })
                else next(false)
            }
        },
    },
    {
        path: '/agent-monitor',
        name: ROUTE_NAMES.AGENT_MONITOR,
        component: AgentMonitorPage,
        meta: {
            layout: 'app',
            requiresAuth: true,
            requiredRoles: [UserRole.ADMIN, UserRole.HELPDESK_AGENT],
            showInSidebar: true,
            navLabelKey: 'navigation.agentMonitor',
            navOrder: 4,
            iconClass: 'bi bi-headset',
        },
    },
    {
        path: '/admin/invite-user',
        name: ROUTE_NAMES.INVITE_USER,
        component: InviteUserPage,
        meta: {
            layout: 'app',
            requiresAuth: true,
            requiredRole: UserRole.ADMIN,
            showInSidebar: true,
            navLabelKey: 'navigation.inviteUser',
            navOrder: 5,
            iconClass: 'bi bi-person-plus',
        },
    },
    {
        path: '/conversations/history',
        name: ROUTE_NAMES.CONVERSATION_HISTORY,
        component: ConversationHistoryPage,
        meta: {
            layout: 'app',
            requiresAuth: true,
            requiredRoles: [UserRole.ADMIN, UserRole.HELPDESK_AGENT],
            showInSidebar: true,
            navLabelKey: 'navigation.conversationHistory',
            navOrder: 6,
            iconClass: 'bi bi-journals',
        },
    },
    {
        path: '/settings/two-factor',
        name: ROUTE_NAMES.TWO_FACTOR_SETTINGS,
        component: TwoFactorSettingsPage,
        meta: {
            layout: 'app',
            requiresAuth: true,
            showInSidebar: true,
            navLabelKey: 'navigation.twoFactorSettings',
            navOrder: 7,
            iconClass: 'bi bi-shield-lock',
        },
    },
    {
        path: '/two-factor-challenge',
        name: ROUTE_NAMES.TWO_FACTOR_CHALLENGE,
        component:TwoFactorChallengePage,
        meta: {
            guestOnly: true,
        },
    },
    {
        path: '/403',
        name: ROUTE_NAMES.FORBIDDEN,
        component: ForbiddenPage,
        meta: {
            layout: 'app',
        },
    },
    {
        name: ROUTE_NAMES.NOT_FOUND,
        path: '/:pathMatch(.*)*',
        component: NotFoundPage,
        meta: {
            layout: 'app',
        },
    },
]

const router = createRouter({
    history: createWebHistory(),
    routes: appRoutes,
})

router.beforeEach(async (to) => {
    const auth = useAuth()
    await auth.ensureAuthLoaded()

    const isLoggedIn = auth.isAuthenticated.value
    const requiresAuth = Boolean(to.meta.requiresAuth)
    const guestOnly = Boolean(to.meta.guestOnly)
    const requiredRole = to.meta.requiredRole as UserRole | undefined
    const requiredRoles = to.meta.requiredRoles as UserRole[] | undefined

    if (requiresAuth && !isLoggedIn) {
        return { name: ROUTE_NAMES.LOGIN }
    }

    if (guestOnly && isLoggedIn) {
        return { name: ROUTE_NAMES.HOME }
    }

    if (requiresAuth && requiredRole && auth.state.user?.role !== requiredRole) {
        return { name: ROUTE_NAMES.HOME }
    }

    if (requiresAuth && requiredRoles && !requiredRoles.includes(auth.state.user?.role as UserRole)) {
        return { name: ROUTE_NAMES.HOME }
    }

    return true
})

export default router
