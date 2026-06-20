<script setup lang="ts">
import type { InertiaForm } from '@inertiajs/vue3';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';

interface VenueFormData {
    name: string;
    street: string;
    postal_code: string;
    city: string;
    region: string;
    country: string;
    description: string;
}

defineProps<{ form: InertiaForm<VenueFormData> }>();
</script>

<template>
    <div class="max-w-2xl space-y-4">
        <div>
            <InputLabel for="name" value="Name" />
            <TextInput id="name" v-model="form.name" class="mt-1 block w-full" required />
            <InputError class="mt-1" :message="form.errors.name" />
        </div>

        <div>
            <InputLabel for="street" value="Straße" />
            <TextInput id="street" v-model="form.street" class="mt-1 block w-full" />
        </div>

        <div class="grid gap-4 sm:grid-cols-3">
            <div>
                <InputLabel for="postal_code" value="PLZ" />
                <TextInput id="postal_code" v-model="form.postal_code" class="mt-1 block w-full" />
            </div>
            <div>
                <InputLabel for="city" value="Stadt" />
                <TextInput id="city" v-model="form.city" class="mt-1 block w-full" />
            </div>
            <div>
                <InputLabel for="country" value="Land (ISO)" />
                <TextInput id="country" v-model="form.country" class="mt-1 block w-full" maxlength="2" />
                <InputError class="mt-1" :message="form.errors.country" />
            </div>
        </div>

        <div>
            <InputLabel for="region" value="Region (optional)" />
            <TextInput id="region" v-model="form.region" class="mt-1 block w-full" />
        </div>

        <div>
            <InputLabel for="description" value="Beschreibung (optional)" />
            <textarea id="description" v-model="form.description" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"></textarea>
        </div>

        <p class="text-xs text-gray-500">Die GPS-Koordinaten werden beim Speichern automatisch aus der Adresse ermittelt.</p>
    </div>
</template>
