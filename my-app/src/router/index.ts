// File: `my-app/src/router/index.ts`
import { createRouter, createWebHistory } from 'vue-router'
import type { RouteRecordRaw } from 'vue-router'
import Login from '../views/Login.vue'
import Dashboard from '../views/Dashboard.vue'
import EnterPassword from '../views/EnterPassword.vue'

const routes: RouteRecordRaw[] = [
    { path: '/', redirect: { name: 'Login' } },
    { path: '/login', name: 'Login', component: Login },
    { path: '/enter-password', name: 'EnterPassword', component: EnterPassword },
    { path: '/dashboard', name: 'Dashboard', component: Dashboard, meta: { requiresAuth: true } },
]

const router = createRouter({
    history: createWebHistory(),
    routes,
})

function isAuthenticated(): boolean {
    return !!localStorage.getItem('auth_token')
}

router.beforeEach((to, _from, next) => {
    if (to.name === 'Login' && isAuthenticated()) {
        return next({ name: 'Dashboard' })
    }
    if ((to.meta as { requiresAuth?: boolean }).requiresAuth && !isAuthenticated()) {
        return next({ name: 'Login' })
    }
    next()
})

export default router
