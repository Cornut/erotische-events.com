<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import OrganizerLayout from '@/Layouts/OrganizerLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import EventFormFields from './Form.vue';

interface EventData {
    id: number;
    title: string;
    short_description: string | null;
    long_description: string | null;
    start_date: string | null;
    end_date: string | null;
    booking_url: string;
    venue_id: number | null;
    status: string;
    categories: string[];
    teachers: number[];
    price_amount: string | number | null;
    price_currency: string | null;
}

const props = defineProps<{
    event: EventData;
    options: {
        venues: { id: number; name: string }[];
        categories: { slug: string; name_de: string }[];
        teachers: { id: number; name: string }[];
    };
}>();

const form = useForm({
    title: props.event.title ?? '',
    short_description: props.event.short_description ?? '',
    long_description: props.event.long_description ?? '',
    start_date: props.event.start_date ?? '',
    end_date: props.event.end_date ?? '',
    booking_url: props.event.booking_url ?? '',
    venue_id: props.event.venue_id,
    categories: [...(props.event.categories ?? [])],
    teachers: [...(props.event.teachers ?? [])],
    price_amount: props.event.price_amount != null ? String(props.event.price_amount) : '',
    price_currency: props.event.price_currency ?? 'EUR',
});

const submit = () => form.put(`/organizer/events/${props.event.id}`);
</script>

<template>
    <Head title="Event bearbeiten" />

    <OrganizerLayout>
        <div class="mb-6 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900">Event bearbeiten</h2>
            <Link href="/organizer/events" class="text-sm text-indigo-600 hover:underline">&larr; Zur Event-Liste</Link>
        </div>

        <form @submit.prevent="submit">
            <EventFormFields :form="form" :options="options" />
            <div class="mt-6">
                <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    Änderungen speichern
                </PrimaryButton>
            </div>
        </form>
    </OrganizerLayout>
</template>
