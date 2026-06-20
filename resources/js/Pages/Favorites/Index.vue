<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import SettingsLayout from '@/Layouts/SettingsLayout.vue';

interface FavoriteEvent {
    id: number;
    title: string;
    slug: string;
    start_date: string;
    organizer?: { company_name: string } | null;
}

const props = defineProps<{ favorites: FavoriteEvent[] }>();

const items = ref<FavoriteEvent[]>([...props.favorites]);

function remove(event: FavoriteEvent): void {
    items.value = items.value.filter((e) => e.id !== event.id);
    router.post(
        `/events/${event.id}/favorite`,
        {},
        { preserveScroll: true, preserveState: true },
    );
}
</script>

<template>
    <Head title="Meine Favoriten" />

    <SettingsLayout>
        <h2 class="mb-6 text-lg font-semibold text-gray-900">Favoriten</h2>

        <p v-if="items.length === 0" class="text-gray-500">
            Du hast noch keine Events als Favorit gespeichert.
        </p>

        <ul class="space-y-3">
            <li
                v-for="event in items"
                :key="event.id"
                class="flex items-start justify-between gap-3 rounded-lg border border-gray-200 bg-white p-4"
            >
                <div>
                    <Link :href="`/events/${event.slug}`" class="font-semibold text-indigo-700 hover:underline">
                        {{ event.title }}
                    </Link>
                    <p class="mt-1 text-xs text-gray-500">
                        <span v-if="event.organizer">{{ event.organizer.company_name }} · </span>
                        {{ new Date(event.start_date).toLocaleDateString('de-DE') }}
                    </p>
                </div>
                <button
                    type="button"
                    class="shrink-0 rounded-md border border-gray-300 px-3 py-1 text-sm text-gray-600 transition hover:border-red-300 hover:bg-red-50 hover:text-red-600"
                    title="Aus Favoriten entfernen"
                    @click="remove(event)"
                >
                    Entfernen
                </button>
            </li>
        </ul>
    </SettingsLayout>
</template>
