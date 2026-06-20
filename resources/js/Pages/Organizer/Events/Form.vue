<script setup lang="ts">
import type { InertiaForm } from '@inertiajs/vue3';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';

interface EventFormData {
    title: string;
    short_description: string;
    long_description: string;
    start_date: string;
    end_date: string;
    booking_url: string;
    venue_id: number | null;
    categories: string[];
    teachers: number[];
    price_amount: string;
    price_currency: string;
}

defineProps<{
    form: InertiaForm<EventFormData>;
    options: {
        venues: { id: number; name: string }[];
        categories: { slug: string; name_de: string }[];
        teachers: { id: number; name: string }[];
    };
}>();
</script>

<template>
    <div class="max-w-2xl space-y-4">
        <div>
            <InputLabel for="title" value="Titel" />
            <TextInput id="title" v-model="form.title" class="mt-1 block w-full" required />
            <InputError class="mt-1" :message="form.errors.title" />
        </div>

        <div>
            <InputLabel for="short_description" value="Kurzbeschreibung" />
            <TextInput id="short_description" v-model="form.short_description" class="mt-1 block w-full" />
            <InputError class="mt-1" :message="form.errors.short_description" />
        </div>

        <div>
            <InputLabel for="long_description" value="Beschreibung" />
            <textarea id="long_description" v-model="form.long_description" rows="5" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"></textarea>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <InputLabel for="start_date" value="Beginn" />
                <input id="start_date" v-model="form.start_date" type="datetime-local" class="mt-1 block w-full rounded-md border-gray-300" required />
                <InputError class="mt-1" :message="form.errors.start_date" />
            </div>
            <div>
                <InputLabel for="end_date" value="Ende (optional)" />
                <input id="end_date" v-model="form.end_date" type="datetime-local" class="mt-1 block w-full rounded-md border-gray-300" />
                <InputError class="mt-1" :message="form.errors.end_date" />
            </div>
        </div>

        <div>
            <InputLabel for="booking_url" value="Link zur Veranstaltung (Webseite)" />
            <TextInput id="booking_url" type="url" v-model="form.booking_url" class="mt-1 block w-full" placeholder="https://…" required />
            <InputError class="mt-1" :message="form.errors.booking_url" />
        </div>

        <div>
            <InputLabel for="venue_id" value="Venue" />
            <select id="venue_id" v-model="form.venue_id" class="mt-1 block w-full rounded-md border-gray-300">
                <option :value="null">— keine —</option>
                <option v-for="v in options.venues" :key="v.id" :value="v.id">{{ v.name }}</option>
            </select>
            <p class="mt-1 text-xs text-gray-500">Eigene Venues unter „Venues" anlegen.</p>
        </div>

        <div>
            <InputLabel value="Kategorien" />
            <div class="mt-1 flex flex-wrap gap-3">
                <label v-for="c in options.categories" :key="c.slug" class="flex items-center gap-1 text-sm">
                    <input type="checkbox" :value="c.slug" v-model="form.categories" class="rounded border-gray-300" />
                    {{ c.name_de }}
                </label>
            </div>
        </div>

        <div>
            <InputLabel for="teachers" value="Teacher (Mehrfachauswahl)" />
            <select id="teachers" v-model="form.teachers" multiple size="6" class="mt-1 block w-full rounded-md border-gray-300">
                <option v-for="t in options.teachers" :key="t.id" :value="t.id">{{ t.name }}</option>
            </select>
            <p class="mt-1 text-xs text-gray-500">Neue Teacher im Bereich „Teacher" anlegen.</p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <InputLabel for="price_amount" value="Preis (optional)" />
                <TextInput id="price_amount" type="number" min="0" step="0.01" v-model="form.price_amount" class="mt-1 block w-full" />
            </div>
            <div>
                <InputLabel for="price_currency" value="Währung" />
                <TextInput id="price_currency" v-model="form.price_currency" class="mt-1 block w-full" maxlength="3" />
            </div>
        </div>
    </div>
</template>
