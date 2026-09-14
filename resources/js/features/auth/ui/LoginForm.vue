<script setup lang="ts">
import { shallowRef } from 'vue'
import { useRouter } from 'vue-router'

import { ApiError } from '@/shared/api'

import { useAuth } from '../model/useAuth'

const email = shallowRef('')
const password = shallowRef('')
const error = shallowRef<string | null>(null)
const submitting = shallowRef(false)

const router = useRouter()
const { login } = useAuth()

async function submit(): Promise<void> {
    if (submitting.value) {
        return
    }

    submitting.value = true
    error.value = null

    try {
        await login(email.value, password.value)
        await router.push({ name: 'settings' })
    } catch (e) {
        error.value = describeError(e)
    } finally {
        submitting.value = false
    }
}

function describeError(e: unknown): string {
    if (e instanceof ApiError) {
        return e.status === 401 ? 'Invalid email or password.' : e.message
    }

    return 'Sign in failed. Please try again.'
}
</script>

<template>
    <form class="form" @submit.prevent="submit">
        <label class="form__field">
            <span class="form__label">Email</span>
            <input
                v-model="email"
                class="form__input"
                type="email"
                name="email"
                required
                autocomplete="email"
            >
        </label>

        <label class="form__field">
            <span class="form__label">Password</span>
            <input
                v-model="password"
                class="form__input"
                type="password"
                name="password"
                required
                autocomplete="current-password"
            >
        </label>

        <p v-if="error !== null" class="form__error" role="alert">{{ error }}</p>

        <button class="form__submit" type="submit" :disabled="submitting">
            {{ submitting ? 'Signing in…' : 'Sign in' }}
        </button>
    </form>
</template>

<style scoped>
.form {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.form__field {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.form__label {
    font-size: 0.875rem;
    color: #4b5563;
}

.form__input {
    padding: 0.5rem 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 0.5rem;
    font-size: 1rem;
}

.form__input:focus {
    outline: 2px solid #2563eb;
    outline-offset: -1px;
    border-color: transparent;
}

.form__error {
    margin: 0;
    padding: 0.5rem 0.75rem;
    border-radius: 0.5rem;
    background: #fef2f2;
    color: #b91c1c;
    font-size: 0.875rem;
}

.form__submit {
    padding: 0.6rem 1rem;
    border: none;
    border-radius: 0.5rem;
    background: #2563eb;
    color: #fff;
    font-size: 1rem;
    cursor: pointer;
}

.form__submit:hover:not(:disabled) {
    background: #1d4ed8;
}

.form__submit:disabled {
    opacity: 0.6;
    cursor: default;
}
</style>
