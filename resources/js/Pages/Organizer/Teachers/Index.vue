<script setup lang="ts">
import { reactive } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import OrganizerLayout from '@/Layouts/OrganizerLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

interface Teacher {
    id: number;
    slug: string;
    name: string;
    bio: string | null;
}

const props = defineProps<{
    teachers: Teacher[];
    filters: { q: string };
}>();

const search = reactive({ q: props.filters.q ?? '' });

function submitSearch(): void {
    router.get('/organizer/teachers', { q: search.q || undefined }, { preserveState: true, replace: true });
}

const form = useForm({ name: '', bio: '' });

const create = () =>
    form.post('/organizer/teachers', {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
</script>

<template>
    <Head title="Teacher" />

    <OrganizerLayout>
        <h2 class="mb-2 text-lg font-semibold text-gray-900">Teacher (gemeinsamer Pool)</h2>
        <p class="mb-6 text-sm text-gray-600">
            Teacher sind plattformweit geteilt. Lege neue an und wähle sie dann im Event-Formular aus.
        </p>

        <div class="grid gap-8 md:grid-cols-[1fr_20rem]">
            <div>
                <form class="mb-4 flex gap-2" @submit.prevent="submitSearch">
                    <input v-model="search.q" type="search" placeholder="Teacher suchen…" class="flex-1 rounded-md border-gray-300 text-sm" />
                    <button type="submit" class="rounded-md border border-gray-300 px-4 py-2 text-sm hover:bg-gray-50">Suchen</button>
                </form>

                <p v-if="teachers.length === 0" class="text-gray-500">Keine Teacher gefunden.</p>
                <ul v-else class="divide-y divide-gray-100 rounded-lg border border-gray-200 bg-white">
                    <li v-for="t in teachers" :key="t.id" class="px-4 py-2">
                        <div class="font-medium text-gray-900">{{ t.name }}</div>
                        <div v-if="t.bio" class="line-clamp-1 text-xs text-gray-500">{{ t.bio }}</div>
                    </li>
                </ul>
            </div>

            <div class="rounded-lg border border-gray-200 bg-white p-4">
                <h3 class="mb-3 font-medium text-gray-900">Neuen Teacher anlegen</h3>
                <div v-if="form.recentlySuccessful" class="mb-3 rounded-md border border-green-200 bg-green-50 p-2 text-sm text-green-800">
                    Angelegt.
                </div>
                <form class="space-y-3" @submit.prevent="create">
                    <div>
                        <InputLabel for="t_name" value="Name" />
                        <TextInput id="t_name" v-model="form.name" class="mt-1 block w-full" required />
                        <InputError class="mt-1" :message="form.errors.name" />
                    </div>
                    <div>
                        <InputLabel for="t_bio" value="Bio (optional)" />
                        <textarea id="t_bio" v-model="form.bio" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"></textarea>
                    </div>
                    <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                        Anlegen
                    </PrimaryButton>
                </form>
            </div>
        </div>
    </OrganizerLayout>
</template>
