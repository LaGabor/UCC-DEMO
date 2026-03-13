<template>
    <div class="m-3 py-3">
        <div class="mb-4">
            <h2 class="mb-1">{{ t('home.title') }}</h2>
            <p class="mb-0 text-muted">{{ t('home.description') }}</p>
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
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from 'vue'
import { onBeforeRouteLeave } from 'vue-router'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()
const flashMessage = ref('')

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
