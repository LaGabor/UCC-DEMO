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
                                        {{
                                            formatElapsed(
                                                conversation.status === ConversationStatus.OPEN
                                                    ? conversation.startedAt
                                                    : (conversation.activeSinceAt ?? conversation.startedAt)
                                            )
                                        }}
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
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import AdminChatWidget from '../components/AdminChatWidget.vue'
import { fetchAdminOrHelpdeskAgentEligibility } from '../api/auth'
import { useAuth } from '../auth'
import {
    getAgentMonitorConversationsRequest,
    getAgentMonitorViewUserChatHistoryRequest,
    postAgentMonitorAnswerUserChatHistoryRequest,
    postAgentMonitorCloseAssignedRequest,
    postAgentMonitorCloseWaitingHumanRequest,
    postAgentMonitorSendAgentMessageRequest,
} from '../api/agentMonitor'
import type {
    AgentMonitorConversationBatchBroadcastPayload,
    AgentMonitorConversationBroadcastPayload,
    AgentMonitorConversationHistoryMessage,
} from '../types/communication'
import {
    ConversationBroadcastType,
    ConversationMessageSenderType,
    ConversationMessageType,
    ConversationStatus,
    UserRole,
} from '../types/enums'
import { ROUTE_NAMES } from '../router'

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

function historyMessageToMonitorMessage(
    historyMessage: AgentMonitorConversationHistoryMessage
): MonitorMessage {
    return {
        id: String(historyMessage.id),
        sender: historyMessage.sender_type as MonitorMessage['sender'],
        message_type: historyMessage.message_type as MonitorMessage['message_type'],
        content: historyMessage.message_text ?? '',
        created_at: historyMessage.created_at,
    }
}

function setConversationMessages(conversationId: number, messages: MonitorMessage[]): void {
    const conversationIndex = conversations.value.findIndex(
        (conversation) => conversation.id === conversationId
    )
    if (conversationIndex >= 0) {
        const updatedConversations = [...conversations.value]
        updatedConversations[conversationIndex] = {
            ...updatedConversations[conversationIndex],
            messages,
        }
        conversations.value = updatedConversations
    }
}

function payloadToMonitorConversation(
    payload: AgentMonitorConversationBroadcastPayload | {
        conversation_id: number
        user_id: number
        user_name: string
        assigned_agent_id: number | null
        status: string
        created_at: string
        last_assign_request: string | null
        last_assigned_at: string | null
        last_closed_at: string | null
        last_open_at: string | null
        last_message_at: string | null
    }
): MonitorConversation {
    const status = payload.status as MonitorConversationStatus
    const startedAt =
        status === ConversationStatus.WAITING_HUMAN && payload.last_assign_request
            ? payload.last_assign_request
            : status === ConversationStatus.OPEN && payload.last_open_at
              ? payload.last_open_at
              : status === ConversationStatus.ASSIGNED && payload.last_assigned_at
                ? payload.last_assigned_at
                : payload.created_at

    return {
        id: payload.conversation_id,
        requesterName: payload.user_name,
        status,
        startedAt,
        activeSinceAt: payload.last_assigned_at ?? undefined,
        assignedToUserId: payload.assigned_agent_id ?? undefined,
        messages: [],
    }
}

const MOBILE_BREAKPOINT_PX = 970
const ACTIVE_LIST_PAGE_SIZE = 10

const { t } = useI18n()
const router = useRouter()
const auth = useAuth()

const nowMs = ref(Date.now())
const isCompact = ref(false)
const activeListPage = ref(1)
const selectedConversationId = ref<number | null>(null)

const conversations = ref<MonitorConversation[]>([])

async function loadConversations(): Promise<void> {
    try {
        const list = await getAgentMonitorConversationsRequest()
        conversations.value = list.map((payload) => payloadToMonitorConversation(payload))
    } catch {
        conversations.value = []
    }
}

function applySingleStatusChange(payload: AgentMonitorConversationBroadcastPayload): void {
    if (payload.status === ConversationStatus.CLOSED) {
        conversations.value = conversations.value.filter(
            (conversation) => conversation.id !== payload.conversation_id
        )
        if (selectedConversationId.value === payload.conversation_id) {
            selectedConversationId.value = null
        }
        return
    }

    const existingConversationIndex = conversations.value.findIndex(
        (conversation) => conversation.id === payload.conversation_id
    )
    const conversationFromPayload = payloadToMonitorConversation(payload)

    if (existingConversationIndex >= 0) {
        const existingConversation = conversations.value[existingConversationIndex]
        conversationFromPayload.messages = existingConversation.messages
        conversations.value = conversations.value.map((conversation) =>
            conversation.id === payload.conversation_id ? conversationFromPayload : conversation
        )
    } else {
        conversations.value = [conversationFromPayload, ...conversations.value]
    }

    if (
        payload.status === ConversationStatus.WAITING_HUMAN &&
        !selectedConversation.value
    ) {
        playIncomingCallTone()
    }
}

function subscribeAgentMonitorChannel(): void {
    const channel = window.Echo.private('agent-monitor')

    channel.listen(
        '.conversation.status.updated',
        (payload: AgentMonitorConversationBroadcastPayload) => {
            applySingleStatusChange(payload)
        }
    )

    channel.listen(
        '.conversation.status.batch.updated',
        (payload: AgentMonitorConversationBatchBroadcastPayload) => {
            if (payload.type !== ConversationBroadcastType.STATUS_CHANGE_OBJECT) {
                return
            }
            for (const item of payload.conversations) {
                applySingleStatusChange(item)
            }
        }
    )
}

function unsubscribeAgentMonitorChannel(): void {
    window.Echo.leave('agent-monitor')
}

let conversationChannelSubscribedId: number | null = null

function subscribeToConversationChannel(conversationId: number): void {
    if (conversationChannelSubscribedId === conversationId) {
        return
    }
    if (conversationChannelSubscribedId !== null) {
        window.Echo.leave(`conversation.${conversationChannelSubscribedId}`)
        conversationChannelSubscribedId = null
    }
    const channelName = `conversation.${conversationId}`
    window.Echo.private(channelName).listen(
        '.conversation.message.created',
        (payload: { conversation_id: number; message: { id: number; sender_type: string; message_type: string; message_text: string | null; sender_user_id: number | null; created_at: string } }) => {
            if (payload.conversation_id !== selectedConversationId.value) {
                return
            }
            const msg = payload.message
            const isOwnAgentMessage =
                msg.sender_type === ConversationMessageSenderType.AGENT &&
                msg.sender_user_id === currentUserId.value
            if (isOwnAgentMessage) {
                return
            }
            const conversationIndex = conversations.value.findIndex(
                (conversation) => conversation.id === payload.conversation_id
            )
            if (conversationIndex < 0) {
                return
            }
            const conversation = conversations.value[conversationIndex]
            const newMessage: MonitorMessage = {
                id: String(msg.id),
                sender: msg.sender_type as MonitorMessage['sender'],
                message_type: msg.message_type as MonitorMessage['message_type'],
                content: msg.message_text ?? '',
                created_at: msg.created_at,
            }
            if (conversation.messages.some((message) => message.id === newMessage.id)) {
                return
            }
            const updatedConversations = [...conversations.value]
            updatedConversations[conversationIndex] = {
                ...updatedConversations[conversationIndex],
                messages: [...updatedConversations[conversationIndex].messages, newMessage],
            }
            conversations.value = updatedConversations
        }
    )
    conversationChannelSubscribedId = conversationId
}

function unsubscribeFromConversationChannel(): void {
    if (conversationChannelSubscribedId !== null) {
        window.Echo.leave(`conversation.${conversationChannelSubscribedId}`)
        conversationChannelSubscribedId = null
    }
}

const waitingAgentConversations = computed(() =>
    conversations.value
        .filter(
            (item) =>
                item.status === ConversationStatus.WAITING_HUMAN ||
                (item.status === ConversationStatus.ASSIGNED && isAssignedToCurrentUser(item))
        )
        .sort(
            (conversationA, conversationB) =>
                new Date(conversationA.startedAt).getTime() -
                new Date(conversationB.startedAt).getTime()
        )
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
                const isSelectedAndAnsweredByMe =
                    item.id === selectedConversationId.value &&
                    item.status === ConversationStatus.ASSIGNED &&
                    isAssignedToCurrentUser(item)
                if (isSelectedAndAnsweredByMe) {
                    return false
                }
                if (item.status === ConversationStatus.OPEN) {
                    return true
                }

                if (item.status === ConversationStatus.ASSIGNED) {
                    return canViewAssignedConversation(item)
                }

                return false
            }
        )
        .sort(
            (conversationA, conversationB) =>
                new Date(conversationB.startedAt).getTime() -
                new Date(conversationA.startedAt).getTime()
        )
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

async function answerWaitingConversation(conversationId: number): Promise<void> {
    const conversation = conversations.value.find((item) => item.id === conversationId)
    if (!conversation || conversation.status !== ConversationStatus.WAITING_HUMAN) {
        return
    }

    try {
        const data = await postAgentMonitorAnswerUserChatHistoryRequest(conversationId)
        const messages = data.messages.map(historyMessageToMonitorMessage)
        setConversationMessages(conversationId, messages)
    } catch {
        return
    }

    selectedConversationId.value = conversationId
}

async function openConversation(conversationId: number): Promise<void> {
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

    try {
        const data = await getAgentMonitorViewUserChatHistoryRequest(conversationId)
        const messages = data.messages.map(historyMessageToMonitorMessage)
        setConversationMessages(conversationId, messages)
    } catch {
    }

    selectedConversationId.value = conversationId
}

function closeSelectedConversation(): void {
    if (selectedConversationId.value !== null) {
        finalizeConversationOnLeave(selectedConversationId.value)
    }
    selectedConversationId.value = null
}

async function endAssignedConversation(): Promise<void> {
    if (
        !selectedConversation.value ||
        selectedConversation.value.status !== ConversationStatus.ASSIGNED ||
        !canManageAssignedConversation(selectedConversation.value)
    ) {
        return
    }

    const conversationId = selectedConversation.value.id
    try {
        await postAgentMonitorCloseWaitingHumanRequest(conversationId)
    } catch {
    }
    selectedConversation.value.status = ConversationStatus.OPEN
    selectedConversation.value.activeSinceAt = null
    selectedConversation.value.assignedToUserId = null
    selectedConversation.value.messages.push({
        id: `${conversationId}-end-${Date.now()}`,
        sender: ConversationMessageSenderType.SYSTEM,
        message_type: ConversationMessageType.SYSTEM_NOTICE,
        content: 'System: Agent communication finished. Returning to bot mode.',
        created_at: new Date().toISOString(),
    })
    selectedConversationId.value = null
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
        postAgentMonitorCloseAssignedRequest(conversationId).catch(() => {})
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

async function sendAgentMessage(content: string): Promise<void> {
    if (
        !selectedConversation.value ||
        selectedConversation.value.status !== ConversationStatus.ASSIGNED ||
        !canManageAssignedConversation(selectedConversation.value)
    ) {
        return
    }

    const conversationId = selectedConversation.value.id
    const tempId = `${conversationId}-${Date.now()}`
    selectedConversation.value.messages.push({
        id: tempId,
        sender: ConversationMessageSenderType.AGENT,
        message_type: ConversationMessageType.AGENT_ANSWER,
        content,
        created_at: new Date().toISOString(),
    })
    try {
        await postAgentMonitorSendAgentMessageRequest(conversationId, content)
    } catch {
        const conversationIndex = conversations.value.findIndex(
            (conversation) => conversation.id === conversationId
        )
        if (conversationIndex >= 0) {
            const updatedConversations = [...conversations.value]
            updatedConversations[conversationIndex] = {
                ...updatedConversations[conversationIndex],
                messages: updatedConversations[conversationIndex].messages.filter(
                    (message) => message.id !== tempId
                ),
            }
            conversations.value = updatedConversations
        }
    }
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

onMounted(async () => {
    const canAccess = await fetchAdminOrHelpdeskAgentEligibility()
    if (!canAccess) {
        await router.replace({ name: ROUTE_NAMES.HOME })
        return
    }
    updateResponsiveState()
    window.addEventListener('resize', updateResponsiveState)
    void loadConversations()
    subscribeAgentMonitorChannel()
    timerId = window.setInterval(() => {
        nowMs.value = Date.now()
    }, 1000)
})

watch(selectedConversationId, (conversationId) => {
    if (conversationId !== null) {
        subscribeToConversationChannel(conversationId)
    } else {
        unsubscribeFromConversationChannel()
    }
})

onBeforeUnmount(() => {
    const selectedId = selectedConversationId.value
    if (selectedId !== null) {
        const conversation = conversations.value.find(
            (item) => item.id === selectedId
        )
        if (
            conversation &&
            conversation.status === ConversationStatus.ASSIGNED &&
            canManageAssignedConversation(conversation)
        ) {
            postAgentMonitorCloseAssignedRequest(selectedId).catch(() => {})
        }
    }
    unsubscribeFromConversationChannel()
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
