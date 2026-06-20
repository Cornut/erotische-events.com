<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import OrganizerLayout from '@/Layouts/OrganizerLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import VenueFormFields from './Form.vue';

const form = useForm({
    name: '',
    street: '',
    postal_code: '',
    city: '',
    region: '',
    country: '',
    description: '',
});

const submit = () => form.post('/organizer/venues');
</script>

<template>
    <Head title="Neue Venue" />

    <OrganizerLayout>
        <div class="mb-6 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900">Neue Venue</h2>
            <Link href="/organizer/venues" class="text-sm text-indigo-600 hover:underline">&larr; Zur Venue-Liste</Link>
        </div>

        <form @submit.prevent="submit">
            <VenueFormFields :form="form" />
            <div class="mt-6">
                <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    Venue anlegen
                </PrimaryButton>
            </div>
        </form>
    </OrganizerLayout>
</template>
