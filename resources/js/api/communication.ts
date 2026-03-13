import { apiClient } from './client'
import type { UserConversationPayload, UserMessageAcceptedPayload } from '../types/communication'
import type { ConversationStatus } from '../types/enums'

type SuccessResponse<T> = {
    success: boolean
    message: string
    data: T
}

export async function getUserConversationRequest(): Promise<UserConversationPayload> {
    const response = await apiClient.get<SuccessResponse<UserConversationPayload>>(
        '/api/communication/user-conversation'
    )

    return response.data.data
}

export async function sendUserMessageRequest(payload: {
    conversation_id: number | null
    conversation_status: ConversationStatus
    message_text: string
}): Promise<UserMessageAcceptedPayload> {
    const response = await apiClient.post<SuccessResponse<UserMessageAcceptedPayload>>(
        '/api/communication/user-message',
        payload
    )

    return response.data.data
}

export async function callAgentRequest(payload: {
    conversation_id: number | null
}): Promise<UserConversationPayload> {
    const response = await apiClient.patch<SuccessResponse<UserConversationPayload>>(
        '/api/communication/call-agent',
        payload
    )

    return response.data.data
}

export async function cancelCallRequest(payload: {
    conversation_id: number | null
}): Promise<UserConversationPayload> {
    const response = await apiClient.patch<SuccessResponse<UserConversationPayload>>(
        '/api/communication/cancel-call',
        payload
    )

    return response.data.data
}
