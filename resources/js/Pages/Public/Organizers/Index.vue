<script setup lang="ts">
import { reactive } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

interface OrganizerItem {
    id: number;
    slug: string;
    company_name: string;
    description: string | null;
    events_count: number;
}

const props = defineProps<{
    organizers: OrganizerItem[];
    filters: { q: string };
}>();

const form = reactive({
    q: props.filters.q ?? '',
});

function submit(): void {
    router.get(
        window.location.pathname,
        { q: form.q || undefined },
        { preserveState: true, replace: true },
    );
}
</script>

<template>
    <Head title="Veranstalter:innen" />

    <AppLayout>
        <div class="mx-auto max-w-5xl p-6">
            <h1 class="text-2xl font-bold">Veranstalter:innen</h1>
            <p class="mb-6 mt-1 text-gray-600">Alle Veranstalter:innen mit veröffentlichten Events</p>

            <form class="mb-6 flex flex-wrap gap-2" @submit.prevent="submit">
                <input
                    v-model="form.q"
                    type="search"
                    placeholder="Veranstalter:innen suchen…"
                    class="flex-1 rounded-md border-gray-300"
                />
                <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 font-semibold text-white hover:bg-indigo-700">
                    Suchen
                </button>
            </form>

            <p class="mb-4 text-sm text-gray-500">
                {{ organizers.length }}
                {{ organizers.length === 1 ? 'Veranstalter:innen' : 'Veranstalter:innen' }}
            </p>

            <div v-if="organizers.length === 0" class="text-gray-500">
                Keine Veranstalter:innen gefunden.
            </div>

            <ul class="grid gap-4 sm:grid-cols-2">
                <li
                    v-for="organizer in organizers"
                    :key="organizer.id"
                    class="rounded-lg border border-gray-200 p-4 hover:shadow"
                >
                    <Link
                        :href="`/organizers/${organizer.slug}`"
                        class="text-lg font-semibold text-indigo-700 hover:underline"
                    >
                        {{ organizer.company_name }}
                    </Link>
                    <p class="mt-1 text-xs text-gray-500">
                        {{ organizer.events_count }}
                        {{ organizer.events_count === 1 ? 'Event' : 'Events' }}
                    </p>
                    <p v-if="organizer.description" class="mt-2 line-clamp-2 text-sm text-gray-600">
                        {{ organizer.description }}
                    </p>
                </li>
            </ul>
        </div>
    </AppLayout>
</template>
