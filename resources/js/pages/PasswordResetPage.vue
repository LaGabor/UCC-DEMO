<template>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-12 col-md-8 col-lg-6">
                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <h2 class="h4 mb-3">{{ t('passwordReset.title') }}</h2>
                        <p v-if="!showBackToLoginOnly" class="text-muted mb-4">{{ t('passwordReset.subtitle') }}</p>
                        
                        <div
                            v-if="alert.message"
                            :class="['alert alert-dismissible fade show', alert.type === 'success' ? 'alert-success' : 'alert-danger']"
                            role="alert"
                        >
                            {{ alert.message }}
                            <button type="button" class="btn-close" @click="clearAlert"></button>
                        </div>

                        <div v-if="showBackToLoginOnly" class="text-center mb-4">
                            <router-link :to="{ name: ROUTE_NAMES.LOGIN }">
                                {{ t('common.backToLogin') }}
                            </router-link>
                        </div>

                        <div v-if="payloadLoaded" class="mb-3">
                            <label class="form-label">{{ t('passwordReset.emailLabel') }}</label>
                            <input class="form-control" :value="payloadEmail" type="text" readonly>
                        </div>

                        <form v-if="payloadLoaded" @submit.prevent="submit">
                            <div class="mb-3">
                                <label for="password" class="form-label">{{ t('passwordReset.password') }}</label>
                                <div class="input-group">
                                    <input
                                        id="password"
                                        v-model="form.password"
                                        :type="showPassword ? 'text' : 'password'"
                                        class="form-control"
                                        autocomplete="new-password"
                                        required
                                    >
                                    <button type="button" class="btn btn-outline-secondary" @click="showPassword = !showPassword">
                                        <i :class="showPassword ? 'bi bi-eye-slash' : 'bi bi-eye'"></i>
                                    </button>
                                </div>
                                <div v-if="fieldErrors.password" class="text-danger small mt-1">{{ fieldErrors.password }}</div>
                            </div>

                            <div class="mb-3">
                                <label for="password_confirmation" class="form-label">{{ t('passwordReset.confirmPassword') }}</label>
                                <div class="input-group">
                                    <input
                                        id="password_confirmation"
                                        v-model="form.password_confirmation"
                                        :type="showConfirmPassword ? 'text' : 'password'"
                                        class="form-control"
                                        autocomplete="new-password"
                                        required
                                    >
                                    <button type="button" class="btn btn-outline-secondary" @click="showConfirmPassword = !showConfirmPassword">
                                        <i :class="showConfirmPassword ? 'bi bi-eye-slash' : 'bi bi-eye'"></i>
                                    </button>
                                </div>
                                <div v-if="fieldErrors.password_confirmation" class="text-danger small mt-1">
                                    {{ fieldErrors.password_confirmation }}
                                </div>
                            </div>

                            <button
                                v-if="!success"
                                type="submit"
                                class="btn btn-primary w-100"
                                :disabled="loading"
                            >
                                {{ loading ? t('passwordReset.submitting') : t('passwordReset.submit') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue'
import { onBeforeRouteLeave, useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { getApiErrorMessage } from '../utils/apiErrorMessage'
import { completePasswordReset, getPasswordResetByToken } from '../api/passwordReset'
import { ROUTE_NAMES } from '../router'

type AlertType = 'success' | 'error'

const route = useRoute()
const { t } = useI18n()

const loading = ref(false)
const payloadLoaded = ref(false)
const success = ref(false)
const showPassword = ref(false)
const showConfirmPassword = ref(false)
const payloadEmail = ref('')
const showBackToLoginOnly = ref(false)

const form = reactive({
    password: '',
    password_confirmation: '',
})

const fieldErrors = reactive({
    password: '',
    password_confirmation: '',
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
    payloadLoaded.value = false
    payloadEmail.value = ''
    showBackToLoginOnly.value = false
    form.password = ''
    form.password_confirmation = ''
    fieldErrors.password = ''
    fieldErrors.password_confirmation = ''
}

function validate(): boolean {
    const passwordValue = form.password
    const passwordConfirmationValue = form.password_confirmation

    const passwordRegex = /^(?=.*[A-Za-z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$/
    if (!passwordRegex.test(passwordValue)) {
        fieldErrors.password = t('validation.passwordRules')
    } else {
        fieldErrors.password = ''
    }

    if (passwordValue !== passwordConfirmationValue) {
        fieldErrors.password_confirmation = t('validation.passwordMismatch')
    } else {
        fieldErrors.password_confirmation = ''
    }

    return !fieldErrors.password && !fieldErrors.password_confirmation
}

async function loadPayload(): Promise<void> {
    const token = String(route.params.token || '').trim()

    if (!token) {
        showBackToLoginOnly.value = true
        setAlert('error', t('passwordReset.invalidToken'))
        return
    }

    try {
        const payload = await getPasswordResetByToken(token)
        payloadEmail.value = String(payload.email || '')
        payloadLoaded.value = true
    } catch (error: any) {
        showBackToLoginOnly.value = true
        setAlert(
            'error',
            getApiErrorMessage(t, error, 'passwordReset.invalidToken', {
                PASSWORD_RESET_TOKEN_INVALID: 'passwordReset.invalidToken',
                PASSWORD_RESET_TOKEN_EXPIRED: 'passwordReset.expiredToken',
                VALIDATION_ERROR: 'errors.validation',
            })
        )
    }
}

async function submit(): Promise<void> {
    clearAlert()
    if (!validate()) {
        return
    }

    const token = String(route.params.token || '').trim()
    if (!token) {
        showBackToLoginOnly.value = true
        setAlert('error', t('passwordReset.invalidToken'))
        return
    }

    loading.value = true

    try {
        await completePasswordReset(token, {
            password: form.password,
            password_confirmation: form.password_confirmation,
        })

        success.value = true
        sessionStorage.setItem('flash_login_success', t('passwordReset.success'))
        window.location.href = '/login'
    } catch (error: any) {
        setAlert(
            'error',
            getApiErrorMessage(t, error, 'passwordReset.error', {
                PASSWORD_RESET_TOKEN_INVALID: 'passwordReset.invalidToken',
                PASSWORD_RESET_TOKEN_EXPIRED: 'passwordReset.expiredToken',
                USER_NOT_FOUND: 'passwordReset.error',
                VALIDATION_ERROR: 'errors.validation',
            })
        )
    } finally {
        loading.value = false
    }
}

onMounted(() => {
    void loadPayload()
})

watch(
    () => form.password,
    () => {
        validate()
    }
)

watch(
    () => form.password_confirmation,
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
