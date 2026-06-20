<script setup lang="ts">
import { reactive } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

interface TeacherItem {
    id: number;
    slug: string;
    name: string;
    bio: string | null;
    events_count: number;
}

const props = defineProps<{
    teachers: TeacherItem[];
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
    <Head title="Facilitator" />

    <AppLayout>
        <div class="mx-auto max-w-5xl p-6">
            <h1 class="text-2xl font-bold">Facilitator</h1>
            <p class="mb-6 mt-1 text-gray-600">Alle Lehrer:innen mit veröffentlichten Events</p>

            <form class="mb-6 flex flex-wrap gap-2" @submit.prevent="submit">
                <input
                    v-model="form.q"
                    type="search"
                    placeholder="Facilitator suchen…"
                    class="flex-1 rounded-md border-gray-300"
                />
                <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 font-semibold text-white hover:bg-indigo-700">
                    Suchen
                </button>
            </form>

            <p class="mb-4 text-sm text-gray-500">
                {{ teachers.length }}
                {{ teachers.length === 1 ? 'Facilitator' : 'Facilitator' }}
            </p>

            <div v-if="teachers.length === 0" class="text-gray-500">
                Keine Facilitator gefunden.
            </div>

            <ul class="grid gap-4 sm:grid-cols-2">
                <li
                    v-for="teacher in teachers"
                    :key="teacher.id"
                    class="rounded-lg border border-gray-200 p-4 hover:shadow"
                >
                    <Link
                        :href="`/teacher/${teacher.slug}`"
                        class="text-lg font-semibold text-indigo-700 hover:underline"
                    >
                        {{ teacher.name }}
                    </Link>
                    <p class="mt-1 text-xs text-gray-500">
                        {{ teacher.events_count }}
                        {{ teacher.events_count === 1 ? 'Event' : 'Events' }}
                    </p>
                    <p v-if="teacher.bio" class="mt-2 line-clamp-2 text-sm text-gray-600">
                        {{ teacher.bio }}
                    </p>
                </li>
            </ul>
        </div>
    </AppLayout>
</template>
