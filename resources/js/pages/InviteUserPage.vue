<template>
    <div class="m-3 py-3">
        <div class="mb-4">
            <h2 class="mb-1">{{ t('inviteUser.title') }}</h2>
            <p class="mb-0 text-muted">{{ t('inviteUser.subtitle') }}</p>
        </div>

        <div
            v-if="alert.message"
            :class="['alert alert-dismissible fade show', alert.type === 'success' ? 'alert-success' : 'alert-danger']"
            role="alert"
        >
            {{ alert.message }}
            <button type="button" class="btn-close" @click="clearAlert"></button>
        </div>

        <div class="card shadow-sm">
            <div class="card-body">
                <h5 class="mb-3">{{ t('inviteUser.formTitle') }}</h5>
                <form @submit.prevent="submit">
                    <div class="mb-3">
                        <label for="email" class="form-label">{{ t('inviteUser.email') }}</label>
                        <input
                            id="email"
                            v-model="form.email"
                            type="email"
                            class="form-control"
                            autocomplete="email"
                            required
                        >
                        <div v-if="fieldErrors.email" class="text-danger small mt-1">{{ fieldErrors.email }}</div>
                    </div>

                    <div class="mb-4">
                        <label for="role" class="form-label">{{ t('inviteUser.role') }}</label>
                        <select id="role" v-model="form.role" class="form-select">
                            <option :value="UserRole.USER">{{ t('inviteUser.roles.user') }}</option>
                            <option :value="UserRole.HELPDESK_AGENT">{{ t('inviteUser.roles.helpdeskAgent') }}</option>
                            <option :value="UserRole.ADMIN">{{ t('inviteUser.roles.admin') }}</option>
                        </select>
                    </div>

                    <button
                        v-if="!success"
                        type="submit"
                        class="btn btn-primary"
                        :disabled="loading"
                    >
                        {{ loading ? t('inviteUser.submitting') : t('inviteUser.submit') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue'
import { onBeforeRouteLeave, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { ROUTE_NAMES } from '../router'
import { UserRole } from '../types/enums'
import { getApiErrorMessage } from '../utils/apiErrorMessage'
import { createUserInvitation, fetchAdminEligibility } from '../api/invitations'

type AlertType = 'success' | 'error'

const router = useRouter()
const { t } = useI18n()

const loading = ref(false)
const success = ref(false)

const form = reactive({
    email: '',
    role: UserRole.USER as UserRole,
})

const fieldErrors = reactive({
    email: '',
})

const alert = reactive<{ type: AlertType; message: string }>({
    type: 'success',
    message: '',
})

function setAlert(type: AlertType, message: string): void {
    alert.type = type
    alert.message = message
}

function clearAlert(): void {
    alert.message = ''
}

function resetUiState(): void {
    clearAlert()
    loading.value = false
    success.value = false
    form.email = ''
    form.role = UserRole.USER
    fieldErrors.email = ''
}

function validate(): boolean {
    form.email = form.email.trim()
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/

    if (!emailRegex.test(form.email)) {
        fieldErrors.email = t('validation.invalidEmail')
        return false
    }

    fieldErrors.email = ''

    return true
}

async function backendAdminCheck(): Promise<boolean> {
    return fetchAdminEligibility()
}

async function submit(): Promise<void> {
    clearAlert()

    if (!validate()) {
        return
    }

    loading.value = true

    try {
        await createUserInvitation({
            email: form.email,
            role: form.role,
        })

        success.value = true
        setAlert('success', t('inviteUser.success'))

        sessionStorage.setItem('flash_success', t('inviteUser.flashSuccess'))
        await router.push({ name: ROUTE_NAMES.HOME })
    } catch (error: any) {
        setAlert(
            'error',
            getApiErrorMessage(t, error, 'inviteUser.error', {
                EMAIL_ALREADY_EXISTS: 'inviteUser.errors.emailAlreadyExists',
                FORBIDDEN: 'errors.forbidden',
                VALIDATION_ERROR: 'errors.validation',
            })
        )
    } finally {
        loading.value = false
    }
}

onMounted(async () => {
    const isAdmin = await backendAdminCheck()

    if (!isAdmin) {
        await router.replace({ name: ROUTE_NAMES.HOME })
    }
})

watch(
    () => form.email,
    () => {
        validate()
    }
)

onBeforeRouteLeave(() => {
    resetUiState()
})

onBeforeUnmount(() => {
    resetUiState()
})
</script>
