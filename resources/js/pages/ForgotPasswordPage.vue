<template>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-12 col-md-7 col-lg-5">
                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <h2 class="h4 mb-3">{{ t('forgotPassword.title') }}</h2>
                        <p class="text-muted mb-4">{{ t('forgotPassword.subtitle') }}</p>

                        <div
                            v-if="alert.message"
                            :class="['alert alert-dismissible fade show', alert.type === 'success' ? 'alert-success' : 'alert-danger']"
                            role="alert"
                        >
                            {{ alert.message }}
                            <button type="button" class="btn-close" @click="clearAlert"></button>
                        </div>

                        <form @submit.prevent="submit">
                            <div class="mb-3">
                                <label for="email" class="form-label">{{ t('forgotPassword.email') }}</label>
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

                            <button
                                v-if="!success"
                                type="submit"
                                class="btn btn-primary w-100"
                                :disabled="loading"
                            >
                                {{ loading ? t('forgotPassword.submitting') : t('forgotPassword.submit') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { onBeforeUnmount, reactive, ref, watch } from 'vue'
import { onBeforeRouteLeave } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { getApiErrorMessage } from '../utils/apiErrorMessage'
import { createPasswordResetRequest } from '../api/passwordReset'

type AlertType = 'success' | 'error'

const { t } = useI18n()
const loading = ref(false)
const success = ref(false)

const form = reactive({
    email: '',
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
    success.value = false
    loading.value = false
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

async function submit(): Promise<void> {
    clearAlert()

    if (!validate()) {
        return
    }

    loading.value = true

    try {
        await createPasswordResetRequest(form.email)

        success.value = true
        sessionStorage.setItem('flash_login_success', t('forgotPassword.success'))
        window.location.href = '/login'
    } catch (error: any) {
        setAlert(
            'error',
            getApiErrorMessage(t, error, 'forgotPassword.error', {
                PASSWORD_RESET_TOKEN_ACTIVE: 'forgotPassword.errors.activeRequestExists',
                VALIDATION_ERROR: 'errors.validation',
            })
        )
    } finally {
        loading.value = false
    }
}

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
