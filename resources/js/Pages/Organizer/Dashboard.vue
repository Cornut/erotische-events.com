<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import OrganizerLayout from '@/Layouts/OrganizerLayout.vue';

interface Organizer {
    id: number;
    company_name: string;
    verification_status: string;
}
interface EventItem {
    id: number;
    title: string;
    status: string;
    start_date: string | null;
}

const props = defineProps<{
    organizer: Organizer | null;
    events: EventItem[];
    venues: { id: number; name: string }[];
}>();

const statusLabel: Record<string, string> = {
    pending: 'in Prüfung',
    approved: 'freigegeben',
    rejected: 'abgelehnt',
};
</script>

<template>
    <Head title="Veranstalter:innen-Bereich" />

    <OrganizerLayout>
        <div v-if="organizer" class="mb-6 rounded-lg border border-gray-200 bg-white p-4">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-lg font-semibold text-gray-900">{{ organizer.company_name }}</div>
                    <div class="text-sm text-gray-500">
                        Status: {{ statusLabel[organizer.verification_status] ?? organizer.verification_status }}
                    </div>
                </div>
                <Link href="/organizer/profile" class="text-sm text-indigo-600 hover:underline">
                    Stammdaten bearbeiten
                </Link>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-3">
            <Link href="/organizer/events" class="rounded-lg border border-gray-200 bg-white p-4 hover:shadow">
                <div class="text-2xl font-bold text-indigo-700">{{ props.events.length }}</div>
                <div class="text-sm text-gray-600">Events verwalten</div>
            </Link>
            <Link href="/organizer/venues" class="rounded-lg border border-gray-200 bg-white p-4 hover:shadow">
                <div class="text-2xl font-bold text-indigo-700">{{ props.venues.length }}</div>
                <div class="text-sm text-gray-600">Venues verwalten</div>
            </Link>
            <Link href="/organizer/teachers" class="rounded-lg border border-gray-200 bg-white p-4 hover:shadow">
                <div class="text-2xl font-bold text-indigo-700">Teacher</div>
                <div class="text-sm text-gray-600">Lehrer:innen-Pool</div>
            </Link>
        </div>

        <div class="mt-6">
            <Link
                href="/organizer/events/create"
                class="inline-flex rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700"
            >
                + Neues Event
            </Link>
        </div>
    </OrganizerLayout>
</template>
