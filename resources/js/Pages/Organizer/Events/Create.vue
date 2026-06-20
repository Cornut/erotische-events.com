<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import OrganizerLayout from '@/Layouts/OrganizerLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import EventFormFields from './Form.vue';

defineProps<{
    options: {
        venues: { id: number; name: string }[];
        categories: { slug: string; name_de: string }[];
        teachers: { id: number; name: string }[];
    };
}>();

const form = useForm({
    title: '',
    short_description: '',
    long_description: '',
    start_date: '',
    end_date: '',
    booking_url: '',
    venue_id: null as number | null,
    categories: [] as string[],
    teachers: [] as number[],
    price_amount: '',
    price_currency: 'EUR',
});

const submit = () => form.post('/organizer/events');
</script>

<template>
    <Head title="Neues Event" />

    <OrganizerLayout>
        <div class="mb-6 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900">Neues Event</h2>
            <Link href="/organizer/events" class="text-sm text-indigo-600 hover:underline">&larr; Zur Event-Liste</Link>
        </div>

        <form @submit.prevent="submit">
            <EventFormFields :form="form" :options="options" />
            <div class="mt-6">
                <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    Event anlegen (Entwurf)
                </PrimaryButton>
            </div>
        </form>
    </OrganizerLayout>
</template>
