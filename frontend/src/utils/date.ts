const PLACEHOLDER = '—'

// Datas "puras" (YYYY-MM-DD) sao formatadas sem passar pelo Date para
// evitar deslocamento de fuso — new Date('2025-08-09') vira UTC midnight
// e em America/Sao_Paulo (UTC-3) regrediria para 2025-08-08.
const ISO_DATE = /^(\d{4})-(\d{2})-(\d{2})/

// Forca America/Sao_Paulo para que datetimes da API (UTC ou outro fuso)
// sejam sempre exibidos na timezone do dominio do produto (ERP BR),
// independente do fuso do navegador ou do container que serve o build.
const dateTimeFormatter = new Intl.DateTimeFormat('pt-BR', {
    dateStyle: 'short',
    timeStyle: 'short',
    timeZone: 'America/Sao_Paulo',
})

export function formatDate(value: string | null | undefined): string {
    if (!value) return PLACEHOLDER
    const match = ISO_DATE.exec(value)
    if (!match) return value
    const [, ano, mes, dia] = match
    return `${dia}/${mes}/${ano}`
}

export function formatDateTime(value: string | null | undefined): string {
    if (!value) return PLACEHOLDER
    const date = new Date(value)
    if (Number.isNaN(date.getTime())) {
        return value
    }
    return dateTimeFormatter.format(date).replace(/\s/g, ' ')
}
