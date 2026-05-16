const formatter = new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'BRL',
})

export function formatBRL(value: string | number): string {
    const numeric = typeof value === 'number' ? value : Number(value)
    if (!Number.isFinite(numeric)) {
        return formatter.format(0)
    }
    return formatter.format(numeric)
}

export function parseCurrencyInput(raw: string): string {
    const digits = raw.replace(/\D+/g, '')
    if (digits.length === 0) {
        return '0.00'
    }
    const padded = digits.padStart(3, '0')
    const inteiro = padded.slice(0, -2)
    const centavos = padded.slice(-2)
    return `${Number(inteiro)}.${centavos}`
}

export function formatCurrencyInput(value: string): string {
    if (!value) {
        return ''
    }
    const numeric = Number(value)
    if (!Number.isFinite(numeric)) {
        return ''
    }
    return numeric.toLocaleString('pt-BR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    })
}
