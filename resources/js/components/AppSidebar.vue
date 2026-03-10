<template>
    <div class="p-3 h-100">
        <h6
            v-if="!collapsed"
            class="text-uppercase text-muted mb-3"
        >
            {{ t('navigation.title') }}
        </h6>

        <div class="list-group list-group-flush">
            <template v-for="item in navigationItems" :key="String(item.name)">
                <span
                    v-if="isCurrentRoute(item)"
                    class="list-group-item list-group-item-action active disabled d-flex align-items-center gap-2"
                    aria-disabled="true"
                    :title="String(item.meta?.navLabel ?? '')"
                >
                    <i v-if="item.meta?.iconClass" :class="String(item.meta.iconClass)"></i>

                    <span v-if="!collapsed">
                        {{ t(String(item.meta?.navLabelKey ?? '')) }}
                    </span>
                </span>

                <router-link
                    v-else
                    :to="{ name: String(item.name) }"
                    class="list-group-item list-group-item-action d-flex align-items-center gap-2"
                    :title="String(item.meta?.navLabel ?? '')"
                >
                    <i v-if="item.meta?.iconClass" :class="String(item.meta.iconClass)"></i>

                    <span v-if="!collapsed">
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

            if (!requiredRole) {
                return true
            }

            return auth.state.user?.role === requiredRole
        })
        .sort((a, b) => Number(a.meta?.navOrder ?? 999) - Number(b.meta?.navOrder ?? 999))
})

function isCurrentRoute(item: RouteRecordRaw): boolean {
    return route.name === item.name
}
</script>
