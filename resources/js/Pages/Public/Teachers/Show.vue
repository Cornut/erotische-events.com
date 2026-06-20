<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

interface TeacherEvent {
    id: number;
    title: string;
    slug: string;
    start_date: string;
    organizer?: { company_name: string } | null;
    venue?: { city: string | null } | null;
}

interface TeacherProfile {
    id: number;
    slug: string;
    name: string;
    bio: string | null;
    events?: TeacherEvent[];
}

defineProps<{ teacher: TeacherProfile }>();

function formatDate(date: string): string {
    return new Date(date).toLocaleDateString('de-DE', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
}
</script>

<template>
    <Head :title="teacher.name" />

    <AppLayout>
        <div class="mx-auto max-w-3xl p-6">
            <Link href="/teachers" class="text-sm text-indigo-600 hover:underline">&larr; Alle Facilitator</Link>

            <h1 class="mt-4 text-3xl font-bold">{{ teacher.name }}</h1>

            <p v-if="teacher.bio" class="mt-4 whitespace-pre-line text-gray-800">
                {{ teacher.bio }}
            </p>

            <h2 class="mt-8 text-xl font-semibold">Events</h2>
            <ul class="mt-3 space-y-2">
                <li v-for="event in teacher.events ?? []" :key="event.id">
                    <Link :href="`/events/${event.slug}?from=teacher&teacher=${teacher.slug}`" class="font-medium text-indigo-700 hover:underline">
                        {{ event.title }}
                    </Link>
                    <span class="text-xs text-gray-500">
                        ·
                        <span v-if="event.organizer">{{ event.organizer.company_name }} · </span>
                        <span v-if="event.venue?.city">{{ event.venue.city }} · </span>
                        {{ formatDate(event.start_date) }}
                    </span>
                </li>
                <li v-if="(teacher.events ?? []).length === 0" class="text-gray-500">
                    Aktuell keine veröffentlichten Events.
                </li>
            </ul>
        </div>
    </AppLayout>
</template>
