<script setup lang="ts">
import { onMounted } from 'vue';
import { useRouter } from 'vue-router';

import { getOrganization } from '@/entities/organization';
import type { Organization, OrganizationStatus } from '@/entities/organization';
import { useAuth } from '@/features/auth';
import { ConnectOrganizationForm } from '@/features/connect-organization';
import { useAsync } from '@/shared/lib';
import { StateBlock } from '@/shared/ui';

const { data: organization, status, error, run: loadOrganization } = useAsync<Organization | null>(
    async () => (await getOrganization()).data,
);
const { user, logout } = useAuth();
const router = useRouter();

const STATUS_LABELS: Record<OrganizationStatus, string> = {
    pending: 'Awaiting parsing',
    parsing: 'Parsing in progress',
    ready: 'Data ready',
    error: 'Parsing error',
};

onMounted(() => {
    void loadOrganization();
});

async function handleSaved(): Promise<void> {
    await loadOrganization();
}

async function handleLogout(): Promise<void> {
    await logout();
    await router.push({ name: 'login' });
}
</script>

<template>
    <section class="settings">
        <header class="settings__header">
            <h1 class="settings__title">Organization settings</h1>
            <div class="settings__user">
                <span class="settings__email">{{ user?.email }}</span>
                <button class="settings__logout" type="button" @click="handleLogout">Sign out</button>
            </div>
        </header>

        <StateBlock :status="status" :error="error" @retry="loadOrganization">
            <div v-if="organization === null" class="settings__block">
                <h2 class="settings__subtitle">No organization connected</h2>
                <ConnectOrganizationForm @saved="handleSaved" />
            </div>

            <div v-else class="settings__block">
                <h2 class="settings__subtitle">Organization connected</h2>
                <dl class="settings__details">
                    <div class="settings__row">
                        <dt>Link</dt>
                        <dd>{{ organization.url }}</dd>
                    </div>
                    <div class="settings__row">
                        <dt>Status</dt>
                        <dd>{{ STATUS_LABELS[organization.status] }}</dd>
                    </div>
                    <div class="settings__row">
                        <dt>Average rating</dt>
                        <dd>{{ organization.rating ?? '—' }}</dd>
                    </div>
                    <div class="settings__row">
                        <dt>Ratings count</dt>
                        <dd>{{ organization.ratingsCount }}</dd>
                    </div>
                    <div class="settings__row">
                        <dt>Reviews count</dt>
                        <dd>{{ organization.reviewsCount }}</dd>
                    </div>
                </dl>
            </div>
        </StateBlock>
    </section>
</template>

<style scoped>
.settings {
    max-width: 40rem;
    margin: 0 auto;
    padding: 2rem 1rem;
}

.settings__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.settings__title {
    margin: 0;
    font-size: 1.5rem;
}

.settings__user {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.settings__email {
    font-size: 0.875rem;
    color: #4b5563;
}

.settings__logout {
    padding: 0.35rem 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 0.5rem;
    background: none;
    cursor: pointer;
}

.settings__logout:hover {
    background: #f3f4f6;
}

.settings__block {
    padding: 1.5rem;
    border: 1px solid #e5e7eb;
    border-radius: 1rem;
    background: #fff;
}

.settings__subtitle {
    margin: 0 0 1rem;
    font-size: 1.125rem;
}

.settings__details {
    margin: 0;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.settings__row {
    display: flex;
    gap: 0.75rem;
}

.settings__row dt {
    min-width: 11rem;
    color: #6b7280;
}

.settings__row dd {
    margin: 0;
    word-break: break-all;
}
</style>
