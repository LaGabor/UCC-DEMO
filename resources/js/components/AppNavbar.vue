<template>
    <nav class="navbar navbar-expand-lg bg-white border-bottom shadow-sm">
        <div class="container-fluid">
            <div class="d-flex align-items-center gap-2">
                <button
                    v-if="showSidebarToggle"
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
                <div class="dropdown">
                    <button
                        class="btn btn-link nav-link p-2 border-0 d-flex align-items-center topbar-trigger"
                        type="button"
                        data-bs-toggle="dropdown"
                        aria-expanded="false"
                        :disabled="isLanguageSwitching"
                    >
                        <span class="fs-5">{{ currentLanguageFlag }}</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <button class="dropdown-item d-flex align-items-center gap-2" type="button" @click="switchLanguage(Language.EN)">
                                <span>🇬🇧</span>
                                <span>{{ t('common.languages.en') }}</span>
                            </button>
                        </li>
                        <li>
                            <button class="dropdown-item d-flex align-items-center gap-2" type="button" @click="switchLanguage(Language.HU)">
                                <span>🇭🇺</span>
                                <span>{{ t('common.languages.hu') }}</span>
                            </button>
                        </li>
                    </ul>
                </div>

                <div class="dropdown">
                    <button
                        class="btn btn-link nav-link p-2 border-0 d-flex align-items-center gap-2 text-decoration-none topbar-trigger"
                        type="button"
                        data-bs-toggle="dropdown"
                        aria-expanded="false"
                    >
                        <i class="bi bi-person-circle fs-5"></i>
                        <span class="text-dark fw-semibold">{{ auth.state.user?.name }}</span>
                        <i class="bi bi-chevron-down text-muted small"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end">
                        <h6 class="dropdown-header">{{ t('common.welcome') }}</h6>
                        <button class="dropdown-item d-flex align-items-center gap-2" type="button" @click="handleLogout">
                            <i class="bi bi-box-arrow-right"></i>
                            <span>{{ t('common.logout') }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </nav>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuth } from '../auth'
import { ROUTE_NAMES } from '../router'
import { useI18n } from 'vue-i18n'
import { Language } from '../types/enums'

defineProps<{
    sidebarCollapsed: boolean
    showSidebarToggle: boolean
}>()

defineEmits<{
    (e: 'toggle-sidebar'): void
}>()

const router = useRouter()
const auth = useAuth()
const { t, locale } = useI18n()
const isLanguageSwitching = ref(false)

const currentLanguageFlag = computed(() =>
    locale.value === Language.HU ? '🇭🇺' : '🇬🇧'
)

async function switchLanguage(language: Language): Promise<void> {
    if (isLanguageSwitching.value) {
        return
    }

    isLanguageSwitching.value = true
    try {
        await auth.updatePreferredLocale(language)
    } finally {
        isLanguageSwitching.value = false
    }
}

async function handleLogout() {
    await auth.logout()
    await router.push({ name: ROUTE_NAMES.LOGIN })
}
</script>

<style scoped>
.topbar-trigger:hover {
    background-color: var(--bs-list-group-action-hover-bg);
}
</style>
