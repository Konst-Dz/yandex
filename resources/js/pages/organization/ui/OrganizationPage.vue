<script setup lang="ts">
import { onMounted, shallowRef, watch } from 'vue';
import { RouterLink } from 'vue-router';

import {
    getOrganization,
    getReviews,
    useParsingStatus,
} from '@/entities/organization';
import type {
    Organization,
    PaginatedReviews,
} from '@/entities/organization';
import { useAsync } from '@/shared/lib';
import { Pagination, StateBlock } from '@/shared/ui';

const page = shallowRef(1);

const {
    data: organization,
    status: organizationStatus,
    error: organizationError,
    run: loadOrganization,
} = useAsync<Organization | null>(async () => (await getOrganization()).data);

const {
    data: reviews,
    status: reviewsStatus,
    error: reviewsError,
    run: executeReviews,
} = useAsync<PaginatedReviews | null>(async () => getReviews(page.value));

const parsing = useParsingStatus(() => {
    void loadOrganization();
    void executeReviews();
});

onMounted(() => {
    void loadOrganization();
});

watch(organization, (value) => {
    if (value !== null && (value.status === 'pending' || value.status === 'parsing')) {
        parsing.start();
    }

    if (value !== null && value.status === 'ready') {
        void executeReviews();
    }
});

function changePage(next: number): void {
    page.value = next;
    void executeReviews();
}

function formatDate(value: string | null): string {
    return value === null ? '—' : new Date(value).toLocaleDateString();
}

function formatRating(value: number | null): string {
    return value === null ? '—' : value.toFixed(1);
}
</script>

<template>
    <section class="org">
        <header class="org__header">
            <h1 class="org__title">Organization reviews</h1>
            <RouterLink class="org__settings" :to="{ name: 'settings' }">Settings</RouterLink>
        </header>

        <StateBlock :status="organizationStatus" :error="organizationError" @retry="loadOrganization">
            <div v-if="organization === null" class="org__block">
                <p class="org__empty">No organization connected yet.</p>
                <RouterLink class="org__connect" :to="{ name: 'settings' }">Connect organization</RouterLink>
            </div>

            <div v-else class="org__content">
                <div v-if="parsing.status.value === 'pending' || parsing.status.value === 'parsing'" class="org__block">
                    <StateBlock status="loading">
                        <p>Parsing organization data…</p>
                    </StateBlock>
                </div>

                <div v-else-if="organization.status === 'error'" class="org__block org__error">
                    <h2 class="org__subtitle">Parsing failed</h2>
                    <p class="org__reason">{{ parsing.reason.value ?? organization.url }}</p>
                    <RouterLink class="org__connect" :to="{ name: 'settings' }">Reconnect in settings</RouterLink>
                </div>

                <template v-else>
                    <div class="org__summary">
                        <div class="org__rating">
                            <span class="org__rating-value">{{ formatRating(organization.rating) }}</span>
                            <span class="org__rating-caption">average rating</span>
                        </div>
                        <div class="org__counters">
                            <div class="org__counter">
                                <span class="org__counter-value">{{ organization.ratingsCount }}</span>
                                <span class="org__counter-caption">ratings</span>
                            </div>
                            <div class="org__counter">
                                <span class="org__counter-value">{{ organization.reviewsCount }}</span>
                                <span class="org__counter-caption">reviews</span>
                            </div>
                        </div>
                    </div>

                    <h2 class="org__subtitle">Reviews</h2>

                    <StateBlock :status="reviewsStatus" :error="reviewsError" @retry="executeReviews">
                        <ul class="org__list">
                            <li
                                v-for="review in reviews?.data ?? []"
                                :key="review.id"
                                class="org__review"
                            >
                                <div class="org__review-head">
                                    <span class="org__review-author">{{ review.author }}</span>
                                    <span class="org__review-meta">
                                        <span class="org__review-rating">★ {{ review.rating ?? '—' }}</span>
                                        <span class="org__review-date">{{ formatDate(review.reviewedAt) }}</span>
                                    </span>
                                </div>
                                <p class="org__review-text">{{ review.text }}</p>
                            </li>
                        </ul>

                        <Pagination
                            :page="page"
                            :last-page="reviews?.meta.last_page ?? 1"
                            :disabled="reviewsStatus === 'loading'"
                            @change="changePage"
                        />
                    </StateBlock>
                </template>
            </div>
        </StateBlock>
    </section>
</template>

<style scoped>
.org {
    max-width: 48rem;
    margin: 0 auto;
    padding: 2rem 1rem;
}

.org__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1.5rem;
}

.org__title {
    margin: 0;
    font-size: 1.5rem;
}

.org__settings,
.org__connect {
    padding: 0.4rem 0.9rem;
    border: 1px solid #d1d5db;
    border-radius: 0.5rem;
    color: #1d4ed8;
    text-decoration: none;
}

.org__block {
    padding: 1.5rem;
    border: 1px solid #e5e7eb;
    border-radius: 1rem;
    background: #fff;
    text-align: center;
}

.org__empty {
    margin: 0 0 1rem;
    color: #4b5563;
}

.org__subtitle {
    margin: 1.5rem 0 0.75rem;
    font-size: 1.125rem;
}

.org__error {
    border-color: #fecaca;
    background: #fef2f2;
}

.org__reason {
    color: #b91c1c;
    margin: 0 0 1rem;
}

.org__summary {
    display: flex;
    align-items: center;
    gap: 2rem;
    padding: 1.5rem;
    border: 1px solid #e5e7eb;
    border-radius: 1rem;
    background: #fff;
}

.org__rating {
    display: flex;
    flex-direction: column;
    align-items: center;
}

.org__rating-value {
    font-size: 2.5rem;
    font-weight: 700;
}

.org__rating-caption,
.org__counter-caption {
    font-size: 0.8rem;
    color: #6b7280;
}

.org__counters {
    display: flex;
    gap: 2rem;
}

.org__counter {
    display: flex;
    flex-direction: column;
    align-items: center;
}

.org__counter-value {
    font-size: 1.5rem;
    font-weight: 600;
}

.org__list {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.org__review {
    padding: 1rem 1.25rem;
    border: 1px solid #e5e7eb;
    border-radius: 0.75rem;
    background: #fff;
}

.org__review-head {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 0.4rem;
}

.org__review-author {
    font-weight: 600;
}

.org__review-meta {
    display: flex;
    gap: 0.75rem;
    font-size: 0.85rem;
    color: #6b7280;
}

.org__review-rating {
    color: #f59e0b;
}

.org__review-text {
    margin: 0;
    line-height: 1.5;
    color: #374151;
}
</style>
