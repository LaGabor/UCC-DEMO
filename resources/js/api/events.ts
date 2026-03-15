import { apiClient } from './client'
import type {
    EventItem,
    EventListQuery,
    EventListResponse,
    EventPayload,
    EventUpdateDescriptionPayload,
} from '../types/events'

type SuccessResponse<T> = {
    success: boolean
    message: string
    data: T
}

export async function fetchEvents(query: EventListQuery): Promise<EventListResponse> {
    const response = await apiClient.get<EventListResponse>('/api/events', {
        params: query,
    })

    return response.data
}

export async function createEvent(payload: EventPayload): Promise<EventItem> {
    const response = await apiClient.post<SuccessResponse<EventItem>>('/api/events', payload)
    return response.data.data
}

export async function getEvent(eventId: number): Promise<EventItem> {
    const response = await apiClient.get<SuccessResponse<EventItem>>(`/api/events/${eventId}`)
    return response.data.data
}

export async function updateEvent(eventId: number, payload: EventUpdateDescriptionPayload): Promise<EventItem> {
    const response = await apiClient.patch<SuccessResponse<EventItem>>(`/api/events/${eventId}`, payload)
    return response.data.data
}

export async function deleteEvent(eventId: number): Promise<void> {
    await apiClient.delete(`/api/events/${eventId}`)
}

