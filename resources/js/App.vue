<template>
    <component :is="layoutComponent">
        <router-view />
    </component>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import AppLayout from './layouts/AppLayout.vue'
import GuestLayout from './layouts/GuestLayout.vue'

const route = useRoute()

const layoutComponent = computed(() => {
    return route.meta.layout === 'app' ? AppLayout : GuestLayout
})
</script>
