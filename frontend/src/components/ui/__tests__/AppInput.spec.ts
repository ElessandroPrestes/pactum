import { describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'
import AppInput from '../AppInput.vue'

describe('AppInput', () => {
    it('emite update:modelValue ao digitar', async () => {
        const wrapper = mount(AppInput, {
            props: { modelValue: '', label: 'Nome' },
        })

        const input = wrapper.find('input')
        await input.setValue('Pactum')

        expect(wrapper.emitted('update:modelValue')?.[0]).toEqual(['Pactum'])
    })

    it('renderiza mensagem de erro com role alert e aria-invalid', () => {
        const wrapper = mount(AppInput, {
            props: { modelValue: '', label: 'Email', error: 'email obrigatorio' },
        })

        expect(wrapper.find('input').attributes('aria-invalid')).toBe('true')
        const alert = wrapper.find('[role="alert"]')
        expect(alert.exists()).toBe(true)
        expect(alert.text()).toContain('email obrigatorio')
    })

    it('associa label ao input via id gerado', () => {
        const wrapper = mount(AppInput, {
            props: { modelValue: '', label: 'Documento' },
        })

        const inputId = wrapper.find('input').attributes('id')
        const labelFor = wrapper.find('label').attributes('for')
        expect(inputId).toBeTruthy()
        expect(labelFor).toBe(inputId)
    })
})
