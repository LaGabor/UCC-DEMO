<template>
    <div class="m-3 py-3">
        <div class="mb-4">
            <h2 class="mb-1">{{ isEditMode ? t('events.editTitle') : t('events.createTitle') }}</h2>
            <p class="mb-0 text-muted">{{ isEditMode ? t('events.editSubtitle') : t('events.createSubtitle') }}</p>
        </div>

        <div
            v-if="alert.message"
            :class="['alert alert-dismissible fade show', alert.type === 'success' ? 'alert-success' : 'alert-danger']"
            role="alert"
        >
            {{ alert.message }}
            <button type="button" class="btn-close" @click="clearAlert"></button>
        </div>

        <div class="card shadow-sm">
            <div class="card-body">
                <form @submit.prevent="submit">
                    <div class="mb-3">
                        <label for="title" class="form-label">{{ t('events.fields.title') }}</label>
                        <input
                            id="title"
                            v-model="form.title"
                            type="text"
                            class="form-control"
                            :required="!isEditMode"
                            :disabled="isEditMode"
                        >
                        <div v-if="fieldErrors.title" class="text-danger small mt-1">{{ fieldErrors.title }}</div>
                    </div>

                    <div class="mb-3">
                        <label for="occurs_at" class="form-label">{{ t('events.fields.occursAt') }}</label>
                        <DateTimePickerInput
                            id="occurs_at"
                            v-model="form.occurs_at"
                            :min="minDateTime"
                            :required="!isEditMode"
                            :disabled="isEditMode"
                        />
                        <div v-if="fieldErrors.occurs_at" class="text-danger small mt-1">{{ fieldErrors.occurs_at }}</div>
                    </div>

                    <div class="mb-4">
                        <label for="description" class="form-label">{{ t('events.fields.description') }}</label>
                        <textarea
                            id="description"
                            v-model="form.description"
                            class="form-control"
                            rows="5"
                        ></textarea>
                        <div v-if="fieldErrors.description" class="text-danger small mt-1">{{ fieldErrors.description }}</div>
                    </div>

                    <div class="d-flex gap-2">
                        <router-link :to="{ name: ROUTE_NAMES.EVENTS }" class="btn btn-outline-secondary">
                            {{ t('events.cancelButton') }}
                        </router-link>
                        <button
                            v-if="isEditMode"
                            type="button"
                            class="btn btn-outline-danger"
                            :disabled="loading || deleting"
                            @click="removeEvent"
                        >
                            {{ deleting ? t('events.deleting') : t('events.deleteButton') }}
                        </button>
                        <button type="submit" class="btn btn-primary" :disabled="loading">
                            {{ loading ? t('events.saving') : t('events.saveButton') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue'
import { onBeforeRouteLeave, useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import DateTimePickerInput from '../components/DateTimePickerInput.vue'
import { createEvent, deleteEvent, getEvent, updateEvent } from '../api/events'
import { ROUTE_NAMES } from '../router'
import { getApiErrorMessage } from '../utils/apiErrorMessage'

type AlertType = 'success' | 'error'

const route = useRoute()
const router = useRouter()
const { t } = useI18n()

const loading = ref(false)
const deleting = ref(false)
const alert = reactive<{ type: AlertType; message: string }>({
    type: 'success',
    message: '',
})
const form = reactive({
    title: '',
    occurs_at: '',
    description: '',
})
const fieldErrors = reactive({
    title: '',
    occurs_at: '',
    description: '',
})

const eventId = computed(() => Number(route.params.id))
const isEditMode = computed(() => Number.isInteger(eventId.value) && eventId.value > 0)

const minDateTime = computed(() => toDateTimeLocalValue(new Date()))

function setAlert(type: AlertType, message: string): void {
    alert.type = type
    alert.message = message
}

function clearAlert(): void {
    alert.message = ''
}

function toDateTimeLocalValue(date: Date): string {
    const offset = date.getTimezoneOffset() * 60000
    const localDate = new Date(date.getTime() - offset)
    return localDate.toISOString().slice(0, 16)
}

function validate(): boolean {
    if (isEditMode.value) {
        fieldErrors.title = ''
        fieldErrors.occurs_at = ''
        fieldErrors.description = ''
        return true
    }

    const title = form.title.trim()
    const occursAtDate = new Date(form.occurs_at)
    const now = Date.now()

    fieldErrors.title = title.length > 0 ? '' : t('events.validation.titleRequired')

    if (!form.occurs_at) {
        fieldErrors.occurs_at = t('events.validation.occursAtRequired')
    } else if (Number.isNaN(occursAtDate.getTime()) || occursAtDate.getTime() <= now) {
        fieldErrors.occurs_at = t('events.validation.occursAtFuture')
    } else {
        fieldErrors.occurs_at = ''
    }

    fieldErrors.description = ''

    return !fieldErrors.title && !fieldErrors.occurs_at && !fieldErrors.description
}

function extractFieldErrors(error: unknown): void {
    const payload = (error as { response?: { data?: { errors?: Record<string, string[]> } } })?.response?.data
    const errors = payload?.errors ?? {}

    fieldErrors.title = errors.title?.[0] ?? ''
    fieldErrors.occurs_at = errors.occurs_at?.[0] ?? ''
    fieldErrors.description = errors.description?.[0] ?? ''
}

async function loadEventForEdit(): Promise<void> {
    if (!isEditMode.value) {
        return
    }

    loading.value = true
    clearAlert()

    try {
        const event = await getEvent(eventId.value)
        form.title = event.title
        form.description = event.description ?? ''
        form.occurs_at = toDateTimeLocalValue(new Date(event.occurs_at))
    } catch (error) {
        setAlert('error', getApiErrorMessage(t, error, 'events.fetchFailed'))
    } finally {
        loading.value = false
    }
}

async function submit(): Promise<void> {
    clearAlert()
    fieldErrors.title = ''
    fieldErrors.occurs_at = ''
    fieldErrors.description = ''

    if (!validate()) {
        return
    }

    loading.value = true

    try {
        const payload = {
            title: form.title.trim(),
            description: form.description.trim() || null,
            occurs_at: new Date(form.occurs_at).toISOString(),
        }

        if (isEditMode.value) {
            await updateEvent(eventId.value, {
                description: payload.description,
            })
            sessionStorage.setItem('flash_events_success', t('events.updateSuccess'))
        } else {
            await createEvent(payload)
            sessionStorage.setItem('flash_events_success', t('events.createSuccess'))
        }

        await router.push({ name: ROUTE_NAMES.EVENTS })
    } catch (error) {
        extractFieldErrors(error)
        setAlert('error', getApiErrorMessage(t, error, 'events.saveFailed', {
            VALIDATION_ERROR: 'errors.validation',
        }))
    } finally {
        loading.value = false
    }
}

async function removeEvent(): Promise<void> {
    if (!isEditMode.value) {
        return
    }

    clearAlert()

    if (!window.confirm(t('events.confirmDelete'))) {
        return
    }

    deleting.value = true

    try {
        await deleteEvent(eventId.value)
        sessionStorage.setItem('flash_events_success', t('events.deleteSuccess'))
        await router.push({ name: ROUTE_NAMES.EVENTS })
    } catch (error) {
        setAlert('error', getApiErrorMessage(t, error, 'events.deleteFailed'))
    } finally {
        deleting.value = false
    }
}

function resetUiState(): void {
    clearAlert()
    loading.value = false
    deleting.value = false
}

onMounted(() => {
    void loadEventForEdit()
})

onBeforeRouteLeave(() => {
    resetUiState()
})

onBeforeUnmount(() => {
    resetUiState()
})
</script>

