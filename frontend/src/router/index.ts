import { createRouter, createWebHistory, type RouteRecordRaw } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

declare module 'vue-router' {
    interface RouteMeta {
        title?: string
        layout?: 'app' | 'blank'
        public?: boolean
    }
}

const routes: RouteRecordRaw[] = [
    {
        path: '/',
        name: 'home',
        component: () => import('@/views/HomeView.vue'),
        meta: { title: 'Visao geral' },
    },
    {
        path: '/clientes',
        name: 'clients-list',
        component: () => import('@/views/clients/ClientsListView.vue'),
        meta: { title: 'Clientes' },
    },
    {
        path: '/clientes/novo',
        name: 'clients-create',
        component: () => import('@/views/clients/ClientFormView.vue'),
        meta: { title: 'Novo cliente' },
    },
    {
        path: '/clientes/:id(\\d+)/editar',
        name: 'clients-edit',
        component: () => import('@/views/clients/ClientFormView.vue'),
        meta: { title: 'Editar cliente' },
    },
    {
        path: '/login',
        name: 'login',
        component: () => import('@/views/LoginView.vue'),
        meta: { title: 'Entrar', layout: 'blank', public: true },
    },
    {
        path: '/erro',
        name: 'server-error',
        component: () => import('@/views/ServerErrorView.vue'),
        meta: { title: 'Erro inesperado', layout: 'blank', public: true },
    },
    {
        path: '/:pathMatch(.*)*',
        name: 'not-found',
        component: () => import('@/views/NotFoundView.vue'),
        meta: { title: 'Pagina nao encontrada', public: true },
    },
]

export const router = createRouter({
    history: createWebHistory(),
    routes,
    scrollBehavior() {
        return { top: 0 }
    },
})

router.beforeEach((to) => {
    const auth = useAuthStore()
    const isPublic = to.meta.public === true

    if (!isPublic && !auth.isAuthenticated) {
        return {
            name: 'login',
            query: to.fullPath !== '/' ? { redirect: to.fullPath } : undefined,
        }
    }

    if (auth.isAuthenticated && to.name === 'login') {
        const redirect = typeof to.query.redirect === 'string' ? to.query.redirect : '/'
        return redirect.startsWith('/') ? redirect : { name: 'home' }
    }

    return true
})
