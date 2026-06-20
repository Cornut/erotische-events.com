<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import OrganizerLayout from '@/Layouts/OrganizerLayout.vue';

interface EventItem {
    id: number;
    title: string;
    status: string;
    start_date: string | null;
    venue?: { name: string } | null;
}

defineProps<{ events: EventItem[] }>();

const statusLabel: Record<string, string> = {
    draft: 'Entwurf',
    pending_review: 'in Prüfung',
    published: 'veröffentlicht',
    rejected: 'abgelehnt',
};

function submit(id: number): void {
    router.post(`/organizer/events/${id}/submit`, {}, { preserveScroll: true });
}

function destroy(id: number): void {
    if (confirm('Dieses Event wirklich löschen?')) {
        router.delete(`/organizer/events/${id}`, { preserveScroll: true });
    }
}

function formatDate(date: string | null): string {
    return date ? new Date(date).toLocaleDateString('de-DE') : '—';
}
</script>

<template>
    <Head title="Meine Events" />

    <OrganizerLayout>
        <div class="mb-6 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900">Meine Events</h2>
            <Link href="/organizer/events/create" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                + Neues Event
            </Link>
        </div>

        <p v-if="events.length === 0" class="text-gray-500">Noch keine Events. Lege dein erstes an.</p>

        <table v-else class="w-full text-left text-sm">
            <thead>
                <tr class="border-b text-gray-500">
                    <th class="py-2 pr-4 font-medium">Titel</th>
                    <th class="py-2 pr-4 font-medium">Datum</th>
                    <th class="py-2 pr-4 font-medium">Status</th>
                    <th class="py-2 font-medium">Aktionen</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="event in events" :key="event.id" class="border-b last:border-0">
                    <td class="py-2 pr-4 text-gray-900">{{ event.title }}</td>
                    <td class="py-2 pr-4 text-gray-600">{{ formatDate(event.start_date) }}</td>
                    <td class="py-2 pr-4">
                        <span class="rounded bg-gray-100 px-2 py-0.5 text-xs text-gray-700">
                            {{ statusLabel[event.status] ?? event.status }}
                        </span>
                    </td>
                    <td class="space-x-3 py-2 whitespace-nowrap">
                        <Link :href="`/organizer/events/${event.id}/edit`" class="text-indigo-600 hover:underline">Bearbeiten</Link>
                        <button v-if="event.status === 'draft'" type="button" class="text-green-700 hover:underline" @click="submit(event.id)">Einreichen</button>
                        <button type="button" class="text-red-600 hover:underline" @click="destroy(event.id)">Löschen</button>
                    </td>
                </tr>
            </tbody>
        </table>
    </OrganizerLayout>
</template>
