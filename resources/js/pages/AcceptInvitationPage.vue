<template>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-12 col-md-9 col-lg-7">
                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <h2 class="h4 mb-3">{{ t('acceptInvitation.title') }}</h2>
                        <p v-if="!showBackToLoginOnly" class="text-muted mb-4">{{ t('acceptInvitation.subtitle') }}</p>

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

                        <form v-if="payloadLoaded" @submit.prevent="submit">
                            <div class="mb-3">
                                <label class="form-label">{{ t('acceptInvitation.email') }}</label>
                                <input class="form-control" :value="payloadEmail" readonly>
                            </div>

                            <div class="mb-3">
                                <label for="name" class="form-label">{{ t('acceptInvitation.name') }}</label>
                                <input id="name" v-model="form.name" type="text" class="form-control" required>
                                <div v-if="fieldErrors.name" class="text-danger small mt-1">{{ fieldErrors.name }}</div>
                            </div>

                            <div class="mb-3">
                                <label for="preferred_locale" class="form-label">{{ t('acceptInvitation.preferredLocale') }}</label>
                                <select id="preferred_locale" v-model="form.preferred_locale" class="form-select">
                                    <option :value="Language.EN">{{ t('common.languages.en') }}</option>
                                    <option :value="Language.HU">{{ t('common.languages.hu') }}</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">{{ t('acceptInvitation.password') }}</label>
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
                                <label for="password_confirmation" class="form-label">{{ t('acceptInvitation.confirmPassword') }}</label>
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
                                {{ loading ? t('acceptInvitation.submitting') : t('acceptInvitation.submit') }}
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
import { onBeforeRouteLeave, useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { Language } from '../types/enums'
import { getApiErrorMessage } from '../utils/apiErrorMessage'
import { ROUTE_NAMES } from '../router'
import { acceptInvitation, getInvitationByToken } from '../api/invitations'

type AlertType = 'success' | 'error'

const route = useRoute()
const router = useRouter()
const { t } = useI18n()

const loading = ref(false)
const payloadLoaded = ref(false)
const success = ref(false)
const showPassword = ref(false)
const showConfirmPassword = ref(false)
const payloadEmail = ref('')
const showBackToLoginOnly = ref(false)

const form = reactive({
    name: '',
    preferred_locale: Language.EN as Language,
    password: '',
    password_confirmation: '',
})

const fieldErrors = reactive({
    name: '',
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
    form.name = ''
    form.preferred_locale = Language.EN
    form.password = ''
    form.password_confirmation = ''
    fieldErrors.name = ''
    fieldErrors.password = ''
    fieldErrors.password_confirmation = ''
}

function validate(): boolean {
    const nameValue = form.name
    const passwordValue = form.password
    const passwordConfirmationValue = form.password_confirmation

    if (nameValue.trim().length < 4) {
        fieldErrors.name = t('validation.nameMin')
    } else {
        fieldErrors.name = ''
    }

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

    return !fieldErrors.name && !fieldErrors.password && !fieldErrors.password_confirmation
}

async function loadPayload(): Promise<void> {
    const token = String(route.params.token || '').trim()

    if (!token) {
        showBackToLoginOnly.value = true
        setAlert('error', t('acceptInvitation.invalidToken'))
        return
    }

    try {
        const payload = await getInvitationByToken(token)
        payloadEmail.value = String(payload.email || '')
        payloadLoaded.value = true
    } catch (error: any) {
        showBackToLoginOnly.value = true
        setAlert(
            'error',
            getApiErrorMessage(t, error, 'acceptInvitation.invalidToken', {
                INVITATION_TOKEN_INVALID: 'acceptInvitation.invalidToken',
                INVITATION_TOKEN_EXPIRED: 'acceptInvitation.expiredToken',
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
        setAlert('error', t('acceptInvitation.invalidToken'))
        return
    }

    loading.value = true

    try {
        await acceptInvitation(token, {
            name: form.name,
            preferred_locale: form.preferred_locale,
            password: form.password,
            password_confirmation: form.password_confirmation,
        })

        sessionStorage.setItem('flash_login_success', t('acceptInvitation.success'))
        await router.push({ name: ROUTE_NAMES.LOGIN })
    } catch (error: any) {
        setAlert(
            'error',
            getApiErrorMessage(t, error, 'acceptInvitation.error', {
                INVITATION_TOKEN_INVALID: 'acceptInvitation.invalidToken',
                INVITATION_TOKEN_EXPIRED: 'acceptInvitation.expiredToken',
                INVITATION_USER_NOT_PENDING: 'acceptInvitation.error',
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
    () => form.name,
    () => {
        validate()
    }
)

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
