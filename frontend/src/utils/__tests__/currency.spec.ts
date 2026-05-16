import { describe, expect, it } from 'vitest'
import { formatBRL, formatCurrencyInput, parseCurrencyInput } from '../currency'

describe('formatBRL', () => {
    it('formata numero com prefixo R$ e separadores ptBR', () => {
        const result = formatBRL(199.9).replace(/\s/g, ' ')
        expect(result).toMatch(/R\$\s?199,90/)
    })

    it('formata string numerica', () => {
        const result = formatBRL('0').replace(/\s/g, ' ')
        expect(result).toMatch(/R\$\s?0,00/)
    })

    it('zera quando valor invalido', () => {
        const result = formatBRL('abc').replace(/\s/g, ' ')
        expect(result).toMatch(/R\$\s?0,00/)
    })
})

describe('parseCurrencyInput', () => {
    it('converte digitos para decimal com centavos', () => {
        expect(parseCurrencyInput('19990')).toBe('199.90')
        expect(parseCurrencyInput('1')).toBe('0.01')
        expect(parseCurrencyInput('100')).toBe('1.00')
    })

    it('ignora separadores e mantem so digitos', () => {
        expect(parseCurrencyInput('R$ 1.234,56')).toBe('1234.56')
    })

    it('vazio retorna zero formal', () => {
        expect(parseCurrencyInput('')).toBe('0.00')
    })
})

describe('formatCurrencyInput', () => {
    it('formata decimal em pt-BR com duas casas', () => {
        expect(formatCurrencyInput('199.90')).toBe('199,90')
        expect(formatCurrencyInput('1234.5')).toBe('1.234,50')
    })

    it('vazio retorna vazio', () => {
        expect(formatCurrencyInput('')).toBe('')
    })
})
