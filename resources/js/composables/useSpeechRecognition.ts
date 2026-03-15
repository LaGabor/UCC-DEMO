import { ref } from 'vue'

declare global {
    interface Window {
        SpeechRecognition?: new () => SpeechRecognitionInstance
        webkitSpeechRecognition?: new () => SpeechRecognitionInstance
    }
}

interface SpeechRecognitionInstance extends EventTarget {
    continuous: boolean
    interimResults: boolean
    lang: string
    start(): void
    stop(): void
    onresult: ((event: SpeechRecognitionResultEvent) => void) | null
    onerror: ((event: SpeechRecognitionErrorEvent) => void) | null
    onend: (() => void) | null
}

interface SpeechRecognitionResultEvent extends Event {
    results: SpeechRecognitionResultList
}

interface SpeechRecognitionResultList {
    length: number
    item(index: number): SpeechRecognitionResult
    [index: number]: SpeechRecognitionResult
}

interface SpeechRecognitionResult {
    length: number
    item(index: number): SpeechRecognitionAlternative
    [index: number]: SpeechRecognitionAlternative
    isFinal: boolean
}

interface SpeechRecognitionAlternative {
    transcript: string
    confidence: number
}

interface SpeechRecognitionErrorEvent extends Event {
    error: string
    message?: string
}

const DEFAULT_LOCALE = 'hu-HU'
const LOCALE_MAP: Record<string, string> = { hu: 'hu-HU', en: 'en-US', de: 'de-DE' }
const MIN_LISTENING_MS = 800
const MAX_RESTARTS = 3
const RESTART_DELAY_MS = 250
const BLOCKING_ERRORS = ['not-allowed', 'no-speech', 'audio-capture'] as const

function normalizeSpeechLang(optionsLang?: string): string {
    const raw =
        (optionsLang ??
            (typeof document !== 'undefined' ? document.documentElement.getAttribute('lang') ?? '' : '')) ||
        DEFAULT_LOCALE
    const code = raw.trim().toLowerCase()
    if (code.length >= 5 && code.includes('-')) return raw
    return LOCALE_MAP[code] ?? `${code}-${code.toUpperCase()}`
}

export function useSpeechRecognition(options: {
    onFinalTranscript: (text: string) => void
    onInterimTranscript?: (text: string) => void
    lang?: string
}) {
    const isListening = ref(false)
    const isSupported =
        typeof window !== 'undefined' &&
        !!(window.SpeechRecognition || window.webkitSpeechRecognition)

    let recognition: SpeechRecognitionInstance | null = null
    let userRequestedStop = false
    let recognitionBlocked = false
    let lastStartTime = 0
    let restartCount = 0

    function createRecognition(): SpeechRecognitionInstance | null {
        if (typeof window === 'undefined') return null
        const Ctor = window.SpeechRecognition || window.webkitSpeechRecognition
        if (!Ctor) return null
        const r = new Ctor() as SpeechRecognitionInstance
        r.continuous = false
        r.interimResults = true
        r.lang = normalizeSpeechLang(options.lang)
        return r
    }

    function requestMicrophoneAccess(): Promise<void> {
        if (typeof navigator === 'undefined' || !navigator.mediaDevices?.getUserMedia) {
            return Promise.resolve()
        }
        return navigator.mediaDevices
            .getUserMedia({ audio: true })
            .then((stream) => {
                stream.getTracks().forEach((track) => track.stop())
            })
    }

    function getTranscript(result: SpeechRecognitionResult): string {
        return Array.from(result)
            .map((alt) => alt.transcript)
            .join(' ')
            .trim()
    }

    function handleResult(event: SpeechRecognitionResultEvent): void {
        const results = event.results
        const last = results[results.length - 1]
        if (!last) return
        const transcript = getTranscript(last)
        if (last.isFinal) {
            if (transcript) options.onFinalTranscript(transcript)
            options.onInterimTranscript?.('')
        } else {
            options.onInterimTranscript?.(transcript)
        }
    }

    function handleError(event: SpeechRecognitionErrorEvent): void {
        if (event.error !== 'aborted') {
            if (BLOCKING_ERRORS.includes(event.error as (typeof BLOCKING_ERRORS)[number])) {
                recognitionBlocked = true
            }
            isListening.value = false
            recognition = null
        }
    }

    function handleEnd(): void {
        const elapsed = Date.now() - lastStartTime

        if (userRequestedStop) {
            userRequestedStop = false
            return
        }
        if (recognitionBlocked) {
            recognitionBlocked = false
            restartCount = 0
            isListening.value = false
            recognition = null
            return
        }
        if (elapsed < MIN_LISTENING_MS && restartCount < MAX_RESTARTS) {
            restartCount += 1
            recognition = null
            setTimeout(() => startSession(), RESTART_DELAY_MS)
        } else {
            restartCount = 0
            isListening.value = false
            recognition = null
        }
    }

    function startSession(): void {
        if (userRequestedStop) return
        const rec = createRecognition()
        if (!rec) {
            isListening.value = false
            return
        }
        recognition = rec
        lastStartTime = Date.now()
        rec.onresult = (e: SpeechRecognitionResultEvent) => handleResult(e)
        rec.onerror = (e: SpeechRecognitionErrorEvent) => handleError(e)
        rec.onend = () => handleEnd()
        try {
            rec.start()
            isListening.value = true
        } catch {
            isListening.value = false
            recognition = null
        }
    }

    function start(): void {
        if (!isSupported || isListening.value) return
        userRequestedStop = false
        recognitionBlocked = false
        restartCount = 0
        requestMicrophoneAccess()
            .then(() => {
                if (!userRequestedStop) startSession()
            })
            .catch(() => {
                isListening.value = false
            })
    }

    function stop(): void {
        if (!recognition || !isListening.value) return
        userRequestedStop = true
        try {
            recognition.stop()
        } catch {
        }
        recognition = null
        isListening.value = false
        restartCount = 0
        recognitionBlocked = false
    }

    function toggle(): void {
        if (isListening.value) stop()
        else start()
    }

    return {
        isSupported,
        isListening,
        start,
        stop,
        toggle,
    }
}
