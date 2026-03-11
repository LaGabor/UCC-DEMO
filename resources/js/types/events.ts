export type EventItem = {
    id: number
    title: string
    description: string | null
    occurs_at: string
    created_at: string
    updated_at: string
}

export type EventListMeta = {
    current_page: number
    last_page: number
    per_page: number
    total: number
}

export type EventListResponse = {
    data: EventItem[]
    meta: EventListMeta
}

export type EventFilters = {
    q: string
    from: string
    to: string
}

export type EventListQuery = {
    page: number
    per_page: number
    sort_by: 'title' | 'description' | 'occurs_at' | 'created_at'
    sort_dir: 'asc' | 'desc'
    q?: string
    from?: string
    to?: string
}

export type EventPayload = {
    title: string
    occurs_at: string
    description?: string | null
}

export type EventUpdateDescriptionPayload = {
    description?: string | null
}

