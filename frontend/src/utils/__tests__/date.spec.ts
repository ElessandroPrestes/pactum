import { describe, expect, it } from 'vitest'
import { formatDate, formatDateTime } from '../date'

describe('formatDate', () => {
    it('formata data ISO YYYY-MM-DD no padrao pt-BR sem deslocar fuso', () => {
        expect(formatDate('2025-08-09')).toBe('09/08/2025')
    })

    it('aceita datetime ISO completo e devolve apenas a data', () => {
        expect(formatDate('2026-01-15T03:00:00-03:00')).toBe('15/01/2026')
    })

    it('retorna placeholder quando valor e null ou vazio', () => {
        expect(formatDate(null)).toBe('—')
        expect(formatDate(undefined)).toBe('—')
        expect(formatDate('')).toBe('—')
    })

    it('preserva valor cru quando nao casa com padrao ISO', () => {
        expect(formatDate('amanha')).toBe('amanha')
    })
})

describe('formatDateTime', () => {
    it('formata datetime no padrao pt-BR (data e hora)', () => {
        const result = formatDateTime('2025-08-09T14:30:00-03:00').replace(/\s/g, ' ')
        expect(result).toMatch(/09\/08\/2025/)
        expect(result).toMatch(/14:30/)
    })

    it('retorna placeholder em null ou vazio', () => {
        expect(formatDateTime(null)).toBe('—')
        expect(formatDateTime('')).toBe('—')
    })

    it('preserva valor cru quando nao e parseavel', () => {
        expect(formatDateTime('not-a-date')).toBe('not-a-date')
    })
})
