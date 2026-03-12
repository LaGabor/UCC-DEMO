<template>
    <div class="app-shell min-vh-100 d-flex flex-column bg-light">
        <AppNavbar
            :sidebar-collapsed="effectiveSidebarCollapsed"
            :show-sidebar-toggle="true"
            @toggle-sidebar="toggleSidebar"
        />

        <div class="app-body d-flex flex-grow-1">
            <aside
                class="app-sidebar border-end bg-white"
                :class="{
                    'app-sidebar--collapsed': effectiveSidebarCollapsed,
                    'app-sidebar--hidden': isSidebarHidden,
                }"
            >
                <AppSidebar :collapsed="effectiveSidebarCollapsed" />
            </aside>

            <main class="app-content flex-grow-1">
                <slot />
            </main>
        </div>

        <AppFooter />
        <UserChatWidget v-if="showUserChatWidget" />
    </div>
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import AppNavbar from '../components/AppNavbar.vue'
import AppSidebar from '../components/AppSidebar.vue'
import AppFooter from '../components/AppFooter.vue'
import UserChatWidget from '../components/UserChatWidget.vue'
import { useAuth } from '../auth'
import { UserRole } from '../types/enums'

const MOBILE_BREAKPOINT_PX = 600
const SIDEBAR_PREF_KEY = 'sidebar_collapsed_preference'

const isUnderMobileBreakpoint = ref(false)
const userSidebarCollapsedPreference = ref(false)
const auth = useAuth()

const effectiveSidebarCollapsed = computed(() =>
    isUnderMobileBreakpoint.value ? true : userSidebarCollapsedPreference.value
)
const isSidebarHidden = computed(
    () => isUnderMobileBreakpoint.value && userSidebarCollapsedPreference.value
)
const showUserChatWidget = computed(() => auth.state.user?.role === UserRole.USER)

function loadUserSidebarPreference(): boolean {
    return window.localStorage.getItem(SIDEBAR_PREF_KEY) === '1'
}

function saveUserSidebarPreference(value: boolean): void {
    window.localStorage.setItem(SIDEBAR_PREF_KEY, value ? '1' : '0')
}

function updateBreakpointState(): void {
    isUnderMobileBreakpoint.value = window.innerWidth < MOBILE_BREAKPOINT_PX
}

function toggleSidebar() {
    userSidebarCollapsedPreference.value = !userSidebarCollapsedPreference.value
    saveUserSidebarPreference(userSidebarCollapsedPreference.value)
}

onMounted(() => {
    userSidebarCollapsedPreference.value = loadUserSidebarPreference()
    updateBreakpointState()
    window.addEventListener('resize', updateBreakpointState)
})

onBeforeUnmount(() => {
    window.removeEventListener('resize', updateBreakpointState)
})
</script>

<style scoped>
.app-body {
    min-height: 0;
    flex: 1 1 auto;
}

.app-sidebar {
    width: 220px;
    min-height: 100%;
    transition:
        width 0.2s ease-in-out,
        transform 0.2s ease-in-out,
        opacity 0.2s ease-in-out;
    overflow-x: hidden;
    position: relative;
    z-index: 20;
}

.app-sidebar--collapsed {
    width: 50px;
    min-width: 50px;
    flex-shrink: 0;
    overflow: visible;
}

.app-sidebar--hidden {
    width: 0;
    min-width: 0;
    transform: translateX(-110%);
    opacity: 0;
    pointer-events: none;
}

.app-content {
    min-width: 0;
}

</style>
