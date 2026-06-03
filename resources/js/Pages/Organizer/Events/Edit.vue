<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

interface Event {
    id: number;
    title: string;
    booking_url: string;
    start_date: string | null;
    short_description: string | null;
}

const props = defineProps<{
    event: Event;
}>();

const form = useForm({
    title: props.event.title,
    booking_url: props.event.booking_url,
    start_date: props.event.start_date ?? '',
    short_description: props.event.short_description ?? '',
});

const submit = () => {
    form.put(route('organizer.events.update', { event: props.event.id }));
};
</script>

<template>
    <Head title="Edit Event" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Edit Event
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <form @submit.prevent="submit">
                            <div>
                                <InputLabel for="title" value="Title" />

                                <TextInput
                                    id="title"
                                    type="text"
                                    class="mt-1 block w-full"
                                    v-model="form.title"
                                    required
                                    autofocus
                                />

                                <InputError class="mt-2" :message="form.errors.title" />
                            </div>

                            <div class="mt-4">
                                <InputLabel for="booking_url" value="Booking URL" />

                                <TextInput
                                    id="booking_url"
                                    type="url"
                                    class="mt-1 block w-full"
                                    v-model="form.booking_url"
                                    required
                                />

                                <InputError class="mt-2" :message="form.errors.booking_url" />
                            </div>

                            <div class="mt-4">
                                <InputLabel for="start_date" value="Start Date" />

                                <TextInput
                                    id="start_date"
                                    type="datetime-local"
                                    class="mt-1 block w-full"
                                    v-model="form.start_date"
                                    required
                                />

                                <InputError class="mt-2" :message="form.errors.start_date" />
                            </div>

                            <div class="mt-4">
                                <InputLabel for="short_description" value="Short Description" />

                                <TextInput
                                    id="short_description"
                                    type="text"
                                    class="mt-1 block w-full"
                                    v-model="form.short_description"
                                />

                                <InputError class="mt-2" :message="form.errors.short_description" />
                            </div>

                            <div class="mt-4 flex items-center justify-end">
                                <PrimaryButton
                                    :class="{ 'opacity-25': form.processing }"
                                    :disabled="form.processing"
                                >
                                    Save Changes
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
