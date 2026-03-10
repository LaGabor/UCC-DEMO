export enum UserStatus {
    ACTIVE = 'active',
    DISABLED = 'disabled',
    PENDING = 'pending',
}

export enum Language {
    HU = 'hu',
    EN = 'en',
}

export enum UserRole {
    USER = 'user',
    HELPDESK_AGENT = 'helpdesk_agent',
    ADMIN = 'admin',
}

export enum PasswordResetTokenType {
    INVITATION = 'invitation',
    PASSWORD_RESET = 'password_reset',
}

export enum ConversationStatus {
    OPEN = 'open',
    WAITING_HUMAN = 'waiting_human',
    ASSIGNED = 'assigned',
    CLOSED = 'closed',
}

export enum ConversationMessageSenderType {
    USER = 'user',
    BOT = 'bot',
    AGENT = 'agent',
    SYSTEM = 'system',
}

export enum ConversationMessageType {
    QUESTION = 'question',
    BOT_ANSWER = 'bot_answer',
    AGENT_ANSWER = 'agent_answer',
    SYSTEM_NOTICE = 'system_notice',
}
