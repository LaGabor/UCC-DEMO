<template>
    <section class="admin-chat card shadow-sm border-0 h-100">
        <header class="admin-chat__header d-flex align-items-center">
            <div>
                <h3 class="h6 mb-0">{{ title }}</h3>
                <small class="text-white-50">{{ subtitle }}</small>
            </div>

            <div class="ms-auto d-flex align-items-center gap-2">
                <button
                    v-if="actionLabel"
                    type="button"
                    class="btn btn-sm"
                    :class="actionButtonClass ?? 'btn-warning'"
                    :disabled="actionDisabled"
                    @click="$emit('action')"
                >
                    {{ actionLabel }}
                </button>
                <button
                    type="button"
                    class="btn btn-sm btn-outline-light"
                    :disabled="speechRecognition.isListening.value"
                    aria-label="Close"
                    @click="$emit('close')"
                >
                    <i class="bi bi-x-lg" />
                </button>
            </div>
        </header>

        <div class="admin-chat__messages-wrapper">
            <div ref="messagesContainer" class="admin-chat__messages">
                <div
                    v-for="message in orderedMessages"
                :key="message.id"
                class="message-row"
                :class="messageRowClass(message.message_type)"
            >
                <div class="message-bubble" :class="messageBubbleClass(message.message_type)">
                    <div class="message-meta">
                        <strong>{{ senderLabel(message.sender) }}</strong>
                        <span class="mx-1">-</span>
                        <span>{{ formatDateTime(message.created_at) }}</span>
                    </div>
                    <p class="mb-0">{{ message.content }}</p>
                </div>
            </div>
        </div>
            <div
                v-if="speechRecognition.isListening.value"
                class="admin-chat__messages-overlay"
                aria-hidden="true"
                role="button"
                :aria-label="t('chat.stopRecording')"
                tabindex="0"
                @click.stop="stopSpeechInput"
                @keydown.enter.space.prevent="stopSpeechInput"
            >
                <i class="bi bi-mic-fill admin-chat__listening-icon" />
            </div>
        </div>

        <form class="admin-chat__form" @submit.prevent="emitSend">
            <div class="position-relative admin-chat__input-wrapper">
                <input
                    ref="adminChatInputRef"
                    :value="displayedDraft"
                    type="text"
                    class="form-control admin-chat__input-with-actions"
                    :placeholder="inputPlaceholder"
                    autocomplete="off"
                    :disabled="inputDisabled"
                    @input="onDraftInput"
                />
                <div
                    class="position-absolute top-50 end-0 translate-middle-y admin-chat__input-actions d-flex align-items-center pe-2"
                    @click="adminChatInputRef?.focus()"
                >
                    <button
                        v-if="speechRecognition.isSupported"
                        type="button"
                        class="btn btn-link btn-sm p-1 text-secondary admin-chat__mic-btn"
                        :class="{ 'admin-chat__mic-btn--active': speechRecognition.isListening.value }"
                        :disabled="inputDisabled"
                        :title="t('chat.speechToText')"
                        :aria-label="t('chat.speechToText')"
                        @click.stop.prevent="toggleSpeechInput"
                    >
                        <i class="bi" :class="speechRecognition.isListening.value ? 'bi-mic-mute-fill' : 'bi-mic-fill'" />
                    </button>
                </div>
            </div>
            <button type="submit" class="btn btn-primary" :disabled="!canSend || inputDisabled">
                <i class="bi bi-send-fill" />
            </button>
        </form>
    </section>
</template>

<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useSpeechRecognition } from '../composables/useSpeechRecognition'
import { ConversationMessageSenderType, ConversationMessageType } from '../types/enums'

type AdminChatMessage = {
    id: string
    sender: ConversationMessageSenderType
    message_type: ConversationMessageType
    content: string
    created_at: string
}

const props = defineProps<{
    title: string
    subtitle: string
    inputPlaceholder: string
    messages: AdminChatMessage[]
    inputDisabled?: boolean
    actionLabel?: string
    actionButtonClass?: string
    actionDisabled?: boolean
}>()

const emit = defineEmits<{
    close: []
    send: [content: string]
    action: []
}>()

const { t } = useI18n()
const messagesContainer = ref<HTMLElement | null>(null)
const adminChatInputRef = ref<HTMLInputElement | null>(null)
const draft = ref('')
const interimTranscript = ref('')

const speechRecognition = useSpeechRecognition({
    onFinalTranscript(text) {
        const trimmed = text.trim()
        if (!trimmed) return
        draft.value = draft.value ? `${draft.value} ${trimmed}` : trimmed
        interimTranscript.value = ''
    },
    onInterimTranscript(text) {
        interimTranscript.value = text
    },
})

const displayedDraft = computed(
    () => draft.value + (interimTranscript.value ? ` ${interimTranscript.value}` : '')
)

function onDraftInput(event: Event): void {
    const target = event.target as HTMLInputElement
    draft.value = target.value
    interimTranscript.value = ''
}

function toggleSpeechInput(): void {
    if (props.inputDisabled) return
    speechRecognition.toggle()
}

function stopSpeechInput(): void {
    if (!speechRecognition.isListening.value) return
    speechRecognition.stop()
    flushInterimToDraft()
}

function flushInterimToDraft(): void {
    if (interimTranscript.value) {
        draft.value = (draft.value + (draft.value ? ' ' : '') + interimTranscript.value).trim()
        interimTranscript.value = ''
    }
}

const orderedMessages = computed(() =>
    [...props.messages].sort(
        (messageA, messageB) =>
            new Date(messageA.created_at).getTime() -
            new Date(messageB.created_at).getTime()
    )
)

function scrollMessagesToBottom(): void {
    if (!messagesContainer.value) return
    messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight
}

const canSend = computed(() => displayedDraft.value.trim().length > 0)

function emitSend(): void {
    if (props.inputDisabled) {
        return
    }

    const content = displayedDraft.value.trim()
    if (!content) {
        return
    }

    emit('send', content)
    draft.value = ''
    interimTranscript.value = ''
}

function senderLabel(sender: ConversationMessageSenderType): string {
    if (sender === ConversationMessageSenderType.USER) {
        return 'User'
    }
    if (sender === ConversationMessageSenderType.BOT) {
        return 'Bot'
    }
    if (sender === ConversationMessageSenderType.AGENT) {
        return 'Agent'
    }
    return 'System'
}

function messageRowClass(type: ConversationMessageType): string {
    if (type === ConversationMessageType.SYSTEM_NOTICE) {
        return 'message-row--center'
    }
    if (type === ConversationMessageType.QUESTION) {
        return 'message-row--left'
    }
    return 'message-row--right'
}

function messageBubbleClass(type: ConversationMessageType): string {
    if (type === ConversationMessageType.SYSTEM_NOTICE) {
        return 'message-bubble--system'
    }
    if (type === ConversationMessageType.QUESTION) {
        return 'message-bubble--question'
    }
    if (type === ConversationMessageType.BOT_ANSWER) {
        return 'message-bubble--bot'
    }
    return 'message-bubble--agent'
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

watch(
    () => orderedMessages.value.length,
    async () => {
        await nextTick()
        scrollMessagesToBottom()
    },
    { immediate: true }
)

watch(
    () => props.messages,
    async () => {
        await nextTick()
        requestAnimationFrame(() => scrollMessagesToBottom())
    },
    { deep: true }
)

onMounted(async () => {
    await nextTick()
    requestAnimationFrame(() => scrollMessagesToBottom())
})

watch(
    () => speechRecognition.isListening.value,
    (isListening, wasListening) => {
        if (wasListening && !isListening) flushInterimToDraft()
    }
)

onBeforeUnmount(() => {
    speechRecognition.stop()
})
</script>

<style scoped>
.admin-chat {
    min-height: 100%;
}

.admin-chat__header {
    background: linear-gradient(120deg, #0d6efd 0%, #0b5ed7 100%);
    color: #fff;
    padding: 0.8rem 1rem;
}

.admin-chat__messages-wrapper {
    position: relative;
    flex: 1 1 auto;
    min-height: 0;
    display: flex;
    flex-direction: column;
}

.admin-chat__messages {
    flex: 1 1 auto;
    min-height: 0;
    overflow-y: auto;
    padding: 1rem;
    background: #f7f8fc;
}

.admin-chat__messages-overlay {
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

.admin-chat__listening-icon {
    font-size: 3.5rem;
    color: #0d6efd;
    opacity: 1;
}

.message-row {
    display: flex;
    margin-bottom: 0.55rem;
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
    max-width: 80%;
    border-radius: 0.75rem;
    padding: 0.55rem 0.75rem;
}

.message-bubble--question {
    background: #e9edf3;
    color: #0f172a;
}

.message-bubble--bot {
    background: #dbe7ff;
    color: #0a3ea8;
}

.message-bubble--agent {
    background: #dff6e8;
    color: #0f5132;
}

.message-bubble--system {
    background: #fff4cf;
    color: #6a4a00;
    max-width: 92%;
}

.message-meta {
    font-size: 0.72rem;
    margin-bottom: 0.25rem;
    opacity: 0.8;
}

.admin-chat__form {
    padding: 0.8rem;
    border-top: 1px solid #e5e7eb;
    background: #fff;
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 0.55rem;
}

.admin-chat__input-wrapper {
    position: relative;
}

.admin-chat__input-with-actions {
    padding-right: 2.75rem;
}

.admin-chat__input-actions {
    z-index: 2;
    cursor: text;
}

.admin-chat__input-actions .admin-chat__mic-btn {
    cursor: pointer;
}

.admin-chat__mic-btn {
    min-width: 2.25rem;
    min-height: 2.25rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.admin-chat__mic-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.admin-chat__mic-btn--active {
    color: var(--bs-primary) !important;
}
</style>
