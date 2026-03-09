<template>
    <div class="app-shell min-vh-100 d-flex flex-column bg-light">
        <AppNavbar
            :sidebar-collapsed="sidebarCollapsed"
            @toggle-sidebar="toggleSidebar"
        />

        <div class="app-body d-flex flex-grow-1">
            <aside
                class="app-sidebar border-end bg-white"
                :class="{
                    'app-sidebar--collapsed': sidebarCollapsed,
                }"
            >
                <AppSidebar :collapsed="sidebarCollapsed" />
            </aside>

            <main class="app-content flex-grow-1 p-4">
                <slot />
            </main>
        </div>

        <AppFooter />
    </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import AppNavbar from '../components/AppNavbar.vue'
import AppSidebar from '../components/AppSidebar.vue'
import AppFooter from '../components/AppFooter.vue'

const sidebarCollapsed = ref(false)

function toggleSidebar() {
    sidebarCollapsed.value = !sidebarCollapsed.value
}
</script>

<style scoped>
.app-body {
    min-height: 0;
    flex: 1 1 auto;
}

.app-sidebar {
    width: 260px;
    min-height: 100%;
    transition: width 0.2s ease-in-out;
    overflow-x: hidden;
}

.app-sidebar--collapsed {
    width: 76px;
}

.app-content {
    min-width: 0;
}
</style>
