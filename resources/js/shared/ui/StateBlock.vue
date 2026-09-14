<script setup lang="ts">
import type { AsyncStatus } from '../lib/useAsync';

defineProps<{
    status: AsyncStatus;
    error?: string | null;
}>();

const emit = defineEmits<{
    retry: [];
}>();
</script>

<template>
    <div v-if="status === 'loading'" class="state">
        <span class="state__spinner" aria-hidden="true" />
        <p class="state__message">Loading…</p>
    </div>

    <div v-else-if="status === 'error'" class="state">
        <p class="state__message">{{ error ?? 'Something went wrong' }}</p>
        <button class="state__retry" type="button" @click="emit('retry')">Retry</button>
    </div>

    <slot v-else-if="status === 'idle'" name="empty" />

    <slot v-else />
</template>

<style scoped>
.state {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.75rem;
    padding: 2rem 1rem;
    color: #6b7280;
}

.state__message {
    margin: 0;
}

.state__spinner {
    width: 1.5rem;
    height: 1.5rem;
    border: 2px solid #d1d5db;
    border-top-color: #2563eb;
    border-radius: 50%;
    animation: state-spin 0.8s linear infinite;
}

.state__retry {
    padding: 0.4rem 1rem;
    border: 1px solid #d1d5db;
    border-radius: 0.5rem;
    background: none;
    cursor: pointer;
}

.state__retry:hover {
    background: #f3f4f6;
}

@keyframes state-spin {
    to {
        transform: rotate(360deg);
    }
}
</style>
