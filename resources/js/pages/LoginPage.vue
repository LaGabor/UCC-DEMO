<template>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-12 col-md-6 col-lg-4">
                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <h2 class="h4 mb-4">
                            {{ t('login.title') }}
                        </h2>

                        <div v-if="errorMessage" class="alert alert-danger">
                            {{ errorMessage }}
                        </div>

                        <div
                            v-if="successMessage"
                            class="alert alert-success alert-dismissible fade show"
                            role="alert"
                        >
                            {{ successMessage }}
                            <button type="button" class="btn-close" @click="clearSuccess"></button>
                        </div>

                        <form @submit.prevent="submit">
                            <div class="mb-3">
                                <label for="email" class="form-label">
                                    {{ t('login.email') }}
                                </label>

                                <input
                                    id="email"
                                    v-model="form.email"
                                    type="email"
                                    class="form-control"
                                    autocomplete="username"
                                    required
                                >
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">
                                    {{ t('login.password') }}
                                </label>

                                <div class="input-group">
                                    <input
                                        id="password"
                                        v-model="form.password"
                                        :type="showPassword ? 'text' : 'password'"
                                        class="form-control"
                                        autocomplete="current-password"
                                        required
                                    >

                                    <button
                                        type="button"
                                        class="btn btn-outline-primary"
                                        @click="showPassword = !showPassword"
                                    >
                                        <i v-if="showPassword" class="bi bi-eye-slash"></i>
                                        <i v-else class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="form-check mb-3">
                                <input
                                    id="remember"
                                    v-model="form.remember"
                                    type="checkbox"
                                    class="form-check-input"
                                >

                                <label for="remember" class="form-check-label">
                                    {{ t('login.rememberMe') }}
                                </label>
                            </div>

                            <div class="mb-3 text-end">
                                <router-link :to="{ name: 'forgot-password' }" class="small">
                                    {{ t('login.forgotPassword') }}
                                </router-link>
                            </div>

                            <button
                                type="submit"
                                class="btn btn-primary w-100"
                                :disabled="loading"
                            >
                                <span v-if="loading">
                                    {{ t('login.loggingIn') }}
                                </span>

                                <span v-else>
                                    {{ t('common.login') }}
                                </span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { onBeforeUnmount, onMounted, reactive, ref } from 'vue'
import { onBeforeRouteLeave, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuth } from '../auth'
import { getCsrfCookie, loginRequest } from '../api/auth'

const router = useRouter()
const auth = useAuth()
const { t } = useI18n()

const showPassword = ref(false)
const loading = ref(false)
const errorMessage = ref('')
const successMessage = ref('')

const form = reactive({
    email: '',
    password: '',
    remember: false,
})

async function submit() {
    errorMessage.value = ''
    successMessage.value = ''
    loading.value = true

    form.email = form.email.trim()
    form.password = form.password.trim()

    try {
        await getCsrfCookie()
        await loginRequest({
            email: form.email,
            password: form.password,
            remember: form.remember,
        })

        const user = await auth.fetchUser().catch(() => null)

        if (user) {
            await router.push({ name: 'home' })
        } else {
            await router.push({ name: 'two-factor-challenge' })
        }
    } catch (error: any) {
        if (error?.response?.status === 422) {
            errorMessage.value = t('login.invalidCredentials')
        } else if (error?.response?.status === 429) {
            errorMessage.value = t('login.tooManyAttempts')
        } else {
            errorMessage.value = t('login.failed')
        }
    } finally {
        loading.value = false
    }
}

function clearSuccess(): void {
    successMessage.value = ''
    sessionStorage.removeItem('flash_login_success')
}

function resetUiState(): void {
    errorMessage.value = ''
    successMessage.value = ''
    loading.value = false
}

onMounted(() => {
    const flashMessage = sessionStorage.getItem('flash_login_success')

    if (!flashMessage) {
        return
    }

    successMessage.value = flashMessage
    sessionStorage.removeItem('flash_login_success')
})

onBeforeRouteLeave(() => {
    resetUiState()
})

onBeforeUnmount(() => {
    resetUiState()
})
</script>
