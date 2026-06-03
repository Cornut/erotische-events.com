<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

interface OrganizerProfile {
    id: number;
    company_name: string;
    description: string | null;
    website: string | null;
    events?: { id: number; title: string; slug: string; start_date: string }[];
}

defineProps<{ organizer: OrganizerProfile }>();
</script>

<template>
    <Head :title="organizer.company_name" />

    <div class="mx-auto max-w-3xl p-6">
        <h1 class="text-3xl font-bold">{{ organizer.company_name }}</h1>

        <a
            v-if="organizer.website"
            :href="organizer.website"
            target="_blank"
            rel="noopener noreferrer"
            class="mt-1 inline-block text-sm text-indigo-600 hover:underline"
        >{{ organizer.website }}</a>

        <p v-if="organizer.description" class="mt-4 whitespace-pre-line text-gray-800">
            {{ organizer.description }}
        </p>

        <h2 class="mt-8 text-xl font-semibold">Events</h2>
        <ul class="mt-3 space-y-2">
            <li v-for="event in organizer.events ?? []" :key="event.id">
                <Link :href="`/events/${event.slug}`" class="text-indigo-700 hover:underline">
                    {{ event.title }}
                </Link>
                <span class="text-xs text-gray-500">
                    · {{ new Date(event.start_date).toLocaleDateString('de-DE') }}
                </span>
            </li>
            <li v-if="(organizer.events ?? []).length === 0" class="text-gray-500">
                Aktuell keine veröffentlichten Events.
            </li>
        </ul>
    </div>
</template>
