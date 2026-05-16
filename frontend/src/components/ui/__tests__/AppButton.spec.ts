import { describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'
import AppButton from '../AppButton.vue'

describe('AppButton', () => {
    it('renderiza slot padrao e emite click', async () => {
        const wrapper = mount(AppButton, {
            slots: { default: 'Salvar' },
        })

        expect(wrapper.text()).toContain('Salvar')
        await wrapper.trigger('click')
        expect(wrapper.emitted('click')).toHaveLength(1)
    })

    it('desabilita interacao quando loading e expoe aria-busy', () => {
        const wrapper = mount(AppButton, {
            props: { loading: true },
            slots: { default: 'Salvando' },
        })

        const button = wrapper.find('button')
        expect(button.attributes('disabled')).toBeDefined()
        expect(button.attributes('aria-busy')).toBe('true')
    })

    it('aplica variante danger', () => {
        const wrapper = mount(AppButton, {
            props: { variant: 'danger' },
            slots: { default: 'Excluir' },
        })
        expect(wrapper.find('button').classes().join(' ')).toContain('bg-danger-600')
    })
})
