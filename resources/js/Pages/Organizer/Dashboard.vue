<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

interface Venue {
    id: number;
    name: string;
}

interface Event {
    id: number;
    title: string;
    status: string;
    start_date: string | null;
}

interface Organizer {
    id: number;
    company_name: string;
    verification_status: string;
}

const props = defineProps<{
    organizer: Organizer | null;
    events: Event[];
    venues: Venue[];
}>();

const submitEvent = (eventId: number) => {
    router.post(`/organizer/events/${eventId}/submit`);
};
</script>

<template>
    <Head title="Organizer Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Organizer Dashboard
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div v-if="organizer" class="mb-6 overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <p class="text-gray-700">
                            <span class="font-semibold">{{ organizer.company_name }}</span>
                            &mdash; Status: {{ organizer.verification_status }}
                        </p>
                    </div>
                </div>

                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="mb-4 flex items-center justify-between">
                            <h3 class="text-lg font-medium text-gray-900">My Events</h3>
                            <Link
                                :href="route('organizer.events.store')"
                                class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                            >
                                Create Event
                            </Link>
                        </div>

                        <div v-if="events.length === 0" class="text-gray-500">
                            No events yet.
                        </div>

                        <table v-else class="w-full text-left text-sm">
                            <thead>
                                <tr class="border-b">
                                    <th class="py-2 pr-4 font-medium text-gray-700">Title</th>
                                    <th class="py-2 pr-4 font-medium text-gray-700">Status</th>
                                    <th class="py-2 font-medium text-gray-700">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="event in events"
                                    :key="event.id"
                                    class="border-b last:border-0"
                                >
                                    <td class="py-2 pr-4 text-gray-900">{{ event.title }}</td>
                                    <td class="py-2 pr-4 text-gray-600">{{ event.status }}</td>
                                    <td class="py-2">
                                        <button
                                            v-if="event.status === 'draft'"
                                            @click="submitEvent(event.id)"
                                            class="rounded bg-green-600 px-3 py-1 text-xs text-white hover:bg-green-700"
                                        >
                                            Submit for Review
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
