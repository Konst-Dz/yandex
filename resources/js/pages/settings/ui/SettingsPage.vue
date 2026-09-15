<script setup lang="ts">
import { onMounted, onUnmounted, shallowRef, watch } from 'vue';
import { RouterLink, useRouter } from 'vue-router';

import {
    deleteOrganization,
    listOrganizations,
} from '@/entities/organization';
import type { Organization, OrganizationStatus } from '@/entities/organization';
import { useAuth } from '@/features/auth';
import { ConnectOrganizationForm } from '@/features/connect-organization';
import { useAsync } from '@/shared/lib';
import { StateBlock } from '@/shared/ui';

const STATUS_LABELS: Record<OrganizationStatus, string> = {
    pending: 'Awaiting parsing',
    parsing: 'Parsing in progress',
    ready: 'Data ready',
    error: 'Parsing error',
};

const { data: organizations, status, error, run: loadOrganizations } = useAsync<Organization[] | null>(
    async () => (await listOrganizations()).data,
);
const { user, logout } = useAuth();
const router = useRouter();

const adding = shallowRef(false);
const editingId = shallowRef<number | null>(null);
const deletingId = shallowRef<number | null>(null);

let statusTimer: ReturnType<typeof setInterval> | null = null;

const POLL_INTERVAL_MS = 3000;

onMounted(() => {
    void loadOrganizations();
});

function hasUnfinished(list: Organization[] | null): boolean {
    return (list ?? []).some((item) => item.status === 'pending' || item.status === 'parsing');
}

function stopPolling(): void {
    if (statusTimer !== null) {
        clearInterval(statusTimer);
        statusTimer = null;
    }
}

function syncStatusPolling(list: Organization[] | null): void {
    if (hasUnfinished(list)) {
        if (statusTimer === null) {
            statusTimer = setInterval(async () => {
                try {
                    organizations.value = (await listOrganizations()).data;
                } catch {
                    // keep the previous list; the next tick retries
                }
            }, POLL_INTERVAL_MS);
        }
        return;
    }

    stopPolling();
}

watch(organizations, (list) => {
    syncStatusPolling(list);
});

onUnmounted(stopPolling);

async function handleSaved(): Promise<void> {
    adding.value = false;
    editingId.value = null;
    try {
        organizations.value = (await listOrganizations()).data;
    } catch {
        await loadOrganizations();
    }
}

async function handleDelete(organization: Organization): Promise<void> {
    deletingId.value = organization.id;
    try {
        await deleteOrganization(organization.id);
        await loadOrganizations();
    } finally {
        deletingId.value = null;
    }
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
                <button class="settings__button" type="button" @click="handleLogout">Sign out</button>
            </div>
        </header>

        <StateBlock :status="status" :error="error" @retry="loadOrganizations">
            <button
                class="settings__button settings__button_primary settings__add"
                type="button"
                @click="adding = !adding"
            >
                {{ adding ? 'Hide form' : 'Add organization' }}
            </button>

            <div v-if="adding" class="settings__block settings__reconnect">
                <ConnectOrganizationForm @saved="handleSaved" />
            </div>

            <p v-if="organizations?.length === 0" class="settings__empty">
                No organizations connected yet. Add your first Yandex Maps card above.
            </p>

            <ul class="settings__list">
                <li v-for="organization in organizations ?? []" :key="organization.id" class="settings__card">
                    <div class="settings__card-head">
                        <RouterLink
                            class="settings__card-name"
                            :to="{ name: 'organization', params: { id: organization.id } }"
                        >
                            {{ organization.name }}
                        </RouterLink>
                        <span class="settings__badge" :data-status="organization.status">
                            {{ STATUS_LABELS[organization.status] }}
                        </span>
                    </div>

                    <a class="settings__card-url" :href="organization.url" target="_blank" rel="noopener">
                        {{ organization.url }}
                    </a>

                    <div class="settings__card-stats">
                        <span class="settings__stat">
                            <span class="settings__stat-value">{{ organization.rating ?? '—' }}</span>
                            rating
                        </span>
                        <span class="settings__stat">
                            <span class="settings__stat-value">{{ organization.ratingsCount }}</span>
                            ratings
                        </span>
                        <span class="settings__stat">
                            <span class="settings__stat-value">{{ organization.reviewsCount }}</span>
                            reviews
                        </span>
                    </div>

                    <div class="settings__card-actions">
                        <RouterLink
                            v-if="organization.status === 'ready'"
                            class="settings__button settings__button_primary"
                            :to="{ name: 'organization', params: { id: organization.id } }"
                        >
                            View reviews
                        </RouterLink>
                        <button
                            class="settings__button"
                            type="button"
                            @click="editingId = editingId === organization.id ? null : organization.id"
                        >
                            {{ editingId === organization.id ? 'Hide form' : 'Change link' }}
                        </button>
                        <button
                            class="settings__button settings__button_danger"
                            type="button"
                            :disabled="deletingId === organization.id"
                            @click="handleDelete(organization)"
                        >
                            {{ deletingId === organization.id ? 'Removing…' : 'Remove' }}
                        </button>
                    </div>

                    <div v-if="editingId === organization.id" class="settings__edit">
                        <ConnectOrganizationForm
                            :organization="organization"
                            @saved="handleSaved"
                        />
                        <p class="settings__note">Saving a different link resets this card's reviews and re-parses it.</p>
                    </div>
                </li>
            </ul>
        </StateBlock>
    </section>
</template>

<style scoped>
.settings {
    max-width: 44rem;
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

.settings__button {
    padding: 0.4rem 0.9rem;
    border: 1px solid #d1d5db;
    border-radius: 0.5rem;
    background: none;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
    color: inherit;
    font-size: 0.9rem;
}

.settings__button:hover:not(:disabled) {
    background: #f3f4f6;
}

.settings__button:disabled {
    opacity: 0.6;
    cursor: default;
}

.settings__button_primary {
    border-color: #2563eb;
    color: #2563eb;
}

.settings__button_danger {
    border-color: #fca5a5;
    color: #b91c1c;
}

.settings__add {
    margin-bottom: 1rem;
}

.settings__reconnect {
    margin-bottom: 1.5rem;
}

.settings__empty {
    margin: 1.5rem 0;
    text-align: center;
    color: #6b7280;
}

.settings__list {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.settings__card {
    padding: 1.25rem 1.5rem;
    border: 1px solid #e5e7eb;
    border-radius: 1rem;
    background: #fff;
}

.settings__card-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
}

.settings__card-name {
    font-size: 1.125rem;
    font-weight: 600;
    color: #111827;
    text-decoration: none;
}

.settings__card-name:hover {
    color: #2563eb;
}

.settings__badge {
    padding: 0.2rem 0.6rem;
    border-radius: 999px;
    font-size: 0.75rem;
    background: #f3f4f6;
    color: #4b5563;
    white-space: nowrap;
}

.settings__badge[data-status='ready'] {
    background: #ecfdf5;
    color: #047857;
}

.settings__badge[data-status='error'] {
    background: #fef2f2;
    color: #b91c1c;
}

.settings__badge[data-status='parsing'],
.settings__badge[data-status='pending'] {
    background: #eff6ff;
    color: #1d4ed8;
}

.settings__card-url {
    display: block;
    margin-top: 0.35rem;
    font-size: 0.8rem;
    color: #6b7280;
    text-decoration: none;
    word-break: break-all;
}

.settings__card-stats {
    display: flex;
    gap: 1.5rem;
    margin-top: 0.75rem;
}

.settings__stat {
    display: flex;
    flex-direction: column;
    font-size: 0.75rem;
    color: #6b7280;
}

.settings__stat-value {
    font-size: 1.1rem;
    font-weight: 600;
    color: #111827;
}

.settings__card-actions {
    display: flex;
    gap: 0.75rem;
    margin-top: 0.9rem;
}

.settings__edit {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #e5e7eb;
}

.settings__note {
    margin: 0.75rem 0 0;
    font-size: 0.8rem;
    color: #6b7280;
}
</style>
