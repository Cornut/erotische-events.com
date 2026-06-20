<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import FavoriteHeart from '@/Components/FavoriteHeart.vue';

interface EventDetail {
    id: number;
    title: string;
    long_description: string | null;
    start_date: string;
    end_date: string | null;
    is_favorited?: boolean;
    booking_title?: string | null;
    organizer?: { company_name: string; slug: string } | null;
    venue?: { name: string; city: string | null } | null;
    categories?: { id: number; name_de: string }[];
    tags?: { id: number; name: string }[];
    teachers?: { id: number; name: string; slug: string }[];
    prices?: { id: number; type: string; amount: string; currency: string }[];
}

defineProps<{
    event: EventDetail;
    calendarBackUrl?: string | null;
    teacherBackUrl?: string | null;
    eventsBackUrl?: string | null;
}>();
</script>

<template>
    <Head :title="event.title" />

    <AppLayout>
    <div class="mx-auto max-w-3xl p-6">
        <div class="flex flex-wrap gap-4 text-sm">
            <Link
                v-if="calendarBackUrl"
                :href="calendarBackUrl"
                class="text-indigo-600 hover:underline"
            >&larr; Zurück zum Kalender</Link>
            <Link
                v-if="teacherBackUrl"
                :href="teacherBackUrl"
                class="text-indigo-600 hover:underline"
            >&larr; Zurück zum Facilitator</Link>
            <Link :href="eventsBackUrl ?? '/events'" class="text-indigo-600 hover:underline">
                &larr; {{ eventsBackUrl ? 'Zurück zur Suche' : 'Alle Events' }}
            </Link>
            <Link
                v-if="event.organizer"
                :href="`/organizers/${event.organizer.slug}`"
                class="text-indigo-600 hover:underline"
            >&larr; Zu den Veranstalter:innen</Link>
        </div>

        <div class="mt-4 flex items-start justify-between gap-3">
            <h1 class="text-3xl font-bold">{{ event.title }}</h1>
            <FavoriteHeart
                class="mt-1 shrink-0"
                :event-id="event.id"
                :favorited="event.is_favorited"
            />
        </div>

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

        <!-- Teachers -->
        <div v-if="event.teachers?.length" class="mt-6">
            <h2 class="text-sm font-semibold text-gray-700">Lehrer:innen</h2>
            <div class="mt-2 flex flex-wrap gap-2">
                <Link
                    v-for="teacher in event.teachers"
                    :key="teacher.id"
                    :href="`/teacher/${teacher.slug}`"
                    class="rounded-full bg-indigo-50 px-3 py-1 text-sm text-indigo-700 hover:bg-indigo-100"
                >
                    {{ teacher.name }}
                </Link>
            </div>
        </div>

        <div class="mt-6 whitespace-pre-line text-gray-800">{{ event.long_description }}</div>

        <ul v-if="event.prices?.length" class="mt-6 space-y-1 text-sm">
            <li v-for="price in event.prices" :key="price.id">
                <span v-if="price.currency !== 'EUR'">{{ price.type }}: </span>
                {{ price.amount }} {{ price.currency }}
            </li>
        </ul>

        <!-- Obfuscated outbound link: routed through /go/{event} which records an
             event_click and 302-redirects to the organizer URL (no raw URL in the page). -->
        <a
            :href="`/go/${event.id}`"
            target="_blank"
            rel="noopener noreferrer nofollow"
            class="mt-8 inline-block rounded-md bg-indigo-600 px-5 py-3 font-semibold text-white hover:bg-indigo-700"
        >
            {{ event.booking_title || 'Zur WebSeite' }}
        </a>
    </div>
    </AppLayout>
</template>
