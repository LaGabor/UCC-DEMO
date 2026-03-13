import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

import { apiClient } from './api/client'

declare global {
    interface Window {
        Pusher: typeof Pusher
        Echo: Echo
    }
}

window.Pusher = Pusher

/**
 * Custom authorizer so private channel auth uses our apiClient (cookies + XSRF).
 * Without this, the default auth request may not send credentials and /broadcasting/auth returns 401.
 */
function reverbAuthorizer(
    channel: { name: string },
    _options: Record<string, unknown>
): { authorize: (socketId: string, callback: (error: boolean, data?: unknown) => void) => void } {
    return {
        authorize: (socketId: string, callback: (error: boolean, data?: unknown) => void) => {
            apiClient
                .post('/broadcasting/auth', {
                    socket_id: socketId,
                    channel_name: channel.name,
                })
                .then((response) => callback(false, response.data))
                .catch((error) => callback(true, error))
        },
    }
}

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: Number(import.meta.env.VITE_REVERB_PORT) || 80,
    wssPort: Number(import.meta.env.VITE_REVERB_PORT) || 443,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
    authEndpoint: '/broadcasting/auth',
    authorizer: reverbAuthorizer,
})
