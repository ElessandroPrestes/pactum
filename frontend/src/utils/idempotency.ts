export function generateIdempotencyKey(): string {
    if (typeof crypto !== 'undefined' && 'randomUUID' in crypto) {
        return crypto.randomUUID()
    }
    return `idemp-${Date.now()}-${Math.random().toString(36).slice(2, 10)}`
}
