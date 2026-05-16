import type { DocumentType } from '@/types/client'

export function onlyDigits(value: string): string {
    return value.replace(/\D+/g, '')
}

export function inferDocumentType(value: string): DocumentType {
    return onlyDigits(value).length > 11 ? 'cnpj' : 'cpf'
}

export function formatDocumento(value: string, type?: DocumentType): string {
    const digits = onlyDigits(value)
    const resolved = type ?? inferDocumentType(digits)

    if (resolved === 'cpf') {
        return digits
            .slice(0, 11)
            .replace(/^(\d{3})(\d)/, '$1.$2')
            .replace(/^(\d{3})\.(\d{3})(\d)/, '$1.$2.$3')
            .replace(/\.(\d{3})(\d)/, '.$1-$2')
    }

    return digits
        .slice(0, 14)
        .replace(/^(\d{2})(\d)/, '$1.$2')
        .replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3')
        .replace(/\.(\d{3})(\d)/, '.$1/$2')
        .replace(/(\d{4})(\d)/, '$1-$2')
}

export function maxLengthFor(type: DocumentType): number {
    return type === 'cpf' ? 14 : 18
}
