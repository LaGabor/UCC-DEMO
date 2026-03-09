<template>
    <div class="container py-1">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-1">{{ t('twoFactorSettings.title') }}</h2>
                <p class="mb-0 text-muted">
                    {{ t('twoFactorSettings.subtitle') }}
                </p>
            </div>
        </div>

        <div v-if="message" class="alert alert-success">
            {{ message }}
        </div>

        <div v-if="errorMessage" class="alert alert-danger">
            {{ errorMessage }}
        </div>

        <div class="card shadow-sm">
            <div class="card-body">
                <template v-if="!isEnabled">
                    <p class="mb-3">
                        {{ t('twoFactorSettings.disabled') }}
                    </p>

                    <button class="btn btn-primary" :disabled="loading" @click="enableTwoFactor">
                        {{ t('twoFactorSettings.enableButton') }}
                    </button>
                </template>

                <template v-else>
                    <div class="mb-4">
                        <p class="mb-2">
                            {{ t('twoFactorSettings.qrInfo') }}
                        </p>

                        <div
                            v-if="qrSvg"
                            class="border rounded p-3 d-inline-block bg-white"
                            v-html="qrSvg"
                        />
                    </div>

                    <div class="mb-4">
                        <h5>{{ t('twoFactorSettings.recoveryCodes') }}</h5>

                        <ul v-if="recoveryCodes.length" class="list-group">
                            <li
                                v-for="code in recoveryCodes"
                                :key="code"
                                class="list-group-item font-monospace"
                            >
                                {{ code }}
                            </li>
                        </ul>
                    </div>

                    <div v-if="!isConfirmed" class="mb-4">
                        <h5>{{ t('twoFactorSettings.confirmSetup') }}</h5>

                        <form class="mt-3" @submit.prevent="confirmTwoFactor">
                            <div class="mb-3" style="max-width: 320px;">
                                <label for="code" class="form-label">
                                    {{ t('twoFactorSettings.authenticationCode') }}
                                </label>

                                <input
                                    id="code"
                                    v-model="confirmForm.code"
                                    type="text"
                                    inputmode="numeric"
                                    autocomplete="one-time-code"
                                    class="form-control"
                                    required
                                >
                            </div>

                            <button class="btn btn-success" :disabled="loading">
                                {{ t('twoFactorSettings.confirmButton') }}
                            </button>
                        </form>
                    </div>

                    <div class="d-flex gap-2 flex-wrap">
                        <button
                            class="btn btn-outline-primary"
                            :disabled="loading"
                            @click="regenerateRecoveryCodes"
                        >
                            {{ t('twoFactorSettings.regenerateRecoveryCodes') }}
                        </button>

                        <button
                            class="btn btn-outline-danger"
                            :disabled="loading"
                            @click="disableTwoFactor"
                        >
                            {{ t('twoFactorSettings.disableButton') }}
                        </button>
                    </div>
                </template>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import axios from 'axios'
import { computed, onMounted, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useAuth } from '../auth'

const { t } = useI18n()
const auth = useAuth()

const loading = ref(false)
const message = ref('')
const errorMessage = ref('')
const qrSvg = ref('')
const recoveryCodes = ref<string[]>([])

const confirmForm = reactive({
    code: '',
})

const user = computed(() => auth.state.user)

const isEnabled = computed(() =>
    !!user.value?.two_factor_secret || recoveryCodes.value.length > 0 || !!qrSvg.value
)

const isConfirmed = computed(() => !!user.value?.two_factor_confirmed_at)

function clearAlerts() {
    message.value = ''
    errorMessage.value = ''
}

async function refreshAuthUser() {
    await auth.fetchUser()
}

async function loadQrAndRecoveryCodes() {
    try {
        const [qrResponse, recoveryResponse] = await Promise.all([
            axios.get('/user/two-factor-qr-code'),
            axios.get('/user/two-factor-recovery-codes'),
        ])

        qrSvg.value = qrResponse.data.svg
        recoveryCodes.value = recoveryResponse.data.recovery_codes ?? []
    } catch {
        qrSvg.value = ''
        recoveryCodes.value = []
    }
}

async function enableTwoFactor() {
    clearAlerts()
    loading.value = true

    try {
        await axios.post('/user/two-factor-authentication')
        await refreshAuthUser()
        await loadQrAndRecoveryCodes()

        message.value = t('twoFactorSettings.enabledMessage')
    } catch {
        errorMessage.value = t('twoFactorSettings.enableFailed')
    } finally {
        loading.value = false
    }
}

async function confirmTwoFactor() {
    clearAlerts()
    loading.value = true

    try {
        await axios.post('/user/confirmed-two-factor-authentication', {
            code: confirmForm.code,
        })

        confirmForm.code = ''

        await refreshAuthUser()
        message.value = t('twoFactorSettings.confirmedMessage')
    } catch (error: any) {
        if (error?.response?.status === 422) {
            errorMessage.value = t('twoFactorSettings.invalidCode')
        } else {
            errorMessage.value = t('twoFactorSettings.confirmFailed')
        }
    } finally {
        loading.value = false
    }
}

async function regenerateRecoveryCodes() {
    clearAlerts()
    loading.value = true

    try {
        await axios.post('/user/two-factor-recovery-codes')
        await loadQrAndRecoveryCodes()

        message.value = t('twoFactorSettings.regeneratedMessage')
    } catch {
        errorMessage.value = t('twoFactorSettings.regenerateFailed')
    } finally {
        loading.value = false
    }
}

async function disableTwoFactor() {
    clearAlerts()
    loading.value = true

    try {
        await axios.delete('/user/two-factor-authentication')

        qrSvg.value = ''
        recoveryCodes.value = []

        await refreshAuthUser()

        message.value = t('twoFactorSettings.disabledMessage')
    } catch {
        errorMessage.value = t('twoFactorSettings.disableFailed')
    } finally {
        loading.value = false
    }
}

onMounted(async () => {
    await refreshAuthUser()

    if (user.value?.two_factor_secret) {
        await loadQrAndRecoveryCodes()
    }
})
</script>
