type Translator = (key: string) => string

type ErrorResponsePayload = {
    code?: string
    errors?: Record<string, string[]>
}

export function getApiErrorMessage(
    t: Translator,
    error: unknown,
    fallbackKey: string,
    codeToKey: Record<string, string> = {}
): string {
    const payload = (error as { response?: { data?: ErrorResponsePayload } })?.response?.data
    const code = payload?.code

    if (code && codeToKey[code]) {
        return t(codeToKey[code])
    }

    const errors = payload?.errors
    if (errors) {
        for (const messages of Object.values(errors)) {
            if (messages.length > 0 && messages[0]) {
                return messages[0]
            }
        }
    }

    return t(fallbackKey)
}
