<template>
    <div class="m-3 py-3">
        <div class="mb-4">
            <h2 class="mb-1">{{ t('home.title') }}</h2>
            <p class="mb-0 text-muted">UCC DEMO</p>
        </div>

        <div
            v-if="flashMessage"
            class="alert alert-success alert-dismissible fade show"
            role="alert"
        >
            {{ flashMessage }}
            <button type="button" class="btn-close" @click="clearFlash"></button>
        </div>

        <div class="card shadow-sm">
            <div class="card-body">
                <p class="mb-0 text-muted" v-if="userName">{{ t('home.hello', { name: userName }) }}</p>
                <p class="mb-0 text-muted small" v-if="userRoleLabel">{{ t('home.userRole') }}: {{ userRoleLabel }}</p>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { onBeforeRouteLeave } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuth } from '../auth'
import { UserRole } from '../types/enums'

const { t } = useI18n()
const auth = useAuth()
const flashMessage = ref('')

const userName = computed(() => auth.state.user?.name ?? '')
const userRoleLabel = computed(() => {
    const role = auth.state.user?.role
    if (!role) return ''
    const key = role === UserRole.USER ? 'user' : role === UserRole.HELPDESK_AGENT ? 'helpdeskAgent' : 'admin'
    return t(`inviteUser.roles.${key}`)
})

function clearFlash(): void {
    flashMessage.value = ''
    sessionStorage.removeItem('flash_success')
}

onMounted(() => {
    const value = sessionStorage.getItem('flash_success')
    if (!value) {
        return
    }

    flashMessage.value = value
    sessionStorage.removeItem('flash_success')
})

onBeforeRouteLeave(() => {
    flashMessage.value = ''
})

onBeforeUnmount(() => {
    flashMessage.value = ''
})
</script>
