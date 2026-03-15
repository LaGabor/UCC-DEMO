import { apiClient, getXsrfTokenFromCookie } from './client'
import type {
    AgentMonitorConversationHistoryPayload,
    AgentMonitorConversationPayload,
} from '../types/communication'

type SuccessResponse<T> = {
    success: boolean
    message?: string
    data: T
}

export async function getAgentMonitorConversationsRequest(): Promise<AgentMonitorConversationPayload[]> {
    const response = await apiClient.get<SuccessResponse<AgentMonitorConversationPayload[]>>(
        '/api/agent-monitor/conversations'
    )

    return response.data.data
}

export async function getAgentMonitorViewUserChatHistoryRequest(
    conversationId: number
): Promise<AgentMonitorConversationHistoryPayload> {
    const response = await apiClient.get<SuccessResponse<AgentMonitorConversationHistoryPayload>>(
        `/api/agent-monitor/conversations/${conversationId}/history`
    )

    return response.data.data
}

export async function postAgentMonitorAnswerUserChatHistoryRequest(
    conversationId: number
): Promise<AgentMonitorConversationHistoryPayload> {
    const response = await apiClient.patch<SuccessResponse<AgentMonitorConversationHistoryPayload>>(
        `/api/agent-monitor/conversations/${conversationId}/answer`
    )

    return response.data.data
}

export async function postAgentMonitorCloseAssignedRequest(conversationId: number): Promise<void> {
    await apiClient.patch(`/api/agent-monitor/conversations/${conversationId}/close-assigned`)
}

export async function postAgentMonitorCloseWaitingHumanRequest(
    conversationId: number
): Promise<void> {
    await apiClient.patch(`/api/agent-monitor/conversations/${conversationId}/close-waiting-human`)
}

export async function postAgentMonitorSendAgentMessageRequest(
    conversationId: number,
    messageText: string
): Promise<void> {
    await apiClient.post(`/api/agent-monitor/conversations/${conversationId}/messages`, {
        message_text: messageText,
    })
}

const CLOSE_AGENT_COMMUNICATION_STATUS_PATH = '/api/agent-monitor/close-agent-communication-status'

export function closeAgentCommunicationStatusBeacon(): void {
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
        .fetch(CLOSE_AGENT_COMMUNICATION_STATUS_PATH, {
            method: 'PATCH',
            credentials: 'include',
            keepalive: true,
            headers,
        })
        .catch(() => {})
}
