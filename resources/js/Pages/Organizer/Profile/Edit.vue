<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import OrganizerLayout from '@/Layouts/OrganizerLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

interface Organizer {
    company_name: string;
    legal_name: string | null;
    contact_name: string | null;
    email: string | null;
    phone: string | null;
    website: string | null;
    description: string | null;
    street: string | null;
    postal_code: string | null;
    city: string | null;
    country: string | null;
    vat_id: string | null;
}

const props = defineProps<{ organizer: Organizer }>();

const form = useForm({
    company_name: props.organizer.company_name ?? '',
    legal_name: props.organizer.legal_name ?? '',
    contact_name: props.organizer.contact_name ?? '',
    email: props.organizer.email ?? '',
    phone: props.organizer.phone ?? '',
    website: props.organizer.website ?? '',
    description: props.organizer.description ?? '',
    street: props.organizer.street ?? '',
    postal_code: props.organizer.postal_code ?? '',
    city: props.organizer.city ?? '',
    country: props.organizer.country ?? '',
    vat_id: props.organizer.vat_id ?? '',
});

const submit = () => form.put('/organizer/profile', { preserveScroll: true });
</script>

<template>
    <Head title="Stammdaten" />

    <OrganizerLayout>
        <h2 class="mb-6 text-lg font-semibold text-gray-900">Stammdaten</h2>

        <div v-if="form.recentlySuccessful" class="mb-4 rounded-md border border-green-200 bg-green-50 p-3 text-sm text-green-800">
            Gespeichert.
        </div>

        <form class="max-w-2xl space-y-4" @submit.prevent="submit">
            <div>
                <InputLabel for="company_name" value="Name" />
                <TextInput id="company_name" v-model="form.company_name" class="mt-1 block w-full" required />
                <InputError class="mt-1" :message="form.errors.company_name" />
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <InputLabel for="legal_name" value="Rechtlicher Name" />
                    <TextInput id="legal_name" v-model="form.legal_name" class="mt-1 block w-full" />
                </div>
                <div>
                    <InputLabel for="contact_name" value="Ansprechperson" />
                    <TextInput id="contact_name" v-model="form.contact_name" class="mt-1 block w-full" />
                </div>
                <div>
                    <InputLabel for="email" value="E-Mail" />
                    <TextInput id="email" type="email" v-model="form.email" class="mt-1 block w-full" />
                    <InputError class="mt-1" :message="form.errors.email" />
                </div>
                <div>
                    <InputLabel for="phone" value="Telefon" />
                    <TextInput id="phone" v-model="form.phone" class="mt-1 block w-full" />
                </div>
                <div class="sm:col-span-2">
                    <InputLabel for="website" value="Website" />
                    <TextInput id="website" type="url" v-model="form.website" class="mt-1 block w-full" placeholder="https://…" />
                    <InputError class="mt-1" :message="form.errors.website" />
                </div>
            </div>

            <div>
                <InputLabel for="description" value="Beschreibung" />
                <textarea id="description" v-model="form.description" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"></textarea>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <InputLabel for="street" value="Straße" />
                    <TextInput id="street" v-model="form.street" class="mt-1 block w-full" />
                </div>
                <div>
                    <InputLabel for="postal_code" value="PLZ" />
                    <TextInput id="postal_code" v-model="form.postal_code" class="mt-1 block w-full" />
                </div>
                <div>
                    <InputLabel for="city" value="Stadt" />
                    <TextInput id="city" v-model="form.city" class="mt-1 block w-full" />
                </div>
                <div>
                    <InputLabel for="country" value="Land (ISO, z. B. DE)" />
                    <TextInput id="country" v-model="form.country" class="mt-1 block w-full" maxlength="2" />
                    <InputError class="mt-1" :message="form.errors.country" />
                </div>
                <div>
                    <InputLabel for="vat_id" value="USt-IdNr." />
                    <TextInput id="vat_id" v-model="form.vat_id" class="mt-1 block w-full" />
                </div>
            </div>

            <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                Speichern
            </PrimaryButton>
        </form>
    </OrganizerLayout>
</template>
