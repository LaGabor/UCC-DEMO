<template>
    <footer class="bg-white border-top py-3">
        <div class="container-fluid d-flex flex-column flex-md-row align-items-center gap-2">
            <div class="text-muted small">
                © {{ currentYear }} {{ t('app.projectName') }}
                <span class="text-muted ms-3 me-3">Lauter Gábor</span>
            </div>

                <a
                    href="https://github.com/LaGabor?tab=repositories"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="text-decoration-none text-dark"
                    :aria-label="t('footer.github')"
                >
                    <i class="bi bi-github fs-5"></i>
                </a>

                <a
                    href="https://www.linkedin.com/in/g%C3%A1bor-lauter/"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="text-decoration-none text-primary"
                    :aria-label="t('footer.linkedin')"
                >
                    <i class="bi bi-linkedin fs-5"></i>
                </a>
        </div>
    </footer>
</template>

<script setup lang="ts">
import { useI18n } from 'vue-i18n'

const currentYear = new Date().getFullYear()
const { t } = useI18n()
</script>
