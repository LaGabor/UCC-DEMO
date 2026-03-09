<template>
    <footer class="bg-white border-top py-3">
        <div class="container-fluid d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
            <div class="text-muted small">
                © {{ currentYear }} {{ t('app.projectName') }}
            </div>

            <div class="d-flex align-items-center gap-3">
                <a
                    href="https://github.com/your-project-url"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="text-decoration-none text-dark"
                    :aria-label="t('footer.github')"
                >
                    <i class="bi bi-github fs-5"></i>
                </a>

                <a
                    href="https://www.linkedin.com/in/your-linkedin-profile"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="text-decoration-none text-primary"
                    :aria-label="t('footer.linkedin')"
                >
                    <i class="bi bi-linkedin fs-5"></i>
                </a>
            </div>
        </div>
    </footer>
</template>

<script setup lang="ts">
import { useI18n } from 'vue-i18n'

const currentYear = new Date().getFullYear()
const { t } = useI18n()
</script>
