<script setup lang="ts">
import { computed, shallowRef } from 'vue'

import { saveOrganizationLink, updateOrganizationLink } from '@/entities/organization'
import { ApiError } from '@/shared/api'

import type { Organization } from '@/entities/organization'

const props = defineProps<{
    organization?: Organization
}>()

const emit = defineEmits<{
    saved: [organization: Organization]
}>()

const url = shallowRef(props.organization?.url ?? '')
const error = shallowRef<string | null>(null)
const submitting = shallowRef(false)

const isEdit = computed(() => props.organization !== undefined)

const submitLabel = computed(() => {
    if (submitting.value) {
        return isEdit.value ? 'Saving…' : 'Connecting…'
    }

    return isEdit.value ? 'Save' : 'Connect'
})

async function submit(): Promise<void> {
    if (submitting.value) {
        return
    }

    submitting.value = true
    error.value = null

    try {
        const response = isEdit.value
            ? await updateOrganizationLink(props.organization!.id, url.value)
            : await saveOrganizationLink(url.value)
        emit('saved', response.data)
    } catch (e) {
        error.value = describeError(e)
    } finally {
        submitting.value = false
    }
}

function describeError(e: unknown): string {
    if (e instanceof ApiError) {
        return e.fieldMessages[0] ?? e.message
    }

    return 'Failed to save the link. Please try again.'
}
</script>

<template>
    <form class="connect" @submit.prevent="submit">
        <label class="connect__field">
            <span class="connect__label">Organization card link on Yandex Maps</span>
            <input
                v-model="url"
                class="connect__input"
                type="url"
                name="url"
                placeholder="https://yandex.ru/maps/org/…"
                required
            >
        </label>

        <p v-if="error !== null" class="connect__error" role="alert">{{ error }}</p>

        <button class="connect__submit" type="submit" :disabled="submitting">
            {{ submitLabel }}
        </button>
    </form>
</template>

<style scoped>
.connect {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.connect__field {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    text-align: left;
}

.connect__label {
    font-size: 0.875rem;
    color: #4b5563;
}

.connect__input {
    padding: 0.5rem 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 0.5rem;
    font-size: 1rem;
}

.connect__input:focus {
    outline: 2px solid #2563eb;
    outline-offset: -1px;
    border-color: transparent;
}

.connect__error {
    margin: 0;
    padding: 0.5rem 0.75rem;
    border-radius: 0.5rem;
    background: #fef2f2;
    color: #b91c1c;
    font-size: 0.875rem;
}

.connect__submit {
    padding: 0.6rem 1rem;
    border: none;
    border-radius: 0.5rem;
    background: #2563eb;
    color: #fff;
    font-size: 1rem;
    cursor: pointer;
}

.connect__submit:hover:not(:disabled) {
    background: #1d4ed8;
}

.connect__submit:disabled {
    opacity: 0.6;
    cursor: default;
}
</style>
