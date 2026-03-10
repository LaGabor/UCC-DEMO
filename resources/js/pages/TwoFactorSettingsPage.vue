<template>
    <div class="container py-3">
        <div class="mb-4">
            <h2 class="mb-1">{{ t('twoFactorSettings.title') }}</h2>
            <p class="mb-0 text-muted">{{ t('twoFactorSettings.subtitle') }}</p>
        </div>

        <div v-if="isInitialLoading" class="alert alert-info">
            {{ t('twoFactorSettings.loadingState') }}
        </div>

        <div class="mb-3">
            <span class="fw-semibold me-2">{{ t('twoFactorSettings.statusLabel') }}</span>
            <span
                :class="[
                    'badge',
                    hasTwoFactorEnabled ? 'text-bg-success' : 'text-bg-secondary',
                ]"
            >
                {{ hasTwoFactorEnabled ? t('twoFactorSettings.statusEnabled') : t('twoFactorSettings.statusDisabled') }}
            </span>
            <span v-if="hasTwoFactorEnabled" class="ms-2 text-muted small">
                {{ isConfirmed ? t('twoFactorSettings.statusConfirmed') : t('twoFactorSettings.statusPendingConfirmation') }}
            </span>
        </div>

        <div v-if="hasUnsavedRecoveryCodes" class="alert alert-warning" role="alert">
            <div class="fw-semibold mb-1">{{ t('twoFactorSettings.recoveryCodesWarningTitle') }}</div>
            <div>{{ t('twoFactorSettings.recoveryCodesWarningBody') }}</div>
        </div>

        <div
            v-if="message"
            class="alert alert-success alert-dismissible fade show"
            role="alert"
        >
            {{ message }}
            <button type="button" class="btn-close" @click="message = ''"></button>
        </div>

        <div
            v-if="errorMessage"
            class="alert alert-danger alert-dismissible fade show"
            role="alert"
        >
            {{ errorMessage }}
            <button type="button" class="btn-close" @click="errorMessage = ''"></button>
        </div>

        <div class="card shadow-sm">
            <div class="card-body">
                <template v-if="!hasTwoFactorEnabled && !isInitialLoading">
                    <p class="mb-3">{{ t('twoFactorSettings.disabled') }}</p>
                    <button class="btn btn-primary" :disabled="loadingAction" @click="enableTwoFactor">
                        {{ t('twoFactorSettings.enableButton') }}
                    </button>
                </template>

                <template v-else-if="hasTwoFactorEnabled">
                    <div class="mb-4">
                        <h5 class="mb-2">{{ t('twoFactorSettings.qrTitle') }}</h5>
                        <p class="mb-2">{{ t('twoFactorSettings.qrInfo') }}</p>
                        <div
                            v-if="qrSvg"
                            class="border rounded p-3 d-inline-block bg-white"
                            v-html="qrSvg"
                        />
                    </div>

                    <div v-if="hasUnsavedRecoveryCodes" class="mb-4">
                        <h5>{{ t('twoFactorSettings.recoveryCodes') }}</h5>
                        <p class="text-muted mb-2">{{ t('twoFactorSettings.recoveryCodesHint') }}</p>

                        <div v-if="recoveryCodes.length" class="row g-2">
                            <div
                                v-for="code in recoveryCodes"
                                :key="code"
                                class="col-12 col-md-6"
                            >
                                <div class="border rounded px-3 py-2 font-monospace bg-light-subtle d-flex align-items-center justify-content-between gap-2">
                                    <span class="text-break">{{ code }}</span>
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-secondary"
                                        :title="t('twoFactorSettings.copyCode')"
                                        @click="copyRecoveryCode(code)"
                                    >
                                        <i :class="copiedCode === code ? 'bi bi-check2' : 'bi bi-copy'"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-muted small">
                            {{ t('twoFactorSettings.recoveryCodesHidden') }}
                        </div>
                    </div>

                    <div v-if="!isConfirmed" class="mb-4">
                        <h5>{{ t('twoFactorSettings.confirmSetup') }}</h5>
                        <form class="mt-3" @submit.prevent="confirmTwoFactor">
                            <div class="mb-3" style="max-width: 320px;">
                                <label for="code" class="form-label">{{ t('twoFactorSettings.authenticationCode') }}</label>
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

                            <button class="btn btn-success" :disabled="loadingAction">
                                {{ t('twoFactorSettings.confirmButton') }}
                            </button>
                        </form>
                    </div>

                    <div class="d-flex gap-2 flex-wrap">
                        <button
                            class="btn btn-outline-primary"
                            :disabled="loadingAction"
                            @click="openSensitiveActionModal('regenerate')"
                        >
                            {{ t('twoFactorSettings.regenerateRecoveryCodes') }}
                        </button>

                        <button
                            class="btn btn-outline-danger"
                            :disabled="loadingAction"
                            @click="openSensitiveActionModal('disable')"
                        >
                            {{ t('twoFactorSettings.disableButton') }}
                        </button>
                    </div>
                </template>
            </div>
        </div>

        <div
            v-if="showLeaveWarningModal"
            class="modal fade show d-block"
            tabindex="-1"
            role="dialog"
            aria-modal="true"
        >
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ t('twoFactorSettings.leaveWarningTitle') }}</h5>
                    </div>
                    <div class="modal-body">
                        <p class="mb-0">{{ t('twoFactorSettings.leaveWarningBody') }}</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" @click="cancelLeave">
                            {{ t('twoFactorSettings.leaveWarningStay') }}
                        </button>
                        <button type="button" class="btn btn-danger" @click="confirmLeave">
                            {{ t('twoFactorSettings.leaveWarningLeave') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div v-if="showLeaveWarningModal" class="modal-backdrop fade show"></div>

        <div
            v-if="showPasswordModal"
            class="modal fade show d-block"
            tabindex="-1"
            role="dialog"
            aria-modal="true"
        >
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ t('twoFactorSettings.passwordConfirmTitle') }}</h5>
                        <button type="button" class="btn-close" @click="closePasswordModal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-2">{{ t('twoFactorSettings.passwordConfirmBody') }}</p>
                        <label for="current-password" class="form-label">{{ t('twoFactorSettings.currentPasswordLabel') }}</label>
                        <div class="input-group">
                            <input
                                id="current-password"
                                v-model="sensitiveActionPassword"
                                class="form-control"
                                :type="showSensitiveActionPassword ? 'text' : 'password'"
                                autocomplete="current-password"
                                @keyup.enter="confirmSensitiveAction"
                            >
                            <button
                                type="button"
                                class="btn btn-outline-secondary"
                                :title="showSensitiveActionPassword ? t('twoFactorSettings.hidePassword') : t('twoFactorSettings.showPassword')"
                                @click="showSensitiveActionPassword = !showSensitiveActionPassword"
                            >
                                <i :class="showSensitiveActionPassword ? 'bi bi-eye-slash' : 'bi bi-eye'"></i>
                            </button>
                        </div>
                        <div v-if="passwordModalError" class="text-danger mt-2 small">
                            {{ passwordModalError }}
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" @click="closePasswordModal">
                            {{ t('twoFactorSettings.passwordConfirmCancel') }}
                        </button>
                        <button
                            type="button"
                            class="btn btn-primary"
                            :disabled="loadingAction"
                            @click="confirmSensitiveAction"
                        >
                            {{ t('twoFactorSettings.passwordConfirmContinue') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div v-if="showPasswordModal" class="modal-backdrop fade show"></div>
    </div>
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue'
import { onBeforeRouteLeave, useRouter, type RouteLocationRaw } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuth } from '../auth'
import {
    confirmTwoFactorRequest,
    disableTwoFactorRequest,
    enableTwoFactorRequest,
    getTwoFactorQrCode,
    getTwoFactorRecoveryCodes,
    regenerateTwoFactorRecoveryCodesRequest,
} from '../api/twoFactor'
import { getApiErrorMessage } from '../utils/apiErrorMessage'

const { t } = useI18n()
const auth = useAuth()
const router = useRouter()

const isInitialLoading = ref(true)
const loadingAction = ref(false)
const message = ref('')
const errorMessage = ref('')
const qrSvg = ref('')
const recoveryCodes = ref<string[]>([])
const hasTwoFactorEnabled = ref(false)
const hasUnsavedRecoveryCodes = ref(false)

const showLeaveWarningModal = ref(false)
const pendingNavigation = ref<RouteLocationRaw | null>(null)
const copiedCode = ref('')
const copiedCodeTimer = ref<number | null>(null)

type SensitiveAction = 'regenerate' | 'disable'
const showPasswordModal = ref(false)
const sensitiveAction = ref<SensitiveAction | null>(null)
const sensitiveActionPassword = ref('')
const showSensitiveActionPassword = ref(false)
const passwordModalError = ref('')

const confirmForm = reactive({
    code: '',
})

const user = computed(() => auth.state.user)
const isConfirmed = computed(() => !!user.value?.two_factor_confirmed_at)

function clearAlerts() {
    message.value = ''
    errorMessage.value = ''
}

function resetAlertState(): void {
    clearAlerts()
    passwordModalError.value = ''
}

async function refreshAuthUser() {
    await auth.fetchUser()
}

async function loadQrState(): Promise<void> {
    try {
        qrSvg.value = await getTwoFactorQrCode()
        hasTwoFactorEnabled.value = true
    } catch {
        qrSvg.value = ''
        hasTwoFactorEnabled.value = !!user.value?.two_factor_confirmed_at
    }
}

async function loadRecoveryCodesForDisplay(): Promise<void> {
    try {
        recoveryCodes.value = await getTwoFactorRecoveryCodes()
        hasUnsavedRecoveryCodes.value = recoveryCodes.value.length > 0
    } catch {
        recoveryCodes.value = []
        hasUnsavedRecoveryCodes.value = false
    }
}

function clearRecoveryCodeWarningState(): void {
    hasUnsavedRecoveryCodes.value = false
    recoveryCodes.value = []
}

async function initializePage(): Promise<void> {
    isInitialLoading.value = true
    clearAlerts()

    await refreshAuthUser()
    await loadQrState()

    recoveryCodes.value = []
    hasUnsavedRecoveryCodes.value = false
    isInitialLoading.value = false
}

async function enableTwoFactor() {
    clearAlerts()
    loadingAction.value = true

    try {
        await enableTwoFactorRequest()
        await refreshAuthUser()
        await loadQrState()
        await loadRecoveryCodesForDisplay()

        message.value = t('twoFactorSettings.enabledMessage')
    } catch {
        errorMessage.value = t('twoFactorSettings.enableFailed')
    } finally {
        loadingAction.value = false
    }
}

async function confirmTwoFactor() {
    clearAlerts()
    loadingAction.value = true

    try {
        await confirmTwoFactorRequest(confirmForm.code)
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
        loadingAction.value = false
    }
}

async function regenerateRecoveryCodes() {
    clearAlerts()
    loadingAction.value = true

    try {
        await regenerateTwoFactorRecoveryCodesRequest(sensitiveActionPassword.value)
        await loadRecoveryCodesForDisplay()

        message.value = t('twoFactorSettings.regeneratedMessage')
    } catch (error) {
        const resolvedMessage = getApiErrorMessage(
            t,
            error,
            'twoFactorSettings.regenerateFailed'
        )
        passwordModalError.value = resolvedMessage
        errorMessage.value = resolvedMessage
        throw error
    } finally {
        loadingAction.value = false
    }
}

async function disableTwoFactor() {
    clearAlerts()
    loadingAction.value = true

    try {
        await disableTwoFactorRequest(sensitiveActionPassword.value)
        await refreshAuthUser()

        qrSvg.value = ''
        hasTwoFactorEnabled.value = false
        clearRecoveryCodeWarningState()

        message.value = t('twoFactorSettings.disabledMessage')
    } catch (error) {
        const resolvedMessage = getApiErrorMessage(
            t,
            error,
            'twoFactorSettings.disableFailed'
        )
        passwordModalError.value = resolvedMessage
        errorMessage.value = resolvedMessage
        throw error
    } finally {
        loadingAction.value = false
    }
}

async function copyRecoveryCode(code: string): Promise<void> {
    try {
        await navigator.clipboard.writeText(code)
        copiedCode.value = code

        if (copiedCodeTimer.value !== null) {
            window.clearTimeout(copiedCodeTimer.value)
        }

        copiedCodeTimer.value = window.setTimeout(() => {
            copiedCode.value = ''
            copiedCodeTimer.value = null
        }, 1500)
    } catch {
        errorMessage.value = t('twoFactorSettings.copyFailed')
    }
}

function openSensitiveActionModal(action: SensitiveAction): void {
    clearAlerts()
    sensitiveAction.value = action
    sensitiveActionPassword.value = ''
    showSensitiveActionPassword.value = false
    passwordModalError.value = ''
    showPasswordModal.value = true
}

function closePasswordModal(): void {
    showPasswordModal.value = false
    sensitiveAction.value = null
    sensitiveActionPassword.value = ''
    showSensitiveActionPassword.value = false
    passwordModalError.value = ''
}

async function confirmSensitiveAction(): Promise<void> {
    if (!sensitiveActionPassword.value.trim()) {
        passwordModalError.value = t('twoFactorSettings.currentPasswordRequired')
        return
    }

    passwordModalError.value = ''

    try {
        if (sensitiveAction.value === 'regenerate') {
            await regenerateRecoveryCodes()
        }

        if (sensitiveAction.value === 'disable') {
            await disableTwoFactor()
        }

        closePasswordModal()
    } catch {
        // Keep modal open to allow correcting password.
    }
}

function beforeUnloadHandler(event: BeforeUnloadEvent): void {
    if (!hasUnsavedRecoveryCodes.value) {
        return
    }

    event.preventDefault()
    event.returnValue = ''
}

function cancelLeave(): void {
    showLeaveWarningModal.value = false
    pendingNavigation.value = null
}

async function confirmLeave(): Promise<void> {
    showLeaveWarningModal.value = false
    hasUnsavedRecoveryCodes.value = false

    const targetRoute = pendingNavigation.value
    pendingNavigation.value = null

    if (targetRoute) {
        await router.push(targetRoute)
    }
}

onBeforeRouteLeave((to) => {
    if (!hasUnsavedRecoveryCodes.value) {
        resetAlertState()
        return true
    }

    pendingNavigation.value = to
    showLeaveWarningModal.value = true
    return false
})

onMounted(async () => {
    window.addEventListener('beforeunload', beforeUnloadHandler)
    await initializePage()
})

onBeforeUnmount(() => {
    window.removeEventListener('beforeunload', beforeUnloadHandler)
    resetAlertState()
    if (copiedCodeTimer.value !== null) {
        window.clearTimeout(copiedCodeTimer.value)
    }
})
</script>

<style scoped>
.modal.d-block {
    z-index: 1055;
}

.modal-backdrop.show {
    z-index: 1050;
}
</style>
