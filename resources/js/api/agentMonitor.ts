import { apiClient } from './client'
import type { AgentMonitorConversationPayload } from '../types/communication'

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
