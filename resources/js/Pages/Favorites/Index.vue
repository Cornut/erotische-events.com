<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

interface FavoriteEvent {
    id: number;
    title: string;
    slug: string;
    start_date: string;
    organizer?: { company_name: string } | null;
}

defineProps<{ favorites: FavoriteEvent[] }>();
</script>

<template>
    <Head title="Meine Favoriten" />

    <div class="mx-auto max-w-3xl p-6">
        <h1 class="mb-6 text-2xl font-bold">Meine Favoriten</h1>

        <p v-if="favorites.length === 0" class="text-gray-500">
            Du hast noch keine Events als Favorit gespeichert.
        </p>

        <ul class="space-y-3">
            <li
                v-for="event in favorites"
                :key="event.id"
                class="rounded-lg border border-gray-200 p-4"
            >
                <Link :href="`/events/${event.slug}`" class="font-semibold text-indigo-700 hover:underline">
                    {{ event.title }}
                </Link>
                <p class="mt-1 text-xs text-gray-500">
                    <span v-if="event.organizer">{{ event.organizer.company_name }} · </span>
                    {{ new Date(event.start_date).toLocaleDateString('de-DE') }}
                </p>
            </li>
        </ul>
    </div>
</template>
