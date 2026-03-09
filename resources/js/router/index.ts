import { createRouter, createWebHistory } from 'vue-router'
import LoginPage from '../pages/LoginPage.vue'
import HomePage from '../pages/HomePage.vue'
import TwoFactorChallengePage from '../pages/TwoFactorChallengePage.vue'
import TwoFactorSettingsPage from '../pages/TwoFactorSettingsPage.vue'
import { useAuth } from '../auth'

export const ROUTE_NAMES = {
    HOME: 'home',
    LOGIN:'login',
    TWO_FACTOR_SETTINGS: 'two-factor-settings',
    TWO_FACTOR_CHALLENGE: 'two-factor-challenge',
    NOT_FOUND: 'not-found',
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
        path: '/settings/two-factor',
        name: ROUTE_NAMES.TWO_FACTOR_SETTINGS,
        component: TwoFactorSettingsPage,
        meta: {
            layout: 'app',
            requiresAuth: true,
            showInSidebar: true,
            navLabelKey: 'navigation.twoFactorSettings',
            navOrder: 2,
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
        name: ROUTE_NAMES.NOT_FOUND,
        path: '/:catchAll(.*)',
        redirect: '/',
    }
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

    if (requiresAuth && !isLoggedIn) {
        return { name: ROUTE_NAMES.LOGIN }
    }

    if (guestOnly && isLoggedIn) {
        return { name: ROUTE_NAMES.HOME }
    }

    return true
})

export default router
