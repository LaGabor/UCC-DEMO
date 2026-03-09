<template>
    <nav class="navbar navbar-expand-lg bg-white border-bottom shadow-sm">
        <div class="container-fluid">
            <div class="d-flex align-items-center gap-2">
                <button
                    type="button"
                    class="btn btn-outline-secondary btn-sm"
                    @click="$emit('toggle-sidebar')"
                >
                    <i
                        class="bi"
                        :class="sidebarCollapsed ? 'bi-layout-sidebar-inset' : 'bi-layout-sidebar'"
                    ></i>
                </button>

                <router-link class="navbar-brand fw-semibold mb-0" :to="{ name: ROUTE_NAMES.HOME }">
                    {{ t('app.projectName') }}
                </router-link>
            </div>

            <div class="ms-auto d-flex align-items-center gap-3">
                <span class="text-muted small">
                    {{ auth.state.user?.name }}
                </span>

                <button class="btn btn-outline-danger btn-sm" @click="handleLogout">
                    <i class="bi bi-box-arrow-right me-1"></i>
                    {{ t('common.logout') }}
                </button>
            </div>
        </div>
    </nav>
</template>

<script setup lang="ts">
import { useRouter } from 'vue-router'
import { useAuth } from '../auth'
import { ROUTE_NAMES } from '../router'
import { useI18n } from 'vue-i18n'

defineProps<{
    sidebarCollapsed: boolean
}>()

defineEmits<{
    (e: 'toggle-sidebar'): void
}>()

const router = useRouter()
const auth = useAuth()
const { t } = useI18n()

async function handleLogout() {
    await auth.logout()
    await router.push({ name: ROUTE_NAMES.LOGIN })
}
</script>
