<template>
    <div class="container-fluid py-3 conversation-history">
        <div class="mb-4">
            <h2 class="mb-1">{{ t('conversationHistory.title') }}</h2>
            <p class="mb-0 text-muted">{{ t('conversationHistory.subtitle') }}</p>
        </div>

        <div
            class="history-grid"
            :class="{
                'history-grid--stacked': isCompact,
                'history-grid--list-only': !selectedConversation || (isCompact && !selectedConversation),
            }"
        >
            <section class="history-list" v-if="!isCompact || !selectedConversation">
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
                                            <button class="table-sort-btn" @click="setSort('startedAt')">
                                                {{ t('conversationHistory.columns.startedAt') }}
                                                <i :class="sortIconClass('startedAt')"></i>
                                            </button>
                                        </th>
                                        <th>
                                            <button class="table-sort-btn" @click="setSort('agentCalledAt')">
                                                {{ t('conversationHistory.columns.agentCalledAt') }}
                                                <i :class="sortIconClass('agentCalledAt')"></i>
                                            </button>
                                        </th>
                                        <th>
                                            <button class="table-sort-btn" @click="setSort('agentAnsweredAt')">
                                                {{ t('conversationHistory.columns.agentAnsweredAt') }}
                                                <i :class="sortIconClass('agentAnsweredAt')"></i>
                                            </button>
                                        </th>
                                        <th>
                                            <button class="table-sort-btn" @click="setSort('finishedAt')">
                                                {{ t('conversationHistory.columns.finishedAt') }}
                                                <i :class="sortIconClass('finishedAt')"></i>
                                            </button>
                                        </th>
                                        <th class="text-end">{{ t('events.columns.operations') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="conversation in pagedConversations" :key="conversation.id">
                                        <td>{{ conversation.requesterName }}</td>
                                        <td>{{ formatDateTime(conversation.startedAt) }}</td>
                                        <td>{{ formatNullableDate(conversation.agentCalledAt) }}</td>
                                        <td>{{ formatNullableDate(conversation.agentAnsweredAt) }}</td>
                                        <td>{{ formatDateTime(conversation.finishedAt) }}</td>
                                        <td class="text-end">
                                            <button
                                                type="button"
                                                class="btn btn-outline-secondary btn-sm"
                                                :disabled="selectedConversationId === conversation.id"
                                                @click="openConversation(conversation.id)"
                                            >
                                                <i class="bi bi-eye me-1" />
                                                {{ t('agentMonitor.viewConversation') }}
                                            </button>
                                        </td>
                                    </tr>
                                    <tr v-if="!pagedConversations.length">
                                        <td colspan="6" class="text-center text-muted py-4">
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
                                    <option v-for="p in totalPages" :key="p" :value="p">{{ p }}</option>
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

            <section
                class="history-chat"
                :class="{ 'history-chat--fullscreen': isCompact && selectedConversation }"
                v-if="selectedConversation || !isCompact"
            >
                <AdminChatWidget
                    v-if="selectedConversation"
                    :title="selectedConversation.requesterName"
                    :subtitle="t('agentMonitor.statusClosed')"
                    :input-placeholder="t('agentMonitor.chatInputPlaceholder')"
                    :messages="selectedConversation.messages"
                    :input-disabled="true"
                    @close="closeConversation"
                />
            </section>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import AdminChatWidget from '../components/AdminChatWidget.vue'
import { ConversationMessageSenderType, ConversationMessageType } from '../types/enums'

type HistoryMessage = {
    id: string
    sender: ConversationMessageSenderType
    message_type: ConversationMessageType
    content: string
    created_at: string
}

type HistoryConversation = {
    id: number
    requesterName: string
    startedAt: string
    agentCalledAt: string | null
    agentAnsweredAt: string | null
    finishedAt: string
    messages: HistoryMessage[]
}

type SortField = 'name' | 'startedAt' | 'agentCalledAt' | 'agentAnsweredAt' | 'finishedAt'
type SortDir = 'asc' | 'desc'

const MOBILE_BREAKPOINT_PX = 970
const { t } = useI18n()

const isCompact = ref(false)
const selectedConversationId = ref<number | null>(null)
const page = ref(1)
const perPage = ref(10)
const sortBy = ref<SortField>('finishedAt')
const sortDir = ref<SortDir>('desc')

const conversations = ref<HistoryConversation[]>([
    {
        id: 8001,
        requesterName: 'Bence Farkas',
        startedAt: '2026-03-10T06:10:00Z',
        agentCalledAt: '2026-03-10T06:11:00Z',
        agentAnsweredAt: '2026-03-10T06:12:00Z',
        finishedAt: '2026-03-10T06:18:00Z',
        messages: [
            {
                id: '8001-1',
                sender: ConversationMessageSenderType.USER,
                message_type: ConversationMessageType.QUESTION,
                content: 'I need help with invitation acceptance.',
                created_at: '2026-03-10T06:10:00Z',
            },
            {
                id: '8001-2',
                sender: ConversationMessageSenderType.AGENT,
                message_type: ConversationMessageType.AGENT_ANSWER,
                content: 'I have resent your invitation link.',
                created_at: '2026-03-10T06:12:00Z',
            },
        ],
    },
    {
        id: 8002,
        requesterName: 'Kata Simon',
        startedAt: '2026-03-10T07:00:00Z',
        agentCalledAt: null,
        agentAnsweredAt: null,
        finishedAt: '2026-03-10T07:05:00Z',
        messages: [
            {
                id: '8002-1',
                sender: ConversationMessageSenderType.USER,
                message_type: ConversationMessageType.QUESTION,
                content: 'How can I change language?',
                created_at: '2026-03-10T07:00:00Z',
            },
            {
                id: '8002-2',
                sender: ConversationMessageSenderType.BOT,
                message_type: ConversationMessageType.BOT_ANSWER,
                content: 'Use the language selector in the top bar.',
                created_at: '2026-03-10T07:00:20Z',
            },
        ],
    },
])

const sortedConversations = computed(() => {
    const list = [...conversations.value]
    const factor = sortDir.value === 'asc' ? 1 : -1

    list.sort((a, b) => {
        if (sortBy.value === 'name') {
            return a.requesterName.localeCompare(b.requesterName) * factor
        }

        const aValue = getSortTimestamp(a, sortBy.value)
        const bValue = getSortTimestamp(b, sortBy.value)
        return (aValue - bValue) * factor
    })

    return list
})

const totalPages = computed(() => Math.max(1, Math.ceil(sortedConversations.value.length / perPage.value)))

const pagedConversations = computed(() => {
    const start = (page.value - 1) * perPage.value
    return sortedConversations.value.slice(start, start + perPage.value)
})

const selectedConversation = computed(
    () => conversations.value.find((item) => item.id === selectedConversationId.value) ?? null
)

watch([perPage, totalPages], () => {
    if (page.value > totalPages.value) {
        page.value = totalPages.value
    }
})

function setSort(field: SortField): void {
    if (sortBy.value === field) {
        sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc'
        return
    }
    sortBy.value = field
    sortDir.value = 'asc'
}

function sortIconClass(field: SortField): string {
    if (sortBy.value !== field) {
        return 'bi bi-arrow-down-up ms-1'
    }

    return sortDir.value === 'asc' ? 'bi bi-sort-up ms-1' : 'bi bi-sort-down ms-1'
}

function getSortTimestamp(item: HistoryConversation, field: SortField): number {
    const value = field === 'startedAt'
        ? item.startedAt
        : field === 'agentCalledAt'
            ? item.agentCalledAt
            : field === 'agentAnsweredAt'
                ? item.agentAnsweredAt
                : item.finishedAt

    return value ? new Date(value).getTime() : 0
}

function openConversation(id: number): void {
    selectedConversationId.value = id
}

function closeConversation(): void {
    selectedConversationId.value = null
}

function goToPage(target: number): void {
    page.value = Math.min(Math.max(1, target), totalPages.value)
}

function updateResponsiveState(): void {
    isCompact.value = window.innerWidth <= MOBILE_BREAKPOINT_PX
}

function formatDateTime(value: string): string {
    const date = new Date(value)
    const year = date.getFullYear()
    const month = String(date.getMonth() + 1).padStart(2, '0')
    const day = String(date.getDate()).padStart(2, '0')
    const hour = String(date.getHours()).padStart(2, '0')
    const minute = String(date.getMinutes()).padStart(2, '0')
    return `${year}. ${month}. ${day} ${hour}:${minute}`
}

function formatNullableDate(value: string | null): string {
    return value ? formatDateTime(value) : '-'
}

onMounted(() => {
    updateResponsiveState()
    window.addEventListener('resize', updateResponsiveState)
})

onBeforeUnmount(() => {
    window.removeEventListener('resize', updateResponsiveState)
    document.body.style.overflow = ''
})

watch(
    () => isCompact.value && !!selectedConversation.value,
    (fullscreen) => {
        document.body.style.overflow = fullscreen ? 'hidden' : ''
    }
)
</script>

<style scoped>
.history-grid {
    display: grid;
    grid-template-columns: minmax(560px, 1fr) minmax(420px, 1fr);
    grid-template-areas: 'list chat';
    gap: 1rem;
    height: max(650px, calc(100dvh - 220px));
}

.history-grid--list-only {
    grid-template-columns: 1fr;
    grid-template-areas: 'list';
}

.history-list {
    grid-area: list;
    min-height: 0;
}

.history-chat {
    grid-area: chat;
    min-height: 0;
}

.history-chat--fullscreen {
    position: fixed;
    inset: 0;
    z-index: 1200;
    width: 100vw;
    height: 100dvh;
}

.history-chat--fullscreen :deep(.admin-chat) {
    height: 100%;
    border-radius: 0;
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
    .history-grid {
        display: block;
        height: auto;
    }
}
</style>
