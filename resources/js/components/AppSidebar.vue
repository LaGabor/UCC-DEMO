<template>
    <div class="h-100 sidebar-root" :class="{ 'is-collapsed': collapsed }">
        <h6
            v-if="!collapsed"
            class="text-uppercase pt-3 text-muted mb-3 ms-3"
        >
            {{ t('navigation.title') }}
        </h6>

        <div class="list-group list-group-flush">
            <template v-for="item in navigationItems" :key="String(item.name)">
                <span
                    v-if="isCurrentRoute(item)"
                    class="list-group-item list-group-item-action active disabled d-flex align-items-center gap-2 nav-item"
                    aria-disabled="true"
                    :title="String(item.meta?.navLabel ?? '')"
                >
                    <i v-if="item.meta?.iconClass" :class="String(item.meta.iconClass)"></i>

                    <span class="nav-label">
                        {{ t(String(item.meta?.navLabelKey ?? '')) }}
                    </span>
                </span>

                <router-link
                    v-else
                    :to="{ name: String(item.name) }"
                    class="list-group-item list-group-item-action d-flex align-items-center gap-2 nav-item"
                    :title="String(item.meta?.navLabel ?? '')"
                >
                    <i v-if="item.meta?.iconClass" :class="String(item.meta.iconClass)"></i>

                    <span class="nav-label">
                        {{ t(String(item.meta?.navLabelKey ?? '')) }}
                    </span>
                </router-link>
            </template>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { appRoutes } from '../router'
import type { RouteRecordRaw } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuth } from '../auth'

defineProps<{
    collapsed: boolean
}>()

const route = useRoute()
const { t } = useI18n()
const auth = useAuth()

const navigationItems = computed(() => {
    return appRoutes
        .filter((item: RouteRecordRaw) => {
            if (!item.name || !item.meta?.showInSidebar) {
                return false
            }

            const requiredRole = item.meta?.requiredRole as string | undefined
            const requiredRoles = item.meta?.requiredRoles as string[] | undefined

            if (requiredRoles && requiredRoles.length > 0) {
                return Boolean(auth.state.user?.role && requiredRoles.includes(auth.state.user.role))
            }

            if (requiredRole) {
                return auth.state.user?.role === requiredRole
            }

            return true
        })
        .sort((a, b) => Number(a.meta?.navOrder ?? 999) - Number(b.meta?.navOrder ?? 999))
})

function isCurrentRoute(item: RouteRecordRaw): boolean {
    return route.name === item.name
}
</script>

<style scoped>
.nav-item {
    overflow: visible;
    position: relative;
}

.sidebar-root.is-collapsed .nav-item {
    justify-content: center;
}

.sidebar-root.is-collapsed .nav-label {
    position: absolute;
    left: calc(100%);
    top: 50%;
    transform: translateY(-50%);
    white-space: nowrap;
    padding: 0.5rem 0.7rem;
    background: var(--bs-list-group-bg);
    opacity: 0;
    visibility: hidden;
    pointer-events: none;
    transition: opacity 0.15s ease, transform 0.15s ease;
    z-index: 50;
}

.sidebar-root.is-collapsed .nav-item:hover .nav-label,
.sidebar-root.is-collapsed .nav-item:focus-within .nav-label {
    opacity: 1;
    visibility: visible;
    transform: translateY(-50%) translateX(2px);
}
</style>
