<template>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-12 col-md-6 col-lg-4">
                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <h2 class="h4 mb-4">
                            {{ t('twoFactorChallenge.title') }}
                        </h2>

                        <div v-if="errorMessage" class="alert alert-danger">
                            {{ errorMessage }}
                        </div>

                        <form @submit.prevent="submit">
                            <div class="mb-3">
                                <label for="code" class="form-label">
                                    {{ t('twoFactorChallenge.authenticationCode') }}
                                </label>

                                <input
                                    id="code"
                                    v-model="form.code"
                                    type="text"
                                    inputmode="numeric"
                                    autocomplete="one-time-code"
                                    class="form-control"
                                    required
                                >
                            </div>

                            <div class="mb-3">
                                <label for="recovery_code" class="form-label">
                                    {{ t('twoFactorChallenge.recoveryCode') }}
                                </label>

                                <input
                                    id="recovery_code"
                                    v-model="form.recovery_code"
                                    type="text"
                                    class="form-control"
                                >

                                <div class="form-text">
                                    {{ t('twoFactorChallenge.helper') }}
                                </div>
                            </div>

                            <button
                                type="submit"
                                class="btn btn-primary w-100"
                                :disabled="loading"
                            >
                                <span v-if="loading">
                                    {{ t('twoFactorChallenge.verifying') }}
                                </span>

                                <span v-else>
                                    {{ t('common.verify') }}
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
import axios from 'axios'
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuth } from '../auth'

const router = useRouter()
const auth = useAuth()
const { t } = useI18n()

const loading = ref(false)
const errorMessage = ref('')

const form = reactive({
    code: '',
    recovery_code: '',
})

async function submit() {
    errorMessage.value = ''
    loading.value = true

    try {
        await axios.post('/two-factor-challenge', {
            code: form.code || undefined,
            recovery_code: form.recovery_code || undefined,
        })

        await auth.fetchUser()
        await router.push({ name: 'home' })
    } catch (error: any) {
        if (error?.response?.status === 422) {
            errorMessage.value = t('twoFactorChallenge.invalidCode')
        } else if (error?.response?.status === 429) {
            errorMessage.value = t('twoFactorChallenge.tooManyAttempts')
        } else {
            errorMessage.value = t('twoFactorChallenge.failed')
        }
    } finally {
        loading.value = false
    }
}
</script>
