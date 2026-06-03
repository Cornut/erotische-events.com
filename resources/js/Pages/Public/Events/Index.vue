<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive } from 'vue';

interface EventItem {
    id: number;
    title: string;
    slug: string;
    short_description: string | null;
    start_date: string;
    organizer?: { company_name: string } | null;
    venue?: { city: string | null } | null;
}

const props = defineProps<{
    events: {
        data: EventItem[];
    };
    filters: {
        q: string;
        city: string;
        category: string;
    };
}>();

const form = reactive({
    q: props.filters.q ?? '',
    city: props.filters.city ?? '',
});

function submit() {
    router.get('/events', { q: form.q || undefined, city: form.city || undefined }, {
        preserveState: true,
        replace: true,
    });
}
</script>

<template>
    <Head title="Events" />

    <div class="mx-auto max-w-5xl p-6">
        <h1 class="mb-6 text-2xl font-bold">Events</h1>

        <form class="mb-6 flex flex-wrap gap-2" @submit.prevent="submit">
            <input
                v-model="form.q"
                type="search"
                placeholder="Suche…"
                class="flex-1 rounded-md border-gray-300"
            />
            <input
                v-model="form.city"
                type="text"
                placeholder="Stadt"
                class="w-40 rounded-md border-gray-300"
            />
            <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 font-semibold text-white hover:bg-indigo-700">
                Suchen
            </button>
        </form>

        <div v-if="events.data.length === 0" class="text-gray-500">
            Noch keine veröffentlichten Events.
        </div>

        <ul class="grid gap-4 sm:grid-cols-2">
            <li
                v-for="event in events.data"
                :key="event.id"
                class="rounded-lg border border-gray-200 p-4 hover:shadow"
            >
                <Link
                    :href="`/events/${event.slug}`"
                    class="text-lg font-semibold text-indigo-700 hover:underline"
                >
                    {{ event.title }}
                </Link>
                <p class="mt-1 text-sm text-gray-600">{{ event.short_description }}</p>
                <p class="mt-2 text-xs text-gray-500">
                    <span v-if="event.organizer">{{ event.organizer.company_name }} · </span>
                    <span v-if="event.venue?.city">{{ event.venue.city }} · </span>
                    {{ new Date(event.start_date).toLocaleDateString('de-DE') }}
                </p>
            </li>
        </ul>
    </div>
</template>
