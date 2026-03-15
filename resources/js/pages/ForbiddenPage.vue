<template>
    <div class="forbidden-page d-flex align-items-center justify-content-center min-vh-50 py-5">
        <div class="text-center">
            <h1 class="forbidden-page__code display-1 fw-bold text-muted">403</h1>
            <p class="forbidden-page__message fs-5 text-secondary mb-0">
                {{ t('forbidden.message') }}
            </p>
        </div>
    </div>
</template>

<script setup lang="ts">
import { useI18n } from 'vue-i18n'

const { t } = useI18n()
</script>

<style scoped>
.forbidden-page {
    min-height: calc(100dvh - 200px);
}

.forbidden-page__code {
    letter-spacing: -0.02em;
    line-height: 1;
}

.forbidden-page__message {
    max-width: 24rem;
    margin-left: auto;
    margin-right: auto;
}
</style>
