import { describe, expect, it } from 'vitest'
import { formatDocumento, inferDocumentType, onlyDigits } from '../documento'

describe('onlyDigits', () => {
    it('mantem apenas digitos', () => {
        expect(onlyDigits('123.456.789-00')).toBe('12345678900')
        expect(onlyDigits('abc')).toBe('')
    })
})

describe('inferDocumentType', () => {
    it('detecta cnpj acima de 11 digitos', () => {
        expect(inferDocumentType('12345678901234')).toBe('cnpj')
    })

    it('detecta cpf ate 11 digitos', () => {
        expect(inferDocumentType('12345678900')).toBe('cpf')
        expect(inferDocumentType('123')).toBe('cpf')
    })
})

describe('formatDocumento', () => {
    it('formata cpf completo', () => {
        expect(formatDocumento('12345678900', 'cpf')).toBe('123.456.789-00')
    })

    it('formata cpf parcial sem quebrar', () => {
        expect(formatDocumento('123', 'cpf')).toBe('123')
        expect(formatDocumento('1234', 'cpf')).toBe('123.4')
    })

    it('formata cnpj completo', () => {
        expect(formatDocumento('12345678000190', 'cnpj')).toBe('12.345.678/0001-90')
    })

    it('infere tipo quando omitido', () => {
        expect(formatDocumento('12345678000190')).toBe('12.345.678/0001-90')
        expect(formatDocumento('12345678900')).toBe('123.456.789-00')
    })

    it('trunca digitos excedentes', () => {
        expect(formatDocumento('123456789001234567', 'cpf')).toBe('123.456.789-00')
    })
})
