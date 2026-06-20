<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import OrganizerLayout from '@/Layouts/OrganizerLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import VenueFormFields from './Form.vue';

interface VenueData {
    id: number;
    name: string;
    street: string | null;
    postal_code: string | null;
    city: string | null;
    region: string | null;
    country: string | null;
    description: string | null;
    latitude: string | number | null;
    longitude: string | number | null;
}

const props = defineProps<{ venue: VenueData }>();

const form = useForm({
    name: props.venue.name ?? '',
    street: props.venue.street ?? '',
    postal_code: props.venue.postal_code ?? '',
    city: props.venue.city ?? '',
    region: props.venue.region ?? '',
    country: props.venue.country ?? '',
    description: props.venue.description ?? '',
});

const submit = () => form.put(`/organizer/venues/${props.venue.id}`);
</script>

<template>
    <Head title="Venue bearbeiten" />

    <OrganizerLayout>
        <div class="mb-6 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900">Venue bearbeiten</h2>
            <Link href="/organizer/venues" class="text-sm text-indigo-600 hover:underline">&larr; Zur Venue-Liste</Link>
        </div>

        <form @submit.prevent="submit">
            <VenueFormFields :form="form" />
            <p v-if="venue.latitude && venue.longitude" class="mt-3 text-xs text-gray-500">
                Koordinaten: {{ venue.latitude }}, {{ venue.longitude }}
            </p>
            <div class="mt-6">
                <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    Änderungen speichern
                </PrimaryButton>
            </div>
        </form>
    </OrganizerLayout>
</template>
