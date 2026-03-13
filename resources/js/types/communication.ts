import type { ConversationMessageSenderType, ConversationMessageType, ConversationStatus } from './enums'

export type ConversationMessageItem = {
    id: number
    sender_type: ConversationMessageSenderType
    message_type: ConversationMessageType
    message_text: string | null
    sender_user_id: number | null
    created_at: string
}

export type UserConversationPayload = {
    conversation_id: number | null
    status: ConversationStatus | null
    messages: ConversationMessageItem[]
}

export type UserMessageAcceptedPayload = {
    conversation_id: number
    status: ConversationStatus
    message: string
}

export type ConversationMessageBroadcastPayload = {
    conversation_id: number
    status: ConversationStatus
    message: ConversationMessageItem
}

export type AgentMonitorConversationPayload = {
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

export type AgentMonitorConversationBroadcastPayload = AgentMonitorConversationPayload
