<template>
    <div class="chat-widget">
        <button
            v-if="!isOpen"
            type="button"
            class="chat-toggle-btn"
            :class="{ 'chat-toggle-btn--mobile': isMobile }"
            @click="openChat"
        >
            <i class="bi bi-chat-dots-fill me-2" />
            <span v-if="!isMobile">{{ t('chat.toggleDesktop') }}</span>
        </button>

        <section
            v-else
            class="chat-panel card border-0 shadow"
            :class="{ 'chat-panel--mobile': isMobile }"
        >
            <header class="chat-panel__header d-flex align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <div class="chat-logo rounded-circle d-flex align-items-center justify-content-center">
                        <i class="bi bi-robot text-white" />
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <div>
                            <h2 class="chat-title mb-0">{{ t('chat.title') }}</h2>
                            <small class="text-white-50">
                                {{ chatSubtitle }}
                            </small>
                        </div>
                        <span
                            v-if="showAgentStatusDot"
                            class="chat-status-dot"
                            :class="conversationStatus === ConversationStatus.ASSIGNED ? 'chat-status-dot--green' : 'chat-status-dot--red'"
                            :title="chatSubtitle"
                            aria-hidden="true"
                        />
                    </div>
                </div>

                <div class="ms-auto d-flex align-items-center gap-2">
                    <button
                        type="button"
                        class="btn btn-sm btn-light"
                        :disabled="waitingForBotResponse || actionLoading"
                        @click="toggleConnectionTarget"
                    >
                        {{
                            t(
                                connectionTarget === 'bot'
                                    ? 'chat.switchToAgent'
                                    : 'chat.switchToBot'
                            )
                        }}
                    </button>
                    <button
                        type="button"
                        class="btn btn-sm btn-outline-light"
                        :disabled="speechRecognition.isListening.value"
                        :aria-label="t('chat.closeChat')"
                        @click="closeChat"
                    >
                        <i class="bi bi-chevron-down" />
                    </button>
                </div>
            </header>

            <div class="chat-panel__messages-wrapper">
                <div ref="messagesContainer" class="chat-panel__messages">
                    <div
                        v-for="message in orderedMessages"
                    :key="message.id"
                    class="message-row"
                    :class="messageRowClass(message)"
                >
                    <div class="message-bubble" :class="messageBubbleClass(message)">
                        <div class="message-meta">
                            <span class="message-sender">{{ senderLabel(message) }}</span>
                            <span class="message-dot">-</span>
                            <span>{{ formatDateTime(message.created_at) }}</span>
                        </div>
                        <p class="mb-0">{{ message.message_text ?? '' }}</p>
                    </div>
                </div>
            </div>
                <div
                    v-if="speechRecognition.isListening.value"
                    class="chat-panel__messages-overlay"
                    aria-hidden="true"
                    role="button"
                    :aria-label="t('chat.stopRecording')"
                    tabindex="0"
                    @click.stop="stopSpeechInput"
                    @keydown.enter.space.prevent="stopSpeechInput"
                >
                    <i class="bi bi-mic-fill chat-panel__listening-icon" />
                </div>
            </div>

            <form class="chat-panel__form" @submit.prevent="submitMessage">
                <div class="position-relative chat-input-wrapper">
                    <input
                        ref="chatInputRef"
                        :value="displayedDraft"
                        type="text"
                        class="form-control chat-input-with-actions"
                        :placeholder="t('chat.inputPlaceholder')"
                        :disabled="waitingForBotResponse || isWaitingForAgent"
                        autocomplete="off"
                        @input="onDraftInput"
                        @focus="onInputFocus"
                    />
                    <div
                        class="position-absolute top-50 end-0 translate-middle-y chat-input-actions d-flex align-items-center gap-1 pe-2"
                        @click="chatInputRef?.focus()"
                    >
                        <span
                            v-if="waitingForBotResponse"
                            class="text-primary chat-loading-spinner"
                            role="status"
                            aria-hidden="true"
                        />
                        <button
                            v-if="speechRecognition.isSupported"
                            type="button"
                            class="btn btn-link btn-sm p-1 text-secondary chat-mic-btn"
                            :class="{ 'chat-mic-btn--active': speechRecognition.isListening.value }"
                            :disabled="waitingForBotResponse || isWaitingForAgent"
                            :title="t('chat.speechToText')"
                            :aria-label="t('chat.speechToText')"
                            @click.stop.prevent="toggleSpeechInput"
                        >
                            <i class="bi" :class="speechRecognition.isListening.value ? 'bi-mic-mute-fill' : 'bi-mic-fill'" />
                        </button>
                    </div>
                </div>
                <button
                    type="submit"
                    class="btn btn-primary"
                    :disabled="!canSubmit || waitingForBotResponse || isWaitingForAgent"
                >
                    <i class="bi bi-send-fill" />
                </button>
            </form>
        </section>
    </div>
</template>

<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useSpeechRecognition } from '../composables/useSpeechRecognition'
import {
    callAgentRequest,
    cancelCallRequest,
    closeUserCommunicationRequest,
    getUserConversationRequest,
    sendUserMessageRequest,
} from '../api/communication'
import type { ConversationMessageBroadcastPayload, ConversationMessageItem } from '../types/communication'
import { ConversationMessageSenderType, ConversationMessageType, ConversationStatus } from '../types/enums'
import { getApiErrorMessage } from '../utils/apiErrorMessage'

type ConnectionTarget = 'bot' | 'agent'

const MOBILE_BREAKPOINT_PX = 900
const SEND_THROTTLE_MS = 1000
const INACTIVITY_DISCONNECT_MS = 150_000
const BOT_RESPONSE_TIMEOUT_MS = 30_000

const OLLAMA_UNAVAILABLE_FALLBACK_TEXT = 'The assistant is temporarily unavailable. Please try again later.'

const { t } = useI18n()

const isOpen = ref(false)
const isMobile = ref(false)
const draftMessage = ref('')
const connectionTarget = ref<ConnectionTarget>('bot')
const messagesContainer = ref<HTMLElement | null>(null)
const hasInitialOpenScroll = ref(false)
const savedScrollTop = ref<number | null>(null)
const messageFeed = ref<ConversationMessageItem[]>([])
const conversationId = ref<number | null>(null)
const conversationStatus = ref<ConversationStatus>(ConversationStatus.OPEN)
const waitingForBotResponse = ref(false)
const actionLoading = ref(false)
const lastSentAtMs = ref(0)
const subscribedConversationId = ref<number | null>(null)
const loadingTimeoutHandle = ref<number | null>(null)
const inactivityTimeoutHandle = ref<number | null>(null)
const chatInputRef = ref<HTMLInputElement | null>(null)
const interimTranscript = ref('')

const speechRecognition = useSpeechRecognition({
    onFinalTranscript(text) {
        const trimmed = text.trim()
        if (!trimmed) return
        draftMessage.value = draftMessage.value ? `${draftMessage.value} ${trimmed}` : trimmed
        interimTranscript.value = ''
    },
    onInterimTranscript(text) {
        interimTranscript.value = text
    },
})

const displayedDraft = computed(
    () =>
        draftMessage.value +
        (interimTranscript.value ? ` ${interimTranscript.value}` : '')
)

function onDraftInput(event: Event): void {
    const target = event.target as HTMLInputElement
    draftMessage.value = target.value
    interimTranscript.value = ''
    onInputActivity()
}

function toggleSpeechInput(): void {
    if (waitingForBotResponse.value || isWaitingForAgent.value) return
    speechRecognition.toggle()
}

function stopSpeechInput(): void {
    if (!speechRecognition.isListening.value) return
    speechRecognition.stop()
    flushInterimToDraft()
}

function flushInterimToDraft(): void {
    if (interimTranscript.value) {
        draftMessage.value = (
            draftMessage.value +
            (draftMessage.value ? ' ' : '') +
            interimTranscript.value
        ).trim()
        interimTranscript.value = ''
    }
}

const orderedMessages = computed(() =>
    [...messageFeed.value].sort(
        (messageA, messageB) =>
            new Date(messageA.created_at).getTime() -
            new Date(messageB.created_at).getTime()
    )
)

const canSubmit = computed(
    () => displayedDraft.value.trim().length > 0 && !actionLoading.value
)
const isWaitingForAgent = computed(
    () => conversationStatus.value === ConversationStatus.WAITING_HUMAN
)
const chatSubtitle = computed(() => {
    if (conversationStatus.value === ConversationStatus.WAITING_HUMAN) {
        return t('chat.waitingForAgent')
    }
    if (conversationStatus.value === ConversationStatus.ASSIGNED) {
        return t('chat.connectedToAgent')
    }
    return connectionTarget.value === 'bot' ? t('chat.connectedToBot') : t('chat.connectedToAgent')
})
const showAgentStatusDot = computed(
    () =>
        conversationStatus.value === ConversationStatus.WAITING_HUMAN ||
        conversationStatus.value === ConversationStatus.ASSIGNED
)

function updateScreenState(): void {
    isMobile.value = window.innerWidth < MOBILE_BREAKPOINT_PX
}

function openChat(): void {
    isOpen.value = true
}

function closeChat(): void {
    if (messagesContainer.value) {
        savedScrollTop.value = messagesContainer.value.scrollTop
    }
    isOpen.value = false
}

async function toggleConnectionTarget(): Promise<void> {
    if (actionLoading.value || waitingForBotResponse.value) {
        return
    }

    actionLoading.value = true
    try {
        if (connectionTarget.value === 'bot') {
            const payload = await callAgentRequest({ conversation_id: conversationId.value })
            hydrateConversation(payload)
            stopInactivityDisconnectTimer()
        } else {
            const payload = await cancelCallRequest({ conversation_id: conversationId.value })
            hydrateConversation(payload)
            resetInactivityDisconnectTimer()
        }
    } catch (error) {
        pushSystemMessage(getApiErrorMessage(t, error, 'errors.unexpected'))
    } finally {
        actionLoading.value = false
    }
}

async function submitMessage(): Promise<void> {
    const content = displayedDraft.value.trim()
    if (!content || waitingForBotResponse.value || actionLoading.value) {
        return
    }

    const nowMs = Date.now()
    if (nowMs - lastSentAtMs.value < SEND_THROTTLE_MS) {
        return
    }

    actionLoading.value = true
    lastSentAtMs.value = nowMs
    resetInactivityDisconnectTimer()
    const localMessageText = content

    try {
        const acknowledgment = await sendUserMessageRequest({
            conversation_id: conversationId.value,
            conversation_status: conversationStatus.value,
            message_text: content,
        })
        draftMessage.value = ''
        interimTranscript.value = ''
        conversationId.value = acknowledgment.conversation_id
        conversationStatus.value = acknowledgment.status
        connectionTarget.value =
            conversationStatus.value === ConversationStatus.WAITING_HUMAN ||
            conversationStatus.value === ConversationStatus.ASSIGNED
                ? 'agent'
                : 'bot'

        appendLocalUserMessage(localMessageText)

        if (conversationStatus.value === ConversationStatus.OPEN) {
            waitingForBotResponse.value = true
            startBotResponseTimeout()
            stopInactivityDisconnectTimer()
        }

        try {
            const conversationPayload = await getUserConversationRequest()
            hydrateConversation(conversationPayload)
        } catch {
        }
    } catch (error) {
        pushSystemMessage(getApiErrorMessage(t, error, 'errors.unexpected'))
    } finally {
        actionLoading.value = false
    }
}

function appendLocalUserMessage(messageText: string): void {
    const optimisticId = -Date.now()
    messageFeed.value.push({
        id: optimisticId,
        sender_type: ConversationMessageSenderType.USER,
        message_type: ConversationMessageType.QUESTION,
        message_text: messageText,
        sender_user_id: null,
        created_at: new Date().toISOString(),
    })
}

function shouldDisplayAsSystemMessage(message: ConversationMessageItem): boolean {
    return (
        message.sender_type === ConversationMessageSenderType.BOT &&
        message.message_text === OLLAMA_UNAVAILABLE_FALLBACK_TEXT
    )
}

function senderLabel(message: ConversationMessageItem): string {
    if (shouldDisplayAsSystemMessage(message)) {
        return t('chat.senderSystem')
    }
    switch (message.sender_type) {
        case ConversationMessageSenderType.USER:
            return t('chat.senderUser')
        case ConversationMessageSenderType.BOT:
            return t('chat.senderBot')
        case ConversationMessageSenderType.AGENT:
            return t('chat.senderAgent')
        case ConversationMessageSenderType.SYSTEM:
            return t('chat.senderSystem')
        default:
            return t('chat.senderSystem')
    }
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

function messageRowClass(message: ConversationMessageItem): string {
    if (shouldDisplayAsSystemMessage(message)) {
        return 'message-row--center'
    }
    if (
        message.message_type === ConversationMessageType.SYSTEM_NOTICE ||
        message.message_type === ConversationMessageType.SYSTEM_ERROR
    ) {
        return 'message-row--center'
    }

    if (message.message_type === ConversationMessageType.QUESTION) {
        return 'message-row--right'
    }

    return 'message-row--left'
}

function messageBubbleClass(message: ConversationMessageItem): string {
    if (shouldDisplayAsSystemMessage(message)) {
        return 'message-bubble--system'
    }
    switch (message.message_type) {
        case ConversationMessageType.QUESTION:
            return 'message-bubble--question'
        case ConversationMessageType.BOT_ANSWER:
            return 'message-bubble--bot'
        case ConversationMessageType.AGENT_ANSWER:
            return 'message-bubble--agent'
        case ConversationMessageType.SYSTEM_NOTICE:
        case ConversationMessageType.SYSTEM_ERROR:
            return 'message-bubble--system'
        default:
            return 'message-bubble--bot'
    }
}

function onInputActivity(): void {
    if (conversationStatus.value === ConversationStatus.OPEN && !waitingForBotResponse.value) {
        resetInactivityDisconnectTimer()
    }
}

function onInputFocus(): void {
    void connectToConversationChannel()
    if (conversationStatus.value === ConversationStatus.OPEN && !waitingForBotResponse.value) {
        resetInactivityDisconnectTimer()
    }
}

function hydrateConversation(payload: {
    conversation_id: number | null
    status: ConversationStatus | null
    messages: ConversationMessageItem[]
}): void {
    conversationId.value = payload.conversation_id
    conversationStatus.value = payload.status ?? ConversationStatus.OPEN
    messageFeed.value = payload.messages
    connectionTarget.value =
        conversationStatus.value === ConversationStatus.WAITING_HUMAN ||
        conversationStatus.value === ConversationStatus.ASSIGNED
            ? 'agent'
            : 'bot'

    if (conversationStatus.value !== ConversationStatus.OPEN) {
        waitingForBotResponse.value = false
        clearBotResponseTimeout()
    } else if (payload.messages.length > 0) {
        const lastMessage = payload.messages[payload.messages.length - 1]
        const lastIsFromBotOrSystem =
            lastMessage.sender_type === ConversationMessageSenderType.BOT ||
            lastMessage.sender_type === ConversationMessageSenderType.AGENT ||
            lastMessage.sender_type === ConversationMessageSenderType.SYSTEM
        if (lastIsFromBotOrSystem) {
            waitingForBotResponse.value = false
            clearBotResponseTimeout()
        }
    }
}

function pushSystemMessage(messageText: string): void {
    const fallbackId = Math.floor(Math.random() * 1_000_000_000)
    messageFeed.value.push({
        id: fallbackId,
        sender_type: ConversationMessageSenderType.SYSTEM,
        message_type: ConversationMessageType.SYSTEM_NOTICE,
        message_text: messageText,
        sender_user_id: null,
        created_at: new Date().toISOString(),
    })
}

async function loadConversation(): Promise<void> {
    try {
        const payload = await getUserConversationRequest()
        hydrateConversation(payload)
    } catch {
        messageFeed.value = []
    }
}

async function connectToConversationChannel(): Promise<void> {
    if (!conversationId.value || subscribedConversationId.value === conversationId.value) {
        return
    }

    disconnectConversationChannel()

    const channelName = `conversation.${conversationId.value}`

    function handleStatusChanged(payload: unknown): void {
        const raw = payload as Record<string, unknown>
        const data = (raw?.data ?? raw) as { conversation_id?: number; status?: string } | undefined
        if (!data || typeof data.conversation_id !== 'number' || data.conversation_id !== conversationId.value) {
            return
        }
        const status = data.status as ConversationStatus | undefined
        if (!status) {
            return
        }
        conversationStatus.value = status
        connectionTarget.value =
            status === ConversationStatus.WAITING_HUMAN || status === ConversationStatus.ASSIGNED
                ? 'agent'
                : 'bot'
        resetInactivityDisconnectTimer()
    }

    window.Echo.private(channelName)
        .listen('.conversation.status.changed', handleStatusChanged)
        .listen(
            '.conversation.message.created',
            (payload: ConversationMessageBroadcastPayload) => {
                if (payload.conversation_id !== conversationId.value) {
                    return
                }

                conversationStatus.value = payload.status
                connectionTarget.value =
                    payload.status === ConversationStatus.WAITING_HUMAN ||
                    payload.status === ConversationStatus.ASSIGNED
                        ? 'agent'
                        : 'bot'

                if (payload.message.sender_type === ConversationMessageSenderType.USER) {
                    const optimisticIndex = messageFeed.value.findIndex(
                        (message) =>
                            message.id < 0 && message.message_text === payload.message.message_text
                    )
                    if (optimisticIndex >= 0) {
                        messageFeed.value = messageFeed.value.map((message, index) =>
                            index === optimisticIndex ? payload.message : message
                        )
                    } else {
                        messageFeed.value.push(payload.message)
                    }
                } else if (!messageFeed.value.some((message) => message.id === payload.message.id)) {
                    messageFeed.value.push(payload.message)
                }

                if (
                    payload.message.sender_type === ConversationMessageSenderType.BOT ||
                    payload.message.sender_type === ConversationMessageSenderType.AGENT ||
                    payload.message.sender_type === ConversationMessageSenderType.SYSTEM
                ) {
                    waitingForBotResponse.value = false
                    clearBotResponseTimeout()
                }

                resetInactivityDisconnectTimer()
            }
        )

    subscribedConversationId.value = conversationId.value
}

function disconnectConversationChannel(): void {
    if (!subscribedConversationId.value) {
        return
    }

    window.Echo.leave(`conversation.${subscribedConversationId.value}`)
    subscribedConversationId.value = null
}

async function closeCurrentConversationAndDisconnect(): Promise<void> {
    const idToClose = conversationId.value ?? subscribedConversationId.value
    if (idToClose !== null) {
        closeUserCommunicationRequest({ conversation_id: idToClose }).catch(() => {})
    }
    disconnectConversationChannel()
}

function resetInactivityDisconnectTimer(): void {
    stopInactivityDisconnectTimer()

    if (conversationStatus.value !== ConversationStatus.OPEN || waitingForBotResponse.value) {
        return
    }

    inactivityTimeoutHandle.value = window.setTimeout(() => {
        void closeCurrentConversationAndDisconnect()
    }, INACTIVITY_DISCONNECT_MS)
}

function stopInactivityDisconnectTimer(): void {
    if (inactivityTimeoutHandle.value !== null) {
        window.clearTimeout(inactivityTimeoutHandle.value)
        inactivityTimeoutHandle.value = null
    }
}

function startBotResponseTimeout(): void {
    clearBotResponseTimeout()
    loadingTimeoutHandle.value = window.setTimeout(() => {
        if (!waitingForBotResponse.value) {
            return
        }

        waitingForBotResponse.value = false
        pushSystemMessage(t('chat.timeoutMessage'))
        resetInactivityDisconnectTimer()
    }, BOT_RESPONSE_TIMEOUT_MS)
}

function clearBotResponseTimeout(): void {
    if (loadingTimeoutHandle.value !== null) {
        window.clearTimeout(loadingTimeoutHandle.value)
        loadingTimeoutHandle.value = null
    }
}

watch(
    () => orderedMessages.value.length,
    async () => {
        if (!isOpen.value || !messagesContainer.value) {
            return
        }

        await nextTick()
        messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight
    }
)

watch(isOpen, async (isWidgetOpen) => {
    if (isWidgetOpen && conversationId.value) {
        void connectToConversationChannel()
    }
    if (!isWidgetOpen) {
        return
    }

    await nextTick()
    if (!messagesContainer.value) {
        return
    }

    if (!hasInitialOpenScroll.value) {
        messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight
        hasInitialOpenScroll.value = true
        return
    }

    if (savedScrollTop.value !== null) {
        messagesContainer.value.scrollTop = savedScrollTop.value
    }
})

watch(conversationId, (currentConversationId) => {
    if (currentConversationId && isOpen.value) {
        void connectToConversationChannel()
    }
})

watch(
    () => speechRecognition.isListening.value,
    (isListening, wasListening) => {
        if (wasListening && !isListening) flushInterimToDraft()
    }
)

onMounted(() => {
    updateScreenState()
    window.addEventListener('resize', updateScreenState)
    void loadConversation()
})

onBeforeUnmount(() => {
    speechRecognition.stop()
    stopInactivityDisconnectTimer()
    clearBotResponseTimeout()
    void closeCurrentConversationAndDisconnect()
    window.removeEventListener('resize', updateScreenState)
})
</script>

<style scoped>
.chat-widget {
    position: fixed;
    right: 0.7rem;
    bottom: 0.7rem;
    z-index: 1100;
}

@media (max-width: 900px) {
    .chat-widget {
        right: 0rem;
        bottom: 0rem;
    }
}
.chat-toggle-btn {
    border: 0;
    border-radius: 999px;
    background: #001dff;
    color: #fff;
    min-height: 3.25rem;
    min-width: 8rem;
    padding: 0.65rem 1.1rem;
    box-shadow: 0 10px 25px rgba(0, 29, 255, 0.28);
    font-weight: 700;
}

.chat-toggle-btn--mobile {
    min-width: 3.25rem;
    width: 3.25rem;
    height: 3.25rem;
    border-radius: 999px;
    padding: 0;
}

.chat-toggle-btn--mobile .bi {
    margin-right: 0 !important;
}

.chat-panel {
    width: min(420px, calc(100vw - 2rem));
    height: min(620px, calc(100vh - 2rem));
    display: flex;
    flex-direction: column;
    border-radius: 0.9rem;
    overflow: hidden;
}

.chat-panel--mobile {
    width: 100vw;
    height: 100vh;
    border-radius: 0;
}

.chat-panel__header {
    background: linear-gradient(120deg, #001dff 0%, #2c44ff 100%);
    color: #fff;
    padding: 0.85rem 1rem;
}

.chat-logo {
    width: 2rem;
    height: 2rem;
    background: rgba(255, 255, 255, 0.25);
}

.chat-title {
    font-size: 1rem;
    font-weight: 700;
}

.chat-panel__messages-wrapper {
    position: relative;
    flex: 1 1 auto;
    min-height: 0;
    display: flex;
    flex-direction: column;
}

.chat-panel__messages {
    background: #f7f8fc;
    overflow-y: auto;
    flex: 1 1 auto;
    min-height: 0;
    padding: 0.95rem;
    overscroll-behavior: contain;
    -webkit-overflow-scrolling: touch;
    touch-action: pan-y;
}

.chat-panel__messages-overlay {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.6);
    backdrop-filter: blur(6px);
    -webkit-backdrop-filter: blur(6px);
    cursor: pointer;
}

.chat-panel__listening-icon {
    font-size: 3.5rem;
    color: #0d6efd;
    opacity: 1;
}

.message-row {
    display: flex;
    margin-bottom: 0.6rem;
}

.message-row--left {
    justify-content: flex-start;
}

.message-row--right {
    justify-content: flex-end;
}

.message-row--center {
    justify-content: center;
}

.message-bubble {
    max-width: 78%;
    border-radius: 0.8rem;
    padding: 0.55rem 0.7rem;
    font-size: 0.9rem;
    line-height: 1.35;
}

.message-bubble--question {
    background: #0d6efd;
    color: #fff;
}

.message-bubble--bot {
    background: #e9edf3;
    color: #0f172a;
}

.message-bubble--agent {
    background: #ddf7e5;
    color: #0f5132;
}

.message-bubble--system {
    background: #fff4cf;
    color: #6a4a00;
    max-width: 92%;
}

.message-meta {
    font-size: 0.72rem;
    margin-bottom: 0.3rem;
    opacity: 0.8;
}

.message-sender {
    font-weight: 700;
}

.message-dot {
    margin: 0 0.25rem;
}

.chat-panel__form {
    border-top: 1px solid #e5e7eb;
    background: #fff;
    padding: 0.8rem;
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 0.55rem;
}

.chat-input-wrapper {
    position: relative;
}

.chat-input-with-actions {
    padding-right: 4.5rem;
}

.chat-input-actions {
    z-index: 2;
    cursor: text;
}

.chat-input-actions .chat-mic-btn {
    cursor: pointer;
}

.chat-mic-btn {
    min-width: 2.25rem;
    min-height: 2.25rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.chat-mic-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.chat-mic-btn--active {
    color: var(--bs-primary) !important;
}

.chat-loading-spinner {
    display: inline-flex;
    width: 0.9rem;
    height: 0.9rem;
}

.chat-loading-spinner::before {
    content: '';
    width: 100%;
    height: 100%;
    border-radius: 50%;
    border: 2px solid currentColor;
    border-right-color: transparent;
    animation: chat-spin 0.8s linear infinite;
}

@keyframes chat-spin {
    to {
        transform: rotate(360deg);
    }
}

.chat-status-dot {
    flex-shrink: 0;
    width: 0.5rem;
    height: 0.5rem;
    border-radius: 50%;
}

.chat-status-dot--red {
    background-color: #ff4444;
}

.chat-status-dot--green {
    background-color: #22c55e;
}
</style>
