<template>
    <section class="admin-chat card shadow-sm border-0 h-100">
        <header class="admin-chat__header d-flex align-items-center">
            <div>
                <h3 class="h6 mb-0">{{ title }}</h3>
                <small class="text-white-50">{{ subtitle }}</small>
            </div>

            <button type="button" class="btn btn-sm btn-outline-light ms-auto" @click="$emit('close')">
                <i class="bi bi-x-lg" />
            </button>
        </header>

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

        <form class="admin-chat__form" @submit.prevent="emitSend">
            <input
                v-model="draft"
                type="text"
                class="form-control"
                :placeholder="inputPlaceholder"
                autocomplete="off"
                :disabled="inputDisabled"
            />
            <button type="submit" class="btn btn-primary" :disabled="!canSend || inputDisabled">
                <i class="bi bi-send-fill" />
            </button>
        </form>
    </section>
</template>

<script setup lang="ts">
import { computed, nextTick, ref, watch } from 'vue'
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
}>()

const emit = defineEmits<{
    close: []
    send: [content: string]
}>()

const messagesContainer = ref<HTMLElement | null>(null)
const draft = ref('')

const orderedMessages = computed(() =>
    [...props.messages].sort(
        (a, b) => new Date(a.created_at).getTime() - new Date(b.created_at).getTime()
    )
)

const canSend = computed(() => draft.value.trim().length > 0)

function emitSend(): void {
    if (props.inputDisabled) {
        return
    }

    const content = draft.value.trim()
    if (!content) {
        return
    }

    emit('send', content)
    draft.value = ''
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
        if (!messagesContainer.value) {
            return
        }

        await nextTick()
        messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight
    },
    { immediate: true }
)
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

.admin-chat__messages {
    flex: 1 1 auto;
    min-height: 0;
    overflow-y: auto;
    padding: 1rem;
    background: #f7f8fc;
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
</style>
