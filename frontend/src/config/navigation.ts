export interface NavItem {
    label: string
    to: string
    icon: 'home' | 'users' | 'briefcase' | 'file-text'
}

export const primaryNavigation: NavItem[] = [
    { label: 'Visao geral', to: '/', icon: 'home' },
    { label: 'Clientes', to: '/clientes', icon: 'users' },
    { label: 'Servicos', to: '/servicos', icon: 'briefcase' },
    { label: 'Contratos', to: '/contratos', icon: 'file-text' },
]
