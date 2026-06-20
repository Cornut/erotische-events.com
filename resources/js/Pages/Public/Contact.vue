<script setup lang="ts">
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const sent = ref(false);

const form = useForm({
    name: '',
    email: '',
    message: '',
});

const submit = () => {
    form.post('/contact', {
        preserveScroll: true,
        onSuccess: () => {
            sent.value = true;
            form.reset();
        },
    });
};
</script>

<template>
    <Head title="Kontakt" />

    <AppLayout>
        <div class="mx-auto max-w-xl p-6">
            <h1 class="text-2xl font-bold">Kontakt</h1>
            <p class="mb-6 mt-1 text-gray-600">Schreib uns eine Nachricht.</p>

            <div
                v-if="sent"
                class="mb-6 rounded-md border border-green-200 bg-green-50 p-4 text-sm text-green-800"
            >
                Vielen Dank! Deine Nachricht wurde gesendet.
            </div>

            <form class="space-y-4" @submit.prevent="submit">
                <div>
                    <InputLabel for="name" value="Name" />
                    <TextInput id="name" v-model="form.name" type="text" class="mt-1 block w-full" required autofocus />
                    <InputError class="mt-2" :message="form.errors.name" />
                </div>

                <div>
                    <InputLabel for="email" value="E-Mail" />
                    <TextInput id="email" v-model="form.email" type="email" class="mt-1 block w-full" required />
                    <InputError class="mt-2" :message="form.errors.email" />
                </div>

                <div>
                    <InputLabel for="message" value="Nachricht" />
                    <textarea
                        id="message"
                        v-model="form.message"
                        rows="6"
                        required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    ></textarea>
                    <InputError class="mt-2" :message="form.errors.message" />
                </div>

                <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    Nachricht senden
                </PrimaryButton>
            </form>
        </div>
    </AppLayout>
</template>
