import { apiClient, getXsrfTokenFromCookie } from './client'
import type {
    CloseUserCommunicationPayload,
    UserConversationPayload,
    UserMessageAcceptedPayload,
} from '../types/communication'
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

const CLOSE_USER_COMMUNICATION_PATH = '/api/communication/close-user-communication'

export async function closeUserCommunicationRequest(payload: {
    conversation_id: number | null
}): Promise<CloseUserCommunicationPayload> {
    const response = await apiClient.patch<SuccessResponse<CloseUserCommunicationPayload>>(
        CLOSE_USER_COMMUNICATION_PATH,
        payload
    )

    return response.data.data
}

export function closeUserCommunicationBeacon(payload: { conversation_id: number | null }): void {
    const token = getXsrfTokenFromCookie()
    const headers: Record<string, string> = {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    }
    if (token) {
        headers['X-XSRF-TOKEN'] = token
    }
    window
        .fetch(CLOSE_USER_COMMUNICATION_PATH, {
            method: 'PATCH',
            credentials: 'include',
            keepalive: true,
            headers,
            body: JSON.stringify(payload),
        })
        .catch(() => {})
}
