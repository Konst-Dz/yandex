import { createRouter, createWebHistory } from 'vue-router';

import { useAuth } from '@/features/auth';
import LoginPage from '@/pages/login';
import OrganizationPage from '@/pages/organization';
import SettingsPage from '@/pages/settings';

const router = createRouter({
    history: createWebHistory(),
    routes: [
        { path: '/', redirect: { name: 'login' } },
        { path: '/login', name: 'login', component: LoginPage },
        { path: '/settings', name: 'settings', component: SettingsPage, meta: { requiresAuth: true } },
        { path: '/organization', name: 'organization', component: OrganizationPage, meta: { requiresAuth: true } },
    ],
});

router.beforeEach(async (to) => {
    const { ensureLoaded, user } = useAuth();

    await ensureLoaded();

    if (to.meta.requiresAuth === true && user.value === null) {
        return { name: 'login' };
    }

    if (to.name === 'login' && user.value !== null) {
        return { name: 'settings' };
    }

    return true;
});

export default router;
