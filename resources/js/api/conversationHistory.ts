import { apiClient } from './client'
import type {
    AgentMonitorConversationHistoryPayload,
    ConversationHistoryListEntry,
} from '../types/communication'

type SuccessResponse<T> = {
    success: boolean
    message?: string
    data: T
}

export async function getConversationHistoryListRequest(): Promise<ConversationHistoryListEntry[]> {
    const response = await apiClient.get<SuccessResponse<ConversationHistoryListEntry[]>>(
        '/api/conversation-history'
    )
    return response.data.data
}

export async function getConversationHistoryMessagesRequest(
    conversationId: number
): Promise<AgentMonitorConversationHistoryPayload> {
    const response = await apiClient.get<SuccessResponse<AgentMonitorConversationHistoryPayload>>(
        `/api/conversation-history/${conversationId}/messages`
    )
    return response.data.data
}
