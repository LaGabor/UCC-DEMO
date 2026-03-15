<template>
    <div class="container-fluid py-3 conversation-history">
        <div class="mb-4">
            <h2 class="mb-1">{{ t('conversationHistory.title') }}</h2>
            <p class="mb-0 text-muted">{{ t('conversationHistory.subtitle') }}</p>
        </div>

        <div
            class="history-wrapper"
            :class="{
                'history-wrapper--split': selectedConversationId,
                'history-wrapper--split-compact': selectedConversationId && isCompact,
            }"
        >
            <section class="history-list">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body p-0 d-flex flex-column h-100">
                        <div class="table-responsive flex-grow-1">
                            <table class="table table-sm table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>
                                            <button class="table-sort-btn" @click="setSort('name')">
                                                {{ t('conversationHistory.columns.name') }}
                                                <i :class="sortIconClass('name')"></i>
                                            </button>
                                        </th>
                                        <th>
                                            <button class="table-sort-btn" @click="setSort('last_message_at')">
                                                {{ t('conversationHistory.columns.lastMessageAt') }}
                                                <i :class="sortIconClass('last_message_at')"></i>
                                            </button>
                                        </th>
                                        <th>{{ t('conversationHistory.columns.lastMessage') }}</th>
                                        <th class="text-end">{{ t('events.columns.operations') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-if="listLoading">
                                        <td colspan="4" class="text-center text-muted py-4">
                                            {{ t('conversationHistory.loading') }}
                                        </td>
                                    </tr>
                                    <tr v-else v-for="row in pagedRows" :key="row.conversation_id">
                                        <td>{{ row.user_name }}</td>
                                        <td>{{ formatDateTime(row.last_message_at) }}</td>
                                        <td>{{ lastMessagePreview(row.last_message_text) }}</td>
                                        <td class="text-end">
                                            <button
                                                type="button"
                                                class="btn btn-outline-secondary btn-sm"
                                                :disabled="selectedConversationId === row.conversation_id"
                                                @click="openConversation(row.conversation_id)"
                                            >
                                                <i class="bi bi-eye me-1" />
                                                {{ t('agentMonitor.viewConversation') }}
                                            </button>
                                        </td>
                                    </tr>
                                    <tr v-if="!listLoading && !pagedRows.length">
                                        <td colspan="4" class="text-center text-muted py-4">
                                            {{ t('conversationHistory.empty') }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="card-footer bg-white d-flex align-items-center justify-content-between gap-2">
                            <button
                                type="button"
                                class="btn btn-outline-secondary btn-sm"
                                :disabled="page === 1"
                                @click="goToPage(page - 1)"
                            >
                                {{ t('events.pagination.previous') }}
                            </button>

                            <div class="d-flex align-items-center gap-2">
                                <label class="small text-muted mb-0">{{ t('events.pagination.perPage') }}</label>
                                <select v-model.number="perPage" class="form-select form-select-sm" style="width: 90px;">
                                    <option :value="10">10</option>
                                    <option :value="25">25</option>
                                    <option :value="50">50</option>
                                    <option :value="100">100</option>
                                </select>
                            </div>

                            <div class="d-flex align-items-center gap-2">
                                <label class="small text-muted mb-0">{{ t('events.pagination.goToPage') }}</label>
                                <select v-model.number="page" class="form-select form-select-sm" style="width: 90px;">
                                    <option v-for="pageNumber in totalPages" :key="pageNumber" :value="pageNumber">{{ pageNumber }}</option>
                                </select>
                            </div>

                            <button
                                type="button"
                                class="btn btn-outline-secondary btn-sm"
                                :disabled="page >= totalPages"
                                @click="goToPage(page + 1)"
                            >
                                {{ t('events.pagination.next') }}
                            </button>
                        </div>
                    </div>
                </div>
            </section>

            <section class="history-chat" v-if="selectedConversationId">
                <div v-if="historyLoading" class="card shadow-sm border-0 h-100 d-flex align-items-center justify-content-center">
                    <p class="text-muted mb-0">{{ t('conversationHistory.loading') }}</p>
                </div>
                <AdminChatWidget
                    v-else
                    :title="selectedRow?.user_name ?? ''"
                    :subtitle="t('agentMonitor.statusClosed')"
                    :input-placeholder="t('agentMonitor.chatInputPlaceholder')"
                    :messages="activeHistoryMessages"
                    :input-disabled="true"
                    @close="closeConversation"
                />
            </section>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import AdminChatWidget from '../components/AdminChatWidget.vue'
import { fetchAdminOrHelpdeskAgentEligibility } from '../api/auth'
import { getConversationHistoryListRequest, getConversationHistoryMessagesRequest } from '../api/conversationHistory'
import { ROUTE_NAMES } from '../router'
import type { ConversationHistoryListEntry } from '../types/communication'
import { ConversationMessageSenderType, ConversationMessageType } from '../types/enums'

const LAST_MESSAGE_PREVIEW_LENGTH = 20

type HistoryMessage = {
    id: string
    sender: ConversationMessageSenderType
    message_type: ConversationMessageType
    content: string
    created_at: string
}

type SortField = 'name' | 'last_message_at'
type SortDir = 'asc' | 'desc'

const MOBILE_BREAKPOINT_PX = 970
const router = useRouter()
const { t } = useI18n()

const isCompact = ref(false)
const selectedConversationId = ref<number | null>(null)
const page = ref(1)
const perPage = ref(10)
const sortBy = ref<SortField>('last_message_at')
const sortDir = ref<SortDir>('desc')

const listLoading = ref(false)
const historyLoading = ref(false)
const listError = ref(false)
const rows = ref<ConversationHistoryListEntry[]>([])
const activeHistoryMessages = ref<HistoryMessage[]>([])

const sortedRows = computed(() => {
    const list = [...rows.value]
    const factor = sortDir.value === 'asc' ? 1 : -1

    list.sort((rowA, rowB) => {
        if (sortBy.value === 'name') {
            return rowA.user_name.localeCompare(rowB.user_name) * factor
        }
        const rowADateMs = rowA.last_message_at
            ? new Date(rowA.last_message_at).getTime()
            : 0
        const rowBDateMs = rowB.last_message_at
            ? new Date(rowB.last_message_at).getTime()
            : 0
        return (rowADateMs - rowBDateMs) * factor
    })
    return list
})

const totalPages = computed(() => Math.max(1, Math.ceil(sortedRows.value.length / perPage.value)))

const pagedRows = computed(() => {
    const start = (page.value - 1) * perPage.value
    return sortedRows.value.slice(start, start + perPage.value)
})

const selectedRow = computed(
    () =>
        rows.value.find(
            (row) => row.conversation_id === selectedConversationId.value
        ) ?? null
)

function lastMessagePreview(text: string | null): string {
    if (text == null || text === '') return '-'
    if (text.length <= LAST_MESSAGE_PREVIEW_LENGTH) return text
    return text.slice(0, LAST_MESSAGE_PREVIEW_LENGTH) + '...'
}

function setSort(field: SortField): void {
    if (sortBy.value === field) {
        sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc'
        return
    }
    sortBy.value = field
    sortDir.value = field === 'name' ? 'asc' : 'desc'
}

function sortIconClass(field: SortField): string {
    if (sortBy.value !== field) return 'bi bi-arrow-down-up ms-1'
    return sortDir.value === 'asc' ? 'bi bi-sort-up ms-1' : 'bi bi-sort-down ms-1'
}

function formatDateTime(value: string | null): string {
    if (!value) return '-'
    const date = new Date(value)
    const year = date.getFullYear()
    const month = String(date.getMonth() + 1).padStart(2, '0')
    const day = String(date.getDate()).padStart(2, '0')
    const hour = String(date.getHours()).padStart(2, '0')
    const minute = String(date.getMinutes()).padStart(2, '0')
    return `${year}. ${month}. ${day} ${hour}:${minute}`
}

function mapApiMessageToHistoryMessage(apiMessage: {
    id: number
    sender_type: string
    message_type: string
    message_text: string | null
    created_at: string
}): HistoryMessage {
    return {
        id: String(apiMessage.id),
        sender: apiMessage.sender_type as ConversationMessageSenderType,
        message_type: apiMessage.message_type as ConversationMessageType,
        content: apiMessage.message_text ?? '',
        created_at: apiMessage.created_at,
    }
}

async function loadList(): Promise<void> {
    listLoading.value = true
    listError.value = false
    try {
        rows.value = await getConversationHistoryListRequest()
    } catch {
        listError.value = true
        rows.value = []
    } finally {
        listLoading.value = false
    }
}

async function openConversation(conversationId: number): void {
    selectedConversationId.value = conversationId
    activeHistoryMessages.value = []
    historyLoading.value = true
    try {
        const data = await getConversationHistoryMessagesRequest(conversationId)
        activeHistoryMessages.value = data.messages.map(mapApiMessageToHistoryMessage)
    } catch {
        activeHistoryMessages.value = []
    } finally {
        historyLoading.value = false
    }
}

function closeConversation(): void {
    selectedConversationId.value = null
    activeHistoryMessages.value = []
}

function goToPage(target: number): void {
    page.value = Math.min(Math.max(1, target), totalPages.value)
}

function updateResponsiveState(): void {
    isCompact.value = window.innerWidth <= MOBILE_BREAKPOINT_PX
}

watch([perPage, totalPages], () => {
    if (page.value > totalPages.value) {
        page.value = totalPages.value
    }
})

onMounted(async () => {
    const canAccess = await fetchAdminOrHelpdeskAgentEligibility()
    if (!canAccess) {
        await router.replace({ name: ROUTE_NAMES.HOME })
        return
    }
    updateResponsiveState()
    window.addEventListener('resize', updateResponsiveState)
    loadList()
})

onBeforeUnmount(() => {
    window.removeEventListener('resize', updateResponsiveState)
    document.body.style.overflow = ''
})

watch(
    () => isCompact.value && !!selectedConversationId.value,
    (fullscreen) => {
        document.body.style.overflow = fullscreen ? 'hidden' : ''
    }
)
</script>

<style scoped>
.history-wrapper {
    min-height: 400px;
}

.history-wrapper--split {
    display: grid;
    grid-template-columns: 40% 60%;
    gap: 1rem;
    height: max(500px, calc(100dvh - 220px));
}

.history-wrapper--split .history-list {
    min-height: 0;
}

.history-wrapper--split .history-chat {
    min-height: 0;
}

.history-wrapper--split-compact .history-list {
    display: none;
}

.history-wrapper--split-compact .history-chat {
    position: fixed;
    inset: 0;
    z-index: 1200;
    width: 100vw;
    height: 100dvh;
}

.history-wrapper--split-compact .history-chat :deep(.admin-chat) {
    height: 100%;
    border-radius: 0;
}

.history-list {
    min-height: 0;
}

.history-chat {
    min-height: 0;
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

@media (max-width: 999px) {
    .history-wrapper--split {
        display: block;
        height: auto;
    }
}
</style>
