<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    company_name: '',
    contact_name: '',
    website: '',
});

const submit = () => {
    form.post('/organizer/register');
};
</script>

<template>
    <Head title="Become an Organizer" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Become an Organizer
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <form @submit.prevent="submit">
                            <div>
                                <InputLabel for="company_name" value="Company Name" />

                                <TextInput
                                    id="company_name"
                                    type="text"
                                    class="mt-1 block w-full"
                                    v-model="form.company_name"
                                    required
                                    autofocus
                                />

                                <InputError class="mt-2" :message="form.errors.company_name" />
                            </div>

                            <div class="mt-4">
                                <InputLabel for="contact_name" value="Contact Name" />

                                <TextInput
                                    id="contact_name"
                                    type="text"
                                    class="mt-1 block w-full"
                                    v-model="form.contact_name"
                                />

                                <InputError class="mt-2" :message="form.errors.contact_name" />
                            </div>

                            <div class="mt-4">
                                <InputLabel for="website" value="Website" />

                                <TextInput
                                    id="website"
                                    type="url"
                                    class="mt-1 block w-full"
                                    v-model="form.website"
                                />

                                <InputError class="mt-2" :message="form.errors.website" />
                            </div>

                            <div class="mt-4 flex items-center justify-end">
                                <PrimaryButton
                                    :class="{ 'opacity-25': form.processing }"
                                    :disabled="form.processing"
                                >
                                    Register as Organizer
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
