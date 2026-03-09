<template>
    <div>
        <h1 class="h3 mb-3">{{ t('home.title') }}</h1>
        <p class="mb-0">{{ t('home.description') }}</p>
    </div>
</template>

<script setup lang="ts">
import { useI18n } from 'vue-i18n'

const { t } = useI18n()
</script>
