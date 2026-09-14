<script setup lang="ts">
import { LoginForm } from '@/features/auth';
</script>

<template>
    <section class="page">
        <div class="page__card">
            <h1 class="page__title">Sign in</h1>
            <LoginForm />
        </div>
    </section>
</template>

<style scoped>
.page {
    display: flex;
    min-height: 100vh;
    align-items: center;
    justify-content: center;
    background: #f9fafb;
}

.page__card {
    width: min(24rem, 100%);
    padding: 2rem;
    border: 1px solid #e5e7eb;
    border-radius: 1rem;
    background: #fff;
}

.page__title {
    margin: 0 0 1.5rem;
    font-size: 1.5rem;
    text-align: center;
}
</style>
