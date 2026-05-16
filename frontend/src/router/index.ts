import { createRouter, createWebHistory, type RouteRecordRaw } from 'vue-router'

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
