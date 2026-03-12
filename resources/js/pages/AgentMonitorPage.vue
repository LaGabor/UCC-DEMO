<template>
    <div class="container-fluid py-3 agent-monitor">
        <div class="mb-4">
            <h2 class="mb-1">{{ t('agentMonitor.title') }}</h2>
            <p class="mb-0 text-muted">{{ t('agentMonitor.subtitle') }}</p>
        </div>

        <div class="agent-monitor-grid" :class="{ 'agent-monitor-grid--stacked': isCompact }">
            <section
                class="agent-monitor-chat"
                :class="{ 'agent-monitor-chat--fullscreen': isCompact && selectedConversation }"
                v-if="selectedConversation || !isCompact"
            >
                <div v-if="selectedConversation" class="h-100">
                    <AdminChatWidget
                        :title="selectedConversation.requesterName"
                        :subtitle="chatSubtitle(selectedConversation)"
                        :input-placeholder="t('agentMonitor.chatInputPlaceholder')"
                        :messages="selectedConversation.messages"
                        :input-disabled="selectedConversation.status === 'closed'"
                        @close="closeSelectedConversation"
                        @send="sendAgentMessage"
                    />
                </div>
                <div v-else class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex align-items-center justify-content-center text-muted">
                        {{ t('agentMonitor.emptyChat') }}
                    </div>
                </div>
            </section>

            <section class="agent-monitor-lists" v-if="!isCompact || !selectedConversation">
                <div class="card shadow-sm border-0 mb-3">
                    <div class="card-header bg-white d-flex justify-content-between align-items-start gap-2">
                        <div>
                            <h3 class="h6 mb-1">{{ t('agentMonitor.waitingListTitle') }}</h3>
                            <p class="mb-0 small text-muted">{{ t('agentMonitor.waitingListSubtitle') }}</p>
                        </div>
                        <span
                            v-if="hasWaitingCalls"
                            class="waiting-alert-dot"
                            :title="t('agentMonitor.waitingIndicatorTitle')"
                            aria-hidden="true"
                        />
                    </div>
                    <ul class="list-group list-group-flush">
                        <li
                            v-for="conversation in waitingAgentConversations"
                            :key="conversation.id"
                            class="list-group-item d-flex align-items-center gap-3"
                        >
                            <div class="flex-grow-1 min-w-0">
                                <div class="fw-semibold text-truncate">{{ conversation.requesterName }}</div>
                                <div class="small text-muted">
                                    {{ t('agentMonitor.waitingForAgent') }}:
                                    {{ formatElapsed(conversation.startedAt) }}
                                </div>
                            </div>

                            <button
                                type="button"
                                class="btn btn-danger btn-sm"
                                @click="openConversation(conversation.id)"
                            >
                                <i class="bi bi-telephone-fill me-1" />
                                {{ t('agentMonitor.openConversation') }}
                            </button>
                        </li>
                        <li v-if="!waitingAgentConversations.length" class="list-group-item text-muted">
                            {{ t('agentMonitor.noWaitingConversations') }}
                        </li>
                    </ul>
                </div>

                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white">
                        <h3 class="h6 mb-1">{{ t('agentMonitor.botAndHistoryTitle') }}</h3>
                        <p class="mb-0 small text-muted">{{ t('agentMonitor.botAndHistorySubtitle') }}</p>
                    </div>
                    <ul class="list-group list-group-flush">
                        <li
                            v-for="conversation in pagedBotAndHistoryConversations"
                            :key="conversation.id"
                            class="list-group-item d-flex align-items-center gap-3"
                        >
                            <div class="flex-grow-1 min-w-0">
                                <div class="fw-semibold text-truncate d-flex align-items-center">
                                    <span class="text-truncate me-2">{{ conversation.requesterName }}</span>
                                    <span
                                        v-if="conversation.status === 'bot_active'"
                                        class="bot-active-dot"
                                        :title="t('agentMonitor.botActiveIndicatorTitle')"
                                        aria-hidden="true"
                                    />
                                </div>
                                <div class="small text-muted">
                                    {{ t('agentMonitor.startedAt') }}:
                                    {{ formatDateTime(conversation.startedAt) }}
                                </div>
                            </div>

                            <button
                                type="button"
                                class="btn btn-outline-secondary btn-sm"
                                @click="openConversation(conversation.id)"
                            >
                                <i class="bi bi-eye me-1" />
                                {{ t('agentMonitor.viewConversation') }}
                            </button>
                        </li>
                        <li v-if="!pagedBotAndHistoryConversations.length" class="list-group-item text-muted">
                            {{ t('agentMonitor.noBotOrHistoryConversations') }}
                        </li>
                    </ul>

                    <div class="card-footer bg-white d-flex align-items-center justify-content-between gap-2">
                        <button
                            type="button"
                            class="btn btn-outline-secondary btn-sm"
                            :disabled="secondListPage === 1"
                            @click="goToSecondListPage(secondListPage - 1)"
                        >
                            {{ t('events.pagination.previous') }}
                        </button>

                        <div class="d-flex align-items-center gap-2">
                            <label class="small text-muted mb-0">
                                {{ t('events.pagination.goToPage') }}
                            </label>
                            <select
                                v-model.number="secondListPage"
                                class="form-select form-select-sm"
                                style="width: 90px;"
                            >
                                <option v-for="page in secondListTotalPages" :key="page" :value="page">
                                    {{ page }}
                                </option>
                            </select>
                        </div>

                        <button
                            type="button"
                            class="btn btn-outline-secondary btn-sm"
                            :disabled="secondListPage >= secondListTotalPages"
                            @click="goToSecondListPage(secondListPage + 1)"
                        >
                            {{ t('events.pagination.next') }}
                        </button>
                    </div>
                </div>
            </section>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import AdminChatWidget from '../components/AdminChatWidget.vue'
import { ConversationMessageSenderType, ConversationMessageType } from '../types/enums'

type MonitorConversationStatus = 'waiting_agent' | 'bot_active' | 'closed'

type MonitorMessage = {
    id: string
    sender: ConversationMessageSenderType
    message_type: ConversationMessageType
    content: string
    created_at: string
}

type MonitorConversation = {
    id: number
    requesterName: string
    status: MonitorConversationStatus
    startedAt: string
    messages: MonitorMessage[]
}

const MOBILE_BREAKPOINT_PX = 999
const SECOND_LIST_PAGE_SIZE = 5

const { t } = useI18n()

const nowMs = ref(Date.now())
const isCompact = ref(false)
const secondListPage = ref(1)
const selectedConversationId = ref<number | null>(null)

const conversations = ref<MonitorConversation[]>([
    {
        id: 101,
        requesterName: 'Anna Kovacs',
        status: 'waiting_agent',
        startedAt: '2026-03-10T07:35:00Z',
        messages: [
            {
                id: '101-1',
                sender: ConversationMessageSenderType.SYSTEM,
                message_type: ConversationMessageType.SYSTEM_NOTICE,
                content: 'System: User requested live agent support.',
                created_at: '2026-03-10T07:35:00Z',
            },
            {
                id: '101-2',
                sender: ConversationMessageSenderType.USER,
                message_type: ConversationMessageType.QUESTION,
                content: 'I need help with changing my account email.',
                created_at: '2026-03-10T07:35:30Z',
            },
            {
                id: '101-3',
                sender: ConversationMessageSenderType.BOT,
                message_type: ConversationMessageType.BOT_ANSWER,
                content: 'I am escalating your request to an agent.',
                created_at: '2026-03-10T07:35:45Z',
            },
        ],
    },
    {
        id: 102,
        requesterName: 'Peter Horvath',
        status: 'waiting_agent',
        startedAt: '2026-03-10T07:55:00Z',
        messages: [
            {
                id: '102-1',
                sender: ConversationMessageSenderType.USER,
                message_type: ConversationMessageType.QUESTION,
                content: 'My invitation link expired, can you help?',
                created_at: '2026-03-10T07:55:00Z',
            },
            {
                id: '102-2',
                sender: ConversationMessageSenderType.BOT,
                message_type: ConversationMessageType.BOT_ANSWER,
                content: 'I can connect you to an agent for account verification.',
                created_at: '2026-03-10T07:55:20Z',
            },
            {
                id: '102-3',
                sender: ConversationMessageSenderType.SYSTEM,
                message_type: ConversationMessageType.SYSTEM_NOTICE,
                content: 'System: Waiting for available agent.',
                created_at: '2026-03-10T07:55:25Z',
            },
        ],
    },
    {
        id: 201,
        requesterName: 'Julia Nagy',
        status: 'bot_active',
        startedAt: '2026-03-10T08:05:00Z',
        messages: [
            {
                id: '201-1',
                sender: ConversationMessageSenderType.USER,
                message_type: ConversationMessageType.QUESTION,
                content: 'How can I enable two-factor authentication?',
                created_at: '2026-03-10T08:05:00Z',
            },
            {
                id: '201-2',
                sender: ConversationMessageSenderType.BOT,
                message_type: ConversationMessageType.BOT_ANSWER,
                content: 'Open Settings > MultiFactor Settings and click Enable.',
                created_at: '2026-03-10T08:05:12Z',
            },
        ],
    },
    {
        id: 202,
        requesterName: 'Mark Toth',
        status: 'bot_active',
        startedAt: '2026-03-10T08:18:00Z',
        messages: [
            {
                id: '202-1',
                sender: ConversationMessageSenderType.USER,
                message_type: ConversationMessageType.QUESTION,
                content: 'Why can I not see events in my list?',
                created_at: '2026-03-10T08:18:00Z',
            },
            {
                id: '202-2',
                sender: ConversationMessageSenderType.BOT,
                message_type: ConversationMessageType.BOT_ANSWER,
                content: 'Please check your date filters, then refresh the page.',
                created_at: '2026-03-10T08:18:20Z',
            },
        ],
    },
    {
        id: 301,
        requesterName: 'Dora Varga',
        status: 'closed',
        startedAt: '2026-03-10T06:40:00Z',
        messages: [
            {
                id: '301-1',
                sender: ConversationMessageSenderType.USER,
                message_type: ConversationMessageType.QUESTION,
                content: 'How can I reset my password?',
                created_at: '2026-03-10T06:40:00Z',
            },
            {
                id: '301-2',
                sender: ConversationMessageSenderType.AGENT,
                message_type: ConversationMessageType.AGENT_ANSWER,
                content: 'Use the forgot-password page and check your email.',
                created_at: '2026-03-10T06:42:30Z',
            },
        ],
    },
    {
        id: 302,
        requesterName: 'Robert Kiss',
        status: 'closed',
        startedAt: '2026-03-10T05:12:00Z',
        messages: [
            {
                id: '302-1',
                sender: ConversationMessageSenderType.USER,
                message_type: ConversationMessageType.QUESTION,
                content: 'Can I change language after login?',
                created_at: '2026-03-10T05:12:00Z',
            },
            {
                id: '302-2',
                sender: ConversationMessageSenderType.BOT,
                message_type: ConversationMessageType.BOT_ANSWER,
                content: 'Yes, use the language selector in the top navbar.',
                created_at: '2026-03-10T05:12:20Z',
            },
        ],
    },
])

const waitingAgentConversations = computed(() =>
    conversations.value
        .filter((item) => item.status === 'waiting_agent')
        .sort((a, b) => new Date(a.startedAt).getTime() - new Date(b.startedAt).getTime())
)

const botAndHistoryConversations = computed(() =>
    conversations.value
        .filter((item) => item.status === 'bot_active' || item.status === 'closed')
        .sort((a, b) => new Date(b.startedAt).getTime() - new Date(a.startedAt).getTime())
)

const secondListTotalPages = computed(() =>
    Math.max(1, Math.ceil(botAndHistoryConversations.value.length / SECOND_LIST_PAGE_SIZE))
)

const pagedBotAndHistoryConversations = computed(() => {
    const start = (secondListPage.value - 1) * SECOND_LIST_PAGE_SIZE
    const end = start + SECOND_LIST_PAGE_SIZE
    return botAndHistoryConversations.value.slice(start, end)
})

const selectedConversation = computed(() =>
    conversations.value.find((item) => item.id === selectedConversationId.value) ?? null
)
const hasWaitingCalls = computed(() => waitingAgentConversations.value.length > 0)

function updateResponsiveState(): void {
    isCompact.value = window.innerWidth <= MOBILE_BREAKPOINT_PX
}

function openConversation(conversationId: number): void {
    selectedConversationId.value = conversationId
}

function closeSelectedConversation(): void {
    selectedConversationId.value = null
}

function goToSecondListPage(page: number): void {
    const normalized = Math.min(Math.max(1, page), secondListTotalPages.value)
    secondListPage.value = normalized
}

function chatSubtitle(conversation: MonitorConversation): string {
    if (conversation.status === 'waiting_agent') {
        return t('agentMonitor.statusWaitingAgent')
    }
    if (conversation.status === 'bot_active') {
        return t('agentMonitor.statusBotActive')
    }
    return t('agentMonitor.statusClosed')
}

function sendAgentMessage(content: string): void {
    if (!selectedConversation.value) {
        return
    }

    const now = new Date().toISOString()
    selectedConversation.value.messages.push({
        id: `${selectedConversation.value.id}-${Date.now()}`,
        sender: ConversationMessageSenderType.AGENT,
        message_type: ConversationMessageType.AGENT_ANSWER,
        content,
        created_at: now,
    })
}

function formatElapsed(startedAt: string): string {
    const diffSeconds = Math.max(
        0,
        Math.floor((nowMs.value - new Date(startedAt).getTime()) / 1000)
    )

    const hours = Math.floor(diffSeconds / 3600)
    const minutes = Math.floor((diffSeconds % 3600) / 60)
    const seconds = diffSeconds % 60

    if (hours > 0) {
        return `${hours}h ${minutes}m ${seconds}s`
    }

    return `${minutes}m ${seconds}s`
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

let timerId: number | null = null
let incomingCallTimeoutId: number | null = null

function playIncomingCallTone(): void {
    try {
        const audioContext = new window.AudioContext()
        const masterGain = audioContext.createGain()
        masterGain.connect(audioContext.destination)

        // Louder, attention-grabbing "ring ring" pattern.
        masterGain.gain.setValueAtTime(0.0001, audioContext.currentTime)
        masterGain.gain.exponentialRampToValueAtTime(0.22, audioContext.currentTime + 0.02)
        masterGain.gain.exponentialRampToValueAtTime(0.0001, audioContext.currentTime + 2.4)

        const ringMoments = [0, 0.45, 1.15, 1.6]
        for (const offset of ringMoments) {
            const oscA = audioContext.createOscillator()
            oscA.type = 'square'
            oscA.frequency.setValueAtTime(1050, audioContext.currentTime + offset)
            oscA.connect(masterGain)
            oscA.start(audioContext.currentTime + offset)
            oscA.stop(audioContext.currentTime + offset + 0.18)

            const oscB = audioContext.createOscillator()
            oscB.type = 'triangle'
            oscB.frequency.setValueAtTime(780, audioContext.currentTime + offset + 0.19)
            oscB.connect(masterGain)
            oscB.start(audioContext.currentTime + offset + 0.19)
            oscB.stop(audioContext.currentTime + offset + 0.34)
        }

        window.setTimeout(() => {
            void audioContext.close()
        }, 2600)
    } catch {
        // Ignore audio API failures in unsupported/autoplay-restricted environments.
    }
}

function scheduleDelayedDummyIncomingCall(): void {
    incomingCallTimeoutId = window.setTimeout(() => {
        const conversation: MonitorConversation = {
            id: 999,
            requesterName: 'Delayed Dummy Caller',
            status: 'waiting_agent',
            startedAt: new Date().toISOString(),
            messages: [
                {
                    id: '999-1',
                    sender: ConversationMessageSenderType.SYSTEM,
                    message_type: ConversationMessageType.SYSTEM_NOTICE,
                    content: 'System: New incoming request queued for agent response.',
                    created_at: new Date().toISOString(),
                },
                {
                    id: '999-2',
                    sender: ConversationMessageSenderType.USER,
                    message_type: ConversationMessageType.QUESTION,
                    content: 'Hi, I need urgent help with account access.',
                    created_at: new Date().toISOString(),
                },
            ],
        }

        conversations.value = [conversation, ...conversations.value]

        if (!selectedConversation.value) {
            playIncomingCallTone()
        }
    }, 6000)
}

onMounted(() => {
    updateResponsiveState()
    window.addEventListener('resize', updateResponsiveState)
    scheduleDelayedDummyIncomingCall()
    timerId = window.setInterval(() => {
        nowMs.value = Date.now()
    }, 1000)
})

onBeforeUnmount(() => {
    window.removeEventListener('resize', updateResponsiveState)
    if (timerId !== null) {
        window.clearInterval(timerId)
        timerId = null
    }
    if (incomingCallTimeoutId !== null) {
        window.clearTimeout(incomingCallTimeoutId)
        incomingCallTimeoutId = null
    }
})
</script>

<style scoped>
.agent-monitor-grid {
    display: grid;
    grid-template-columns: minmax(340px, 460px) minmax(540px, 1fr);
    grid-template-areas: 'lists chat';
    gap: 1rem;
    min-height: calc(100vh - 220px);
}

.agent-monitor-grid--stacked {
    grid-template-columns: 1fr;
    grid-template-areas: 'chat';
}

.agent-monitor-chat {
    grid-area: chat;
    min-height: 0;
}

.agent-monitor-chat--fullscreen {
    min-height: calc(100vh - 140px);
}

.agent-monitor-lists {
    grid-area: lists;
    min-height: 0;
}

.waiting-alert-dot {
    width: 0.85rem;
    height: 0.85rem;
    border-radius: 999px;
    background: #dc3545;
    box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.8);
    animation: waiting-blink 1.1s ease-in-out infinite;
    margin-top: 0.3rem;
}

.bot-active-dot {
    width: 0.7rem;
    height: 0.7rem;
    border-radius: 999px;
    background: var(--bs-success);
    box-shadow: 0 0 0 0 rgba(25, 135, 84, 0.8);
    animation: bot-active-blink 1s ease-in-out infinite;
    flex-shrink: 0;
}

@media (max-width: 999px) {
    .agent-monitor {
        padding-left: 0.4rem;
        padding-right: 0.4rem;
    }
}

@keyframes waiting-blink {
    0% {
        opacity: 0.4;
        box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.7);
    }
    50% {
        opacity: 1;
        box-shadow: 0 0 0 8px rgba(220, 53, 69, 0);
    }
    100% {
        opacity: 0.4;
        box-shadow: 0 0 0 0 rgba(220, 53, 69, 0);
    }
}

@keyframes bot-active-blink {
    0% {
        opacity: 0.45;
        box-shadow: 0 0 0 0 rgba(25, 135, 84, 0.7);
    }
    50% {
        opacity: 1;
        box-shadow: 0 0 0 6px rgba(25, 135, 84, 0);
    }
    100% {
        opacity: 0.45;
        box-shadow: 0 0 0 0 rgba(25, 135, 84, 0);
    }
}
</style>
