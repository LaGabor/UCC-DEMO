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
                    <div>
                        <h2 class="chat-title mb-0">{{ t('chat.title') }}</h2>
                        <small class="text-white-50">
                            {{ t(connectionTarget === 'bot' ? 'chat.connectedToBot' : 'chat.connectedToAgent') }}
                        </small>
                    </div>
                </div>

                <div class="ms-auto d-flex align-items-center gap-2">
                    <button
                        type="button"
                        class="btn btn-sm btn-light"
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
                        @click="closeChat"
                    >
                        <i class="bi bi-chevron-down" />
                    </button>
                </div>
            </header>

            <div ref="messagesContainer" class="chat-panel__messages">
                <div
                    v-for="message in orderedMessages"
                    :key="message.id"
                    class="message-row"
                    :class="messageRowClass(message)"
                >
                    <div class="message-bubble" :class="messageBubbleClass(message)">
                        <div class="message-meta">
                            <span class="message-sender">{{ senderLabel(message.sender) }}</span>
                            <span class="message-dot">-</span>
                            <span>{{ formatDateTime(message.created_at) }}</span>
                        </div>
                        <p class="mb-0">{{ message.content }}</p>
                    </div>
                </div>
            </div>

            <form class="chat-panel__form" @submit.prevent="submitMessage">
                <input
                    v-model="draftMessage"
                    type="text"
                    class="form-control"
                    :placeholder="t('chat.inputPlaceholder')"
                    autocomplete="off"
                />
                <button type="submit" class="btn btn-primary" :disabled="!canSubmit">
                    <i class="bi bi-send-fill" />
                </button>
            </form>
        </section>
    </div>
</template>

<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { ConversationMessageSenderType, ConversationMessageType } from '../types/enums'

type ConnectionTarget = 'bot' | 'agent'

type ChatMessage = {
    id: string
    sender: ConversationMessageSenderType
    message_type: ConversationMessageType
    content: string
    created_at: string
}

const MOBILE_BREAKPOINT_PX = 900

const { t } = useI18n()

const isOpen = ref(false)
const isMobile = ref(false)
const draftMessage = ref('')
const connectionTarget = ref<ConnectionTarget>('bot')
const messagesContainer = ref<HTMLElement | null>(null)

const messageFeed = ref<ChatMessage[]>([
    {
        id: 'msg-1',
        sender: ConversationMessageSenderType.SYSTEM,
        message_type: ConversationMessageType.SYSTEM_NOTICE,
        content: 'System: This conversation has started with bot support.',
        created_at: '2026-03-10T08:00:00Z',
    },
    {
        id: 'msg-2',
        sender: ConversationMessageSenderType.USER,
        message_type: ConversationMessageType.QUESTION,
        content: 'Hi, I need help with my account.',
        created_at: '2026-03-10T08:01:00Z',
    },
    {
        id: 'msg-3',
        sender: ConversationMessageSenderType.BOT,
        message_type: ConversationMessageType.BOT_ANSWER,
        content: 'Sure, I can help. Can you tell me what issue you are seeing?',
        created_at: '2026-03-10T08:01:15Z',
    },
    {
        id: 'msg-4',
        sender: ConversationMessageSenderType.USER,
        message_type: ConversationMessageType.QUESTION,
        content: 'I cannot reset my password.',
        created_at: '2026-03-10T08:02:05Z',
    },
    {
        id: 'msg-5',
        sender: ConversationMessageSenderType.AGENT,
        message_type: ConversationMessageType.AGENT_ANSWER,
        content: 'Agent here. I checked your request and resent the reset link.',
        created_at: '2026-03-10T08:02:55Z',
    },
    {
        id: 'msg-6',
        sender: ConversationMessageSenderType.USER,
        message_type: ConversationMessageType.QUESTION,
        content: 'Thanks. Is there anything else I should do?',
        created_at: '2026-03-10T08:03:25Z',
    },
    {
        id: 'msg-7',
        sender: ConversationMessageSenderType.BOT,
        message_type: ConversationMessageType.BOT_ANSWER,
        content: 'Please check spam folder as well. If needed, we can continue with a human agent.',
        created_at: '2026-03-10T08:03:50Z',
    },
])

const orderedMessages = computed(() =>
    [...messageFeed.value].sort(
        (a, b) => new Date(a.created_at).getTime() - new Date(b.created_at).getTime()
    )
)

const canSubmit = computed(() => draftMessage.value.trim().length > 0)

function updateScreenState(): void {
    isMobile.value = window.innerWidth < MOBILE_BREAKPOINT_PX
}

function openChat(): void {
    isOpen.value = true
}

function closeChat(): void {
    isOpen.value = false
}

function toggleConnectionTarget(): void {
    connectionTarget.value = connectionTarget.value === 'bot' ? 'agent' : 'bot'
}

function submitMessage(): void {
    const content = draftMessage.value.trim()
    if (!content) {
        return
    }

    const now = new Date()
    const userMessage: ChatMessage = {
        id: `msg-${now.getTime()}`,
        sender: ConversationMessageSenderType.USER,
        message_type: ConversationMessageType.QUESTION,
        content,
        created_at: now.toISOString(),
    }

    messageFeed.value.push(userMessage)
    draftMessage.value = ''
}

function senderLabel(sender: ConversationMessageSenderType): string {
    switch (sender) {
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

function messageRowClass(message: ChatMessage): string {
    if (message.message_type === ConversationMessageType.SYSTEM_NOTICE) {
        return 'message-row--center'
    }

    if (message.message_type === ConversationMessageType.QUESTION) {
        return 'message-row--right'
    }

    return 'message-row--left'
}

function messageBubbleClass(message: ChatMessage): string {
    switch (message.message_type) {
        case ConversationMessageType.QUESTION:
            return 'message-bubble--question'
        case ConversationMessageType.BOT_ANSWER:
            return 'message-bubble--bot'
        case ConversationMessageType.AGENT_ANSWER:
            return 'message-bubble--agent'
        case ConversationMessageType.SYSTEM_NOTICE:
            return 'message-bubble--system'
        default:
            return 'message-bubble--bot'
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

watch(isOpen, async (open) => {
    if (!open || !messagesContainer.value) {
        return
    }

    await nextTick()
    messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight
})

onMounted(() => {
    connectionTarget.value = 'bot'
    updateScreenState()
    window.addEventListener('resize', updateScreenState)
})

onBeforeUnmount(() => {
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

.chat-panel__messages {
    background: #f7f8fc;
    overflow-y: auto;
    flex: 1 1 auto;
    padding: 0.95rem;
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
</style>
