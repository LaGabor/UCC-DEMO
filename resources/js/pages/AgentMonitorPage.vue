<template>
    <div class="container-fluid py-3 agent-monitor">
        <div class="mb-4">
            <h2 class="mb-1">{{ t('agentMonitor.title') }}</h2>
            <p class="mb-0 text-muted">{{ t('agentMonitor.subtitle') }}</p>
        </div>

        <div
            class="agent-monitor-grid"
            :class="{
                'agent-monitor-grid--stacked': isCompact,
                'agent-monitor-grid--lists-only': isCompact && !selectedConversation,
            }"
        >
            <section
                v-if="selectedConversation || !isCompact"
                class="agent-monitor-chat"
                :class="{ 'agent-monitor-chat--fullscreen': isFullscreenChat }"
            >
                <AdminChatWidget
                    v-if="selectedConversation"
                    :title="selectedConversation.requesterName"
                    :subtitle="chatSubtitle(selectedConversation)"
                    :input-placeholder="t('agentMonitor.chatInputPlaceholder')"
                    :messages="selectedConversation.messages"
                    :input-disabled="
                        selectedConversation.status !== ConversationStatus.ASSIGNED ||
                        !canManageAssignedConversation(selectedConversation)
                    "
                    :action-label="
                        selectedConversation.status === ConversationStatus.ASSIGNED &&
                        canManageAssignedConversation(selectedConversation)
                            ? t('agentMonitor.endCommunication')
                            : undefined
                    "
                    action-button-class="btn-danger"
                    @close="closeSelectedConversation"
                    @send="sendAgentMessage"
                    @action="endAssignedConversation"
                />
                <div v-else class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex align-items-center justify-content-center text-muted">
                        {{ t('agentMonitor.emptyChat') }}
                    </div>
                </div>
            </section>

            <section class="agent-monitor-lists" v-if="!isCompact || !selectedConversation">
                <div
                    v-if="hasWaitingCalls"
                    class="card shadow-sm border-0 agent-monitor-list-card agent-monitor-list-card--waiting"
                >
                    <div class="card-header bg-white d-flex justify-content-between align-items-start gap-2">
                        <div>
                            <h3 class="h6 mb-1">{{ t('agentMonitor.waitingListTitle') }}</h3>
                            <p class="mb-0 small text-muted">{{ t('agentMonitor.waitingListSubtitle') }}</p>
                        </div>
                        <span class="waiting-alert-dot" :title="t('agentMonitor.waitingIndicatorTitle')" />
                    </div>

                    <div class="agent-monitor-list-scroll">
                        <ul class="list-group list-group-flush">
                            <li
                                v-for="conversation in waitingAgentConversations"
                                :key="conversation.id"
                                class="list-group-item d-flex align-items-center gap-3"
                            >
                                <div class="flex-grow-1 min-w-0">
                                    <div class="fw-semibold text-truncate">{{ conversation.requesterName }}</div>
                                    <div
                                        v-if="conversation.status !== ConversationStatus.ASSIGNED"
                                        class="small text-muted"
                                    >
                                        {{ t('agentMonitor.waitingForAgent') }}:
                                        {{ formatElapsed(conversation.startedAt) }}
                                    </div>
                                </div>

                                <button
                                    type="button"
                                    :class="
                                        conversation.status === ConversationStatus.ASSIGNED
                                            ? 'btn btn-success btn-sm'
                                            : 'btn btn-danger btn-sm'
                                    "
                                    :disabled="
                                        conversation.status === ConversationStatus.ASSIGNED ||
                                        selectedConversationId === conversation.id
                                    "
                                    @click="answerWaitingConversation(conversation.id)"
                                >
                                    <i class="bi bi-telephone-fill me-1" />
                                    {{
                                        conversation.status === ConversationStatus.ASSIGNED
                                            ? t('agentMonitor.activeConversation')
                                            : t('agentMonitor.openConversation')
                                    }}
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="card shadow-sm border-0 agent-monitor-list-card agent-monitor-list-card--active">
                    <div class="card-header bg-white">
                        <h3 class="h6 mb-1">{{ t('agentMonitor.activeListTitle') }}</h3>
                        <p class="mb-0 small text-muted">{{ t('agentMonitor.activeListSubtitle') }}</p>
                    </div>

                    <div class="agent-monitor-list-scroll">
                        <ul class="list-group list-group-flush">
                            <li
                                v-for="conversation in pagedActiveConversations"
                                :key="conversation.id"
                                class="list-group-item d-flex align-items-center gap-3"
                            >
                                <div class="flex-grow-1 min-w-0">
                                    <div class="fw-semibold text-truncate d-flex align-items-center">
                                        <span class="text-truncate me-2">{{ conversation.requesterName }}</span>
                                        <span
                                            class="badge"
                                            :class="
                                                conversation.status === ConversationStatus.OPEN
                                                    ? 'text-bg-success'
                                                    : 'text-bg-primary'
                                            "
                                        >
                                            {{
                                                conversation.status === ConversationStatus.OPEN
                                                    ? t('agentMonitor.badgeBot')
                                                    : t('agentMonitor.badgeAgent')
                                            }}
                                        </span>
                                    </div>
                                    <div class="small text-muted">
                                        {{ t('agentMonitor.activeAgentSince') }}:
                                        {{ formatElapsed(conversation.activeSinceAt ?? conversation.startedAt) }}
                                    </div>
                                </div>

                                <button
                                    type="button"
                                    class="btn btn-outline-secondary btn-sm"
                                    :disabled="
                                        selectedConversationId === conversation.id ||
                                        (conversation.status === ConversationStatus.ASSIGNED &&
                                            !canViewAssignedConversation(conversation))
                                    "
                                    @click="openConversation(conversation.id)"
                                >
                                    <i class="bi bi-eye me-1" />
                                    {{ t('agentMonitor.viewConversation') }}
                                </button>
                            </li>
                            <li v-if="!pagedActiveConversations.length" class="list-group-item text-muted">
                                {{ t('agentMonitor.noActiveConversations') }}
                            </li>
                        </ul>
                    </div>

                    <div class="card-footer bg-white d-flex align-items-center justify-content-between gap-2">
                        <button
                            type="button"
                            class="btn btn-outline-secondary btn-sm"
                            :disabled="activeListPage === 1"
                            @click="goToActiveListPage(activeListPage - 1)"
                        >
                            {{ t('events.pagination.previous') }}
                        </button>

                        <div class="d-flex align-items-center gap-2">
                            <label class="small text-muted mb-0">{{ t('events.pagination.goToPage') }}</label>
                            <select
                                v-model.number="activeListPage"
                                class="form-select form-select-sm"
                                style="width: 90px;"
                            >
                                <option v-for="page in activeListTotalPages" :key="page" :value="page">
                                    {{ page }}
                                </option>
                            </select>
                        </div>

                        <button
                            type="button"
                            class="btn btn-outline-secondary btn-sm"
                            :disabled="activeListPage >= activeListTotalPages"
                            @click="goToActiveListPage(activeListPage + 1)"
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
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import AdminChatWidget from '../components/AdminChatWidget.vue'
import { useAuth } from '../auth'
import { getAgentMonitorConversationsRequest } from '../api/agentMonitor'
import type { AgentMonitorConversationBroadcastPayload } from '../types/communication'
import {
    ConversationMessageSenderType,
    ConversationMessageType,
    ConversationStatus,
    UserRole,
} from '../types/enums'

type MonitorConversationStatus = ConversationStatus

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
    activeSinceAt?: string | null
    assignedToUserId?: number | null
    messages: MonitorMessage[]
}

function payloadToMonitorConversation(
    p: AgentMonitorConversationBroadcastPayload | {
        conversation_id: number
        user_id: number
        user_name: string
        assigned_agent_id: number | null
        status: string
        created_at: string
        last_assigned_call: string | null
        last_assigned_at: string | null
        last_closed_at: string | null
        last_open_at: string | null
    }
): MonitorConversation {
    const status = p.status as MonitorConversationStatus
    const startedAt =
        status === ConversationStatus.WAITING_HUMAN && p.last_assigned_call
            ? p.last_assigned_call
            : p.created_at

    return {
        id: p.conversation_id,
        requesterName: p.user_name,
        status,
        startedAt,
        activeSinceAt: p.last_assigned_at ?? undefined,
        assignedToUserId: p.assigned_agent_id ?? undefined,
        messages: [],
    }
}

const MOBILE_BREAKPOINT_PX = 970
const ACTIVE_LIST_PAGE_SIZE = 10

const { t } = useI18n()
const auth = useAuth()

const nowMs = ref(Date.now())
const isCompact = ref(false)
const activeListPage = ref(1)
const selectedConversationId = ref<number | null>(null)

const conversations = ref<MonitorConversation[]>([])

async function loadConversations(): Promise<void> {
    try {
        const list = await getAgentMonitorConversationsRequest()
        conversations.value = list.map((p) => payloadToMonitorConversation(p))
    } catch {
        conversations.value = []
    }
}

function subscribeAgentMonitorChannel(): void {
    window.Echo.private('agent-monitor').listen(
        '.conversation.status.updated',
        (payload: AgentMonitorConversationBroadcastPayload) => {
            if (payload.status === ConversationStatus.CLOSED) {
                conversations.value = conversations.value.filter((c) => c.id !== payload.conversation_id)
                if (selectedConversationId.value === payload.conversation_id) {
                    selectedConversationId.value = null
                }
                return
            }

            const existing = conversations.value.findIndex((c) => c.id === payload.conversation_id)
            const next = payloadToMonitorConversation(payload)

            if (existing >= 0) {
                const prev = conversations.value[existing]
                next.messages = prev.messages
                conversations.value = conversations.value.map((c) =>
                    c.id === payload.conversation_id ? next : c
                )
            } else {
                conversations.value = [next, ...conversations.value]
            }

            if (
                payload.status === ConversationStatus.WAITING_HUMAN &&
                !selectedConversation.value
            ) {
                playIncomingCallTone()
            }
        }
    )
}

function unsubscribeAgentMonitorChannel(): void {
    window.Echo.leave('agent-monitor')
}

const waitingAgentConversations = computed(() =>
    conversations.value
        .filter(
            (item) =>
                item.status === ConversationStatus.WAITING_HUMAN ||
                (item.status === ConversationStatus.ASSIGNED && isAssignedToCurrentUser(item))
        )
        .sort((a, b) => new Date(a.startedAt).getTime() - new Date(b.startedAt).getTime())
)

const currentUserId = computed(() => auth.state.user?.id ?? null)
const isAdmin = computed(() => auth.state.user?.role === UserRole.ADMIN)

function isAssignedToCurrentUser(conversation: MonitorConversation): boolean {
    return !!currentUserId.value && conversation.assignedToUserId === currentUserId.value
}

function canViewAssignedConversation(conversation: MonitorConversation): boolean {
    return isAdmin.value || isAssignedToCurrentUser(conversation)
}

function canManageAssignedConversation(conversation: MonitorConversation): boolean {
    return isAssignedToCurrentUser(conversation)
}

const activeConversations = computed(() =>
    conversations.value
        .filter(
            (item) => {
                if (item.status === ConversationStatus.OPEN) {
                    return true
                }

                if (item.status === ConversationStatus.ASSIGNED) {
                    return canViewAssignedConversation(item) && !isAssignedToCurrentUser(item)
                }

                return false
            }
        )
        .sort((a, b) => new Date(b.startedAt).getTime() - new Date(a.startedAt).getTime())
)

const activeListTotalPages = computed(() =>
    Math.max(1, Math.ceil(activeConversations.value.length / ACTIVE_LIST_PAGE_SIZE))
)

const pagedActiveConversations = computed(() => {
    const start = (activeListPage.value - 1) * ACTIVE_LIST_PAGE_SIZE
    return activeConversations.value.slice(start, start + ACTIVE_LIST_PAGE_SIZE)
})

const selectedConversation = computed(
    () => conversations.value.find((item) => item.id === selectedConversationId.value) ?? null
)
const hasWaitingCalls = computed(() => waitingAgentConversations.value.length > 0)
const isFullscreenChat = computed(() => isCompact.value && !!selectedConversation.value)

function updateResponsiveState(): void {
    isCompact.value = window.innerWidth <= MOBILE_BREAKPOINT_PX
}

function answerWaitingConversation(conversationId: number): void {
    const conversation = conversations.value.find((item) => item.id === conversationId)
    if (!conversation) {
        return
    }

    if (conversation.status === ConversationStatus.WAITING_HUMAN) {
        conversation.status = ConversationStatus.ASSIGNED
        conversation.activeSinceAt = new Date().toISOString()
        conversation.assignedToUserId = currentUserId.value
        conversation.messages.push({
            id: `${conversation.id}-accepted-${Date.now()}`,
            sender: ConversationMessageSenderType.SYSTEM,
            message_type: ConversationMessageType.SYSTEM_NOTICE,
            content: 'System: Agent accepted this conversation.',
            created_at: new Date().toISOString(),
        })
    }

    openConversation(conversationId)
}

function openConversation(conversationId: number): void {
    const targetConversation = conversations.value.find((item) => item.id === conversationId)
    if (!targetConversation) {
        return
    }

    if (
        targetConversation.status === ConversationStatus.ASSIGNED &&
        !canViewAssignedConversation(targetConversation)
    ) {
        return
    }

    if (selectedConversationId.value !== null && selectedConversationId.value !== conversationId) {
        finalizeConversationOnLeave(selectedConversationId.value)
    }

    selectedConversationId.value = conversationId
}

function closeSelectedConversation(): void {
    if (selectedConversationId.value !== null) {
        finalizeConversationOnLeave(selectedConversationId.value)
    }
    selectedConversationId.value = null
}

function endAssignedConversation(): void {
    if (
        !selectedConversation.value ||
        selectedConversation.value.status !== ConversationStatus.ASSIGNED ||
        !canManageAssignedConversation(selectedConversation.value)
    ) {
        return
    }

    selectedConversation.value.status = ConversationStatus.OPEN
    selectedConversation.value.activeSinceAt = null
    selectedConversation.value.assignedToUserId = null
    selectedConversation.value.messages.push({
        id: `${selectedConversation.value.id}-end-${Date.now()}`,
        sender: ConversationMessageSenderType.SYSTEM,
        message_type: ConversationMessageType.SYSTEM_NOTICE,
        content: 'System: Agent communication finished. Returning to bot mode.',
        created_at: new Date().toISOString(),
    })
}

function finalizeConversationOnLeave(conversationId: number): void {
    const conversation = conversations.value.find((item) => item.id === conversationId)
    if (!conversation) {
        return
    }

    if (conversation.status === ConversationStatus.ASSIGNED) {
        if (!canManageAssignedConversation(conversation)) {
            return
        }

        conversation.status = ConversationStatus.WAITING_HUMAN
        conversation.activeSinceAt = null
        conversation.assignedToUserId = null
        return
    }

    if (conversation.status === ConversationStatus.CLOSED) {
        conversations.value = conversations.value.filter((item) => item.id !== conversationId)
        return
    }

    if (conversation.status === ConversationStatus.OPEN) {
        conversation.activeSinceAt = null
    }
}

function goToActiveListPage(page: number): void {
    activeListPage.value = Math.min(Math.max(1, page), activeListTotalPages.value)
}

function chatSubtitle(conversation: MonitorConversation): string {
    if (conversation.status === ConversationStatus.WAITING_HUMAN) {
        return t('agentMonitor.statusWaitingAgent')
    }
    if (conversation.status === ConversationStatus.OPEN) {
        return t('agentMonitor.statusBotActive')
    }
    if (conversation.status === ConversationStatus.ASSIGNED) {
        return t('agentMonitor.statusAgentActive')
    }
    return t('agentMonitor.statusClosed')
}

function sendAgentMessage(content: string): void {
    if (
        !selectedConversation.value ||
        selectedConversation.value.status !== ConversationStatus.ASSIGNED ||
        !canManageAssignedConversation(selectedConversation.value)
    ) {
        return
    }

    selectedConversation.value.messages.push({
        id: `${selectedConversation.value.id}-${Date.now()}`,
        sender: ConversationMessageSenderType.AGENT,
        message_type: ConversationMessageType.AGENT_ANSWER,
        content,
        created_at: new Date().toISOString(),
    })
}

function formatElapsed(startedAt: string): string {
    const diffSeconds = Math.max(0, Math.floor((nowMs.value - new Date(startedAt).getTime()) / 1000))
    const hours = Math.floor(diffSeconds / 3600)
    const minutes = Math.floor((diffSeconds % 3600) / 60)
    const seconds = diffSeconds % 60
    return hours > 0 ? `${hours}h ${minutes}m ${seconds}s` : `${minutes}m ${seconds}s`
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
let lastAlertPlayedAtMs = 0

function playIncomingCallTone(): void {
    const now = Date.now()
    if (now - lastAlertPlayedAtMs < 3000) {
        return
    }
    lastAlertPlayedAtMs = now

    try {
        const audioContext = new window.AudioContext()
        const masterGain = audioContext.createGain()
        masterGain.connect(audioContext.destination)
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

        window.setTimeout(() => void audioContext.close(), 2600)
    } catch {
        // Ignore restricted environments.
    }
}

onMounted(() => {
    updateResponsiveState()
    window.addEventListener('resize', updateResponsiveState)
    void loadConversations()
    subscribeAgentMonitorChannel()
    timerId = window.setInterval(() => {
        nowMs.value = Date.now()
    }, 1000)
})

onBeforeUnmount(() => {
    window.removeEventListener('resize', updateResponsiveState)
    unsubscribeAgentMonitorChannel()
    document.body.style.overflow = ''
    if (timerId !== null) {
        window.clearInterval(timerId)
    }
})

watch(isFullscreenChat, (fullscreen) => {
    document.body.style.overflow = fullscreen ? 'hidden' : ''
})
</script>

<style scoped>
.agent-monitor-grid {
    display: grid;
    grid-template-columns: minmax(340px, 460px) minmax(540px, 1fr);
    grid-template-areas: 'lists chat';
    gap: 1rem;
    height: max(650px, calc(100dvh - 220px));
    min-height: 650px;
}

@supports not (height: 100dvh) {
    .agent-monitor-grid {
        height: max(650px, calc(100vh - 220px));
    }
}

.agent-monitor-grid--stacked {
    grid-template-columns: 1fr;
    grid-template-areas: 'chat';
}

.agent-monitor-grid--lists-only {
    display: block;
    height: auto;
    min-height: 0;
}

.agent-monitor-chat {
    grid-area: chat;
    height: 100%;
    min-height: 0;
}

.agent-monitor-chat--fullscreen {
    position: fixed;
    inset: 0;
    z-index: 1200;
    width: 100vw;
    height: 100dvh;
    min-height: 100dvh;
    padding: 0;
}

.agent-monitor-chat--fullscreen :deep(.admin-chat) {
    border-radius: 0;
    height: 100%;
    max-height: 100%;
}

.agent-monitor-lists {
    grid-area: lists;
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    height: 100%;
    min-height: 0;
}

.agent-monitor-list-card {
    display: flex;
    flex-direction: column;
    min-height: 0;
}

.agent-monitor-list-card--waiting {
    flex: 0 1 auto;
    max-height: 50%;
}

.agent-monitor-list-card--active {
    flex: 1 1 auto;
}

.agent-monitor-list-scroll {
    flex: 1 1 auto;
    min-height: 0;
    overflow-y: auto;
    overscroll-behavior: contain;
    -webkit-overflow-scrolling: touch;
    touch-action: pan-y;
    scrollbar-width: thin;
    scrollbar-color: #c3c8d4 transparent;
}

.agent-monitor-list-scroll::-webkit-scrollbar {
    width: 8px;
}

.agent-monitor-list-scroll::-webkit-scrollbar-thumb {
    background: #c3c8d4;
    border-radius: 999px;
}

.agent-monitor-list-scroll::-webkit-scrollbar-thumb:hover {
    background: #9ea5b6;
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

@media (max-width: 999px) {
    .agent-monitor {
        padding-left: 0.4rem;
        padding-right: 0.4rem;
    }

    .agent-monitor-grid {
        height: auto;
        min-height: 0;
    }

    .agent-monitor-lists {
        display: block;
        height: auto;
    }

    .agent-monitor-list-card--waiting,
    .agent-monitor-list-card--active {
        flex: none;
        max-height: none;
        margin-bottom: 0.75rem;
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

</style>
