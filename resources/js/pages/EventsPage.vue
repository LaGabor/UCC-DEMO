<template>
    <div class="m-3 py-3">
        <div class="mb-4">
            <h2 class="mb-1">{{ t('events.title') }}</h2>
            <p class="mb-0 text-muted">{{ t('events.subtitle') }}</p>
        </div>

        <div
            v-if="alert.message"
            :class="['alert alert-dismissible fade show', alert.type === 'success' ? 'alert-success' : 'alert-danger']"
            role="alert"
        >
            {{ alert.message }}
            <button type="button" class="btn-close" @click="clearAlert"></button>
        </div>

        <div class="card shadow-sm mb-3">
            <div class="card-body event-list-card-body">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <h5 class="mb-0">{{ t('events.listTitle') }}</h5>
                    <router-link
                        :to="{ name: ROUTE_NAMES.EVENT_CREATE }"
                        class="btn btn-primary"
                    >
                        <i class="bi bi-plus-lg me-1"></i>
                        {{ t('events.newEvent') }}
                    </router-link>
                </div>

                <form class="row g-3 mb-3 event-filters-form" @submit.prevent="applyFilters">
                    <div class="col-12 col-md-4">
                        <label for="search" class="form-label">{{ t('events.searchLabel') }}</label>
                        <input
                            id="search"
                            v-model="filters.q"
                            type="text"
                            class="form-control"
                            :placeholder="t('events.searchPlaceholder')"
                        >
                    </div>
                    <div class="col-12 col-md-3">
                        <label for="from" class="form-label">{{ t('events.fromLabel') }}</label>
                        <DateTimePickerInput
                            id="from"
                            v-model="filters.from"
                        />
                    </div>
                    <div class="col-12 col-md-3">
                        <label for="to" class="form-label">{{ t('events.toLabel') }}</label>
                        <DateTimePickerInput
                            id="to"
                            v-model="filters.to"
                        />
                    </div>
                    <div class="col-12 col-md-2 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-outline-primary w-100">
                            {{ t('events.searchButton') }}
                        </button>
                        <button type="button" class="btn btn-outline-secondary w-100" @click="resetFilters">
                            {{ t('events.resetButton') }}
                        </button>
                    </div>
                </form>

                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
                    <div class="d-flex align-items-center gap-2">
                        <label for="per-page" class="form-label mb-0">{{ t('events.pagination.perPage') }}</label>
                        <select
                            id="per-page"
                            v-model.number="query.per_page"
                            class="form-select form-select-sm"
                            style="width: 90px;"
                            @change="handlePerPageChange"
                        >
                            <option v-for="size in perPageOptions" :key="size" :value="size">{{ size }}</option>
                        </select>
                    </div>
                    <small class="text-muted">
                        {{ t('events.pagination.pageStatus', { current: meta.current_page, total: meta.last_page }) }}
                    </small>
                </div>

                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>
                                    <button class="table-sort-btn" @click="sortBy('title')">
                                        {{ t('events.columns.title') }}
                                        <i :class="sortIconClass('title')"></i>
                                    </button>
                                </th>
                                <th>
                                    <button class="table-sort-btn" @click="sortBy('description')">
                                        {{ t('events.columns.description') }}
                                        <i :class="sortIconClass('description')"></i>
                                    </button>
                                </th>
                                <th>
                                    <button class="table-sort-btn" @click="sortBy('occurs_at')">
                                        {{ t('events.columns.occursAt') }}
                                        <i :class="sortIconClass('occurs_at')"></i>
                                    </button>
                                </th>
                                <th class="text-end">{{ t('events.columns.operations') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="loading">
                                <td colspan="4" class="text-center py-4 text-muted">
                                    {{ t('events.loading') }}
                                </td>
                            </tr>
                            <tr v-else-if="events.length === 0">
                                <td colspan="4" class="text-center py-4 text-muted">
                                    {{ t('events.empty') }}
                                </td>
                            </tr>
                            <tr v-for="event in events" v-else :key="event.id">
                                <td>{{ event.title }}</td>
                                <td>{{ previewDescription(event.description) }}</td>
                                <td>{{ formatDate(event.occurs_at) }}</td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-1">
                                        <router-link
                                            :to="{ name: ROUTE_NAMES.EVENT_EDIT, params: { id: event.id } }"
                                            class="btn btn-sm btn-outline-primary action-btn"
                                            :title="t('events.operations.edit')"
                                        >
                                            <i class="bi bi-pencil-square"></i>
                                        </router-link>
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-danger action-btn"
                                            :title="t('events.operations.delete')"
                                            :disabled="deletingEventId === event.id"
                                            @click="removeEvent(event.id)"
                                        >
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <nav v-if="meta.last_page > 1" class="mt-3 d-flex flex-wrap align-items-center justify-content-between gap-3" aria-label="Events pagination">
                    <ul class="pagination mb-0">
                        <li class="page-item" :class="{ disabled: meta.current_page <= 1 || loading }">
                            <button class="page-link" @click="goToPage(meta.current_page - 1)">
                                {{ t('events.pagination.previous') }}
                            </button>
                        </li>

                        <li
                            v-for="page in visiblePages"
                            :key="page"
                            class="page-item"
                            :class="{ active: meta.current_page === page, disabled: loading }"
                        >
                            <button class="page-link" @click="goToPage(page)">
                                {{ page }}
                            </button>
                        </li>

                        <li class="page-item" :class="{ disabled: meta.current_page >= meta.last_page || loading }">
                            <button class="page-link" @click="goToPage(meta.current_page + 1)">
                                {{ t('events.pagination.next') }}
                            </button>
                        </li>
                    </ul>

                    <div class="d-flex align-items-center gap-2">
                        <label for="page-select" class="form-label mb-0">{{ t('events.pagination.goToPage') }}</label>
                        <select
                            id="page-select"
                            :value="meta.current_page"
                            class="form-select form-select-sm"
                            style="width: 110px;"
                            :disabled="loading"
                            @change="handlePageSelectChange"
                        >
                            <option v-for="page in pageOptions" :key="page" :value="page">
                                {{ page }}
                            </option>
                        </select>
                    </div>
                </nav>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue'
import { onBeforeRouteLeave } from 'vue-router'
import { useI18n } from 'vue-i18n'
import DateTimePickerInput from '../components/DateTimePickerInput.vue'
import { deleteEvent, fetchEvents } from '../api/events'
import type { EventFilters, EventItem, EventListMeta, EventListQuery } from '../types/events'
import { ROUTE_NAMES } from '../router'
import { getApiErrorMessage } from '../utils/apiErrorMessage'

type AlertType = 'success' | 'error'
type SortableColumn = EventListQuery['sort_by']

const { t } = useI18n()
const perPageOptions = [10, 25, 50, 100]

const loading = ref(false)
const deletingEventId = ref<number | null>(null)
const events = ref<EventItem[]>([])
const meta = reactive<EventListMeta>({
    current_page: 1,
    last_page: 1,
    per_page: 10,
    total: 0,
})
const query = reactive<EventListQuery>({
    page: 1,
    per_page: 10,
    sort_by: 'occurs_at',
    sort_dir: 'asc',
})
const filters = reactive<EventFilters>({
    q: '',
    from: '',
    to: '',
})
const alert = reactive<{ type: AlertType; message: string }>({
    type: 'success',
    message: '',
})

function setAlert(type: AlertType, message: string): void {
    alert.type = type
    alert.message = message
}

function clearAlert(): void {
    alert.message = ''
}

function toApiDate(value: string): string | undefined {
    if (!value) {
        return undefined
    }

    const date = new Date(value)
    if (Number.isNaN(date.getTime())) {
        return undefined
    }

    return date.toISOString()
}

async function loadEvents(): Promise<void> {
    loading.value = true

    try {
        const payload = await fetchEvents({
            page: query.page,
            per_page: query.per_page,
            sort_by: query.sort_by,
            sort_dir: query.sort_dir,
            q: filters.q.trim() || undefined,
            from: toApiDate(filters.from),
            to: toApiDate(filters.to),
        })

        events.value = payload.data
        meta.current_page = payload.meta.current_page
        meta.last_page = payload.meta.last_page
        meta.per_page = payload.meta.per_page
        meta.total = payload.meta.total
    } catch (error) {
        setAlert('error', getApiErrorMessage(t, error, 'events.fetchFailed'))
    } finally {
        loading.value = false
    }
}

function applyFilters(): void {
    clearAlert()
    query.page = 1
    void loadEvents()
}

function resetFilters(): void {
    clearAlert()
    filters.q = ''
    filters.from = ''
    filters.to = ''
    query.page = 1
    void loadEvents()
}

function sortBy(column: SortableColumn): void {
    clearAlert()
    if (query.sort_by === column) {
        query.sort_dir = query.sort_dir === 'asc' ? 'desc' : 'asc'
    } else {
        query.sort_by = column
        query.sort_dir = 'asc'
    }

    query.page = 1
    void loadEvents()
}

function sortIconClass(column: SortableColumn): string {
    if (query.sort_by !== column) {
        return 'bi bi-arrow-down-up ms-1'
    }

    return query.sort_dir === 'asc'
        ? 'bi bi-sort-up ms-1'
        : 'bi bi-sort-down ms-1'
}

function previewDescription(value: string | null): string {
    if (!value) {
        return '-'
    }

    if (value.length <= 30) {
        return value
    }

    return `${value.slice(0, 30)}...`
}

function formatDate(value: string): string {
    const date = new Date(value)
    if (Number.isNaN(date.getTime())) {
        return value
    }

    return new Intl.DateTimeFormat(undefined, {
        year: 'numeric',
        month: 'short',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        hour12: false,
    }).format(date)
}

function goToPage(page: number): void {
    if (page < 1 || page > meta.last_page || page === meta.current_page || loading.value) {
        return
    }

    query.page = page
    void loadEvents()
}

const visiblePages = computed(() => {
    const pages: number[] = []
    const start = Math.max(1, meta.current_page - 2)
    const end = Math.min(meta.last_page, meta.current_page + 2)

    for (let page = start; page <= end; page += 1) {
        pages.push(page)
    }

    return pages
})

const pageOptions = computed(() =>
    Array.from({ length: meta.last_page }, (_, idx) => idx + 1)
)

async function removeEvent(eventId: number): Promise<void> {
    clearAlert()

    if (!window.confirm(t('events.confirmDelete'))) {
        return
    }

    deletingEventId.value = eventId

    try {
        await deleteEvent(eventId)

        if (events.value.length === 1 && query.page > 1) {
            query.page -= 1
        }

        await loadEvents()
        setAlert('success', t('events.deleteSuccess'))
    } catch (error) {
        setAlert('error', getApiErrorMessage(t, error, 'events.deleteFailed'))
    } finally {
        deletingEventId.value = null
    }
}

function handlePerPageChange(): void {
    clearAlert()
    query.page = 1
    void loadEvents()
}

function handlePageSelectChange(event: Event): void {
    const selected = Number((event.target as HTMLSelectElement).value)
    goToPage(selected)
}

function resetUiState(): void {
    clearAlert()
    loading.value = false
    deletingEventId.value = null
}

onMounted(() => {
    const flash = sessionStorage.getItem('flash_events_success')
    if (flash) {
        setAlert('success', flash)
        sessionStorage.removeItem('flash_events_success')
    }

    void loadEvents()
})

onBeforeRouteLeave(() => {
    resetUiState()
})

onBeforeUnmount(() => {
    resetUiState()
})
</script>

<style scoped>
.event-list-card-body {
    container-type: inline-size;
    container-name: event-filters;
}

@container event-filters (max-width: 880px) {
    .event-filters-form > * {
        flex: 0 0 100% !important;
        max-width: 100% !important;
    }
}

.table-sort-btn {
    border: 0;
    background: transparent;
    font-weight: 600;
    padding: 0;
    color: inherit;
}

.table-sort-btn:hover {
    color: var(--bs-primary);
}

.action-btn:hover {
    transform: translateY(-1px);
}
</style>

