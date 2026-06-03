<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

interface EventDetail {
    id: number;
    title: string;
    long_description: string | null;
    start_date: string;
    end_date: string | null;
    booking_url: string;
    organizer?: { company_name: string; slug: string } | null;
    venue?: { name: string; city: string | null } | null;
    categories?: { id: number; name_de: string }[];
    tags?: { id: number; name: string }[];
    teachers?: { id: number; name: string }[];
    prices?: { id: number; type: string; amount: string; currency: string }[];
}

defineProps<{ event: EventDetail }>();
</script>

<template>
    <Head :title="event.title" />

    <div class="mx-auto max-w-3xl p-6">
        <Link href="/events" class="text-sm text-indigo-600 hover:underline">&larr; Alle Events</Link>

        <h1 class="mt-4 text-3xl font-bold">{{ event.title }}</h1>

        <p class="mt-2 text-sm text-gray-500">
            <Link
                v-if="event.organizer"
                :href="`/organizers/${event.organizer.slug}`"
                class="text-indigo-600 hover:underline"
            >{{ event.organizer.company_name }}</Link>
            <span v-if="event.venue"> · {{ event.venue.name }}<span v-if="event.venue.city">, {{ event.venue.city }}</span></span>
        </p>

        <p class="mt-1 text-sm text-gray-500">
            {{ new Date(event.start_date).toLocaleDateString('de-DE') }}
            <span v-if="event.end_date"> – {{ new Date(event.end_date).toLocaleDateString('de-DE') }}</span>
        </p>

        <div v-if="event.categories?.length" class="mt-3 flex flex-wrap gap-2">
            <span
                v-for="category in event.categories"
                :key="category.id"
                class="rounded bg-gray-100 px-2 py-1 text-xs"
            >{{ category.name_de }}</span>
        </div>

        <div class="mt-6 whitespace-pre-line text-gray-800">{{ event.long_description }}</div>

        <ul v-if="event.prices?.length" class="mt-6 space-y-1 text-sm">
            <li v-for="price in event.prices" :key="price.id">
                {{ price.type }}: {{ price.amount }} {{ price.currency }}
            </li>
        </ul>

        <a
            :href="event.booking_url"
            target="_blank"
            rel="noopener noreferrer"
            class="mt-8 inline-block rounded-md bg-indigo-600 px-5 py-3 font-semibold text-white hover:bg-indigo-700"
        >
            Zum Veranstalter
        </a>
    </div>
</template>
