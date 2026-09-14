<script setup lang="ts">
defineProps<{
    page: number
    lastPage: number
    disabled?: boolean
}>()

const emit = defineEmits<{
    change: [page: number]
}>()
</script>

<template>
    <div v-if="lastPage > 1" class="pagination">
        <button
            class="pagination__button"
            type="button"
            :disabled="disabled || page <= 1"
            @click="emit('change', page - 1)"
        >
            Previous
        </button>

        <span class="pagination__page">{{ page }} / {{ lastPage }}</span>

        <button
            class="pagination__button"
            type="button"
            :disabled="disabled || page >= lastPage"
            @click="emit('change', page + 1)"
        >
            Next
        </button>
    </div>
</template>

<style scoped>
.pagination {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 1rem;
    margin-top: 1rem;
}

.pagination__button {
    padding: 0.4rem 0.9rem;
    border: 1px solid #d1d5db;
    border-radius: 0.5rem;
    background: none;
    cursor: pointer;
}

.pagination__button:hover:not(:disabled) {
    background: #f3f4f6;
}

.pagination__button:disabled {
    opacity: 0.5;
    cursor: default;
}

.pagination__page {
    font-size: 0.9rem;
    color: #4b5563;
}
</style>
