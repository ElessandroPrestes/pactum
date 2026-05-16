import { expect, test } from '@playwright/test'

const DEV_EMAIL = process.env.E2E_EMAIL ?? 'dev@pactum.local'
const DEV_PASSWORD = process.env.E2E_PASSWORD ?? 'change-me-in-dev'

async function login(page: import('@playwright/test').Page): Promise<void> {
    await page.goto('/login')
    await page.getByLabel('Email').fill(DEV_EMAIL)
    await page.getByLabel('Senha').fill(DEV_PASSWORD)
    await page.getByRole('button', { name: /entrar/i }).click()
    await expect(page).not.toHaveURL(/\/login/)
}

test.describe('Fluxo critico de contrato', () => {
    test('login -> criar contrato -> abrir detalhe -> cancelar', async ({ page }) => {
        await login(page)

        await page.goto('/contratos/novo')
        await expect(
            page.getByRole('heading', { level: 2, name: /novo contrato/i }),
        ).toBeVisible()

        const clienteSelect = page.getByLabel(/^Cliente\b/)
        await expect(clienteSelect).toBeEnabled()
        const clienteOptions = await clienteSelect
            .locator('option:not([disabled]):not([value=""])')
            .all()
        expect(clienteOptions.length).toBeGreaterThan(0)
        const primeiroCliente = await clienteOptions[0]!.getAttribute('value')
        await clienteSelect.selectOption(primeiroCliente!)

        const servicoSelect = page.getByLabel(/^Servico\b/).first()
        const servicoOptions = await servicoSelect
            .locator('option:not([disabled]):not([value=""])')
            .all()
        expect(servicoOptions.length).toBeGreaterThan(0)
        const primeiroServico = await servicoOptions[0]!.getAttribute('value')
        await servicoSelect.selectOption(primeiroServico!)

        await page.getByRole('button', { name: /cadastrar contrato/i }).click()

        await expect(page).toHaveURL(/\/contratos\/\d+$/)
        await expect(page.getByRole('heading', { level: 2, name: /contrato #/i })).toBeVisible()
        await expect(page.getByText('ativo', { exact: true }).first()).toBeVisible()

        await page.getByRole('button', { name: /^cancelar contrato$/i }).click()

        const confirmar = page
            .getByRole('dialog')
            .getByRole('button', { name: /^cancelar contrato$/i })
        await expect(confirmar).toBeVisible()
        await confirmar.click()

        await expect(page.getByText('cancelado', { exact: true }).first()).toBeVisible({
            timeout: 10_000,
        })
        await expect(
            page.getByText(/este contrato foi cancelado e nao aceita mais alteracoes/i),
        ).toBeVisible()
    })
})
