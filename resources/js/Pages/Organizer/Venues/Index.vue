<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import OrganizerLayout from '@/Layouts/OrganizerLayout.vue';

interface VenueItem {
    id: number;
    name: string;
    street: string | null;
    city: string | null;
    country: string | null;
    latitude: string | number | null;
    longitude: string | number | null;
}

defineProps<{ venues: VenueItem[] }>();

function destroy(id: number): void {
    if (confirm('Diese Venue wirklich löschen?')) {
        router.delete(`/organizer/venues/${id}`, { preserveScroll: true });
    }
}
</script>

<template>
    <Head title="Meine Venues" />

    <OrganizerLayout>
        <div class="mb-6 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900">Meine Venues</h2>
            <Link href="/organizer/venues/create" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                + Neue Venue
            </Link>
        </div>

        <p v-if="venues.length === 0" class="text-gray-500">Noch keine Venues angelegt.</p>

        <ul v-else class="space-y-3">
            <li
                v-for="venue in venues"
                :key="venue.id"
                class="flex items-start justify-between gap-3 rounded-lg border border-gray-200 bg-white p-4"
            >
                <div>
                    <div class="font-semibold text-gray-900">{{ venue.name }}</div>
                    <div class="text-sm text-gray-500">
                        <span v-if="venue.street">{{ venue.street }}, </span>
                        <span v-if="venue.city">{{ venue.city }}</span>
                        <span v-if="venue.country"> ({{ venue.country }})</span>
                    </div>
                    <div class="mt-1 text-xs" :class="venue.latitude ? 'text-green-600' : 'text-amber-600'">
                        {{ venue.latitude ? `GPS: ${venue.latitude}, ${venue.longitude}` : 'Keine Koordinaten' }}
                    </div>
                </div>
                <div class="space-x-3 whitespace-nowrap text-sm">
                    <Link :href="`/organizer/venues/${venue.id}/edit`" class="text-indigo-600 hover:underline">Bearbeiten</Link>
                    <button type="button" class="text-red-600 hover:underline" @click="destroy(venue.id)">Löschen</button>
                </div>
            </li>
        </ul>
    </OrganizerLayout>
</template>
