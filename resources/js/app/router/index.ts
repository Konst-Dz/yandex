import { createRouter, createWebHistory } from 'vue-router';

import LoginPage from '@/pages/login';
import OrganizationPage from '@/pages/organization';
import SettingsPage from '@/pages/settings';

export default createRouter({
    history: createWebHistory(),
    routes: [
        { path: '/', redirect: { name: 'login' } },
        { path: '/login', name: 'login', component: LoginPage },
        { path: '/settings', name: 'settings', component: SettingsPage },
        { path: '/organization', name: 'organization', component: OrganizationPage },
    ],
});
