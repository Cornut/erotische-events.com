<script setup lang="ts">
import { ref, watch } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Checkbox from '@/Components/Checkbox.vue';
import { useAuthModalStore } from '@/stores/authModal';

const props = withDefaults(
    defineProps<{
        show?: boolean;
        initialMode?: 'login' | 'register';
    }>(),
    {
        show: false,
        initialMode: 'login',
    },
);

const authModal = useAuthModalStore();

const mode = ref<'login' | 'register'>(props.initialMode);

// Reset to the requested mode each time the popup is opened.
watch(
    () => props.show,
    (open) => {
        if (open) {
            mode.value = props.initialMode;
        }
    },
);

const loginForm = useForm({
    email: '',
    password: '',
    remember: false,
});

const registerForm = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

// After a successful login/registration: close the popup and, if the user had
// clicked a heart while logged out, favorite that event automatically.
const afterAuth = () => {
    const eventId = authModal.consumePendingFavorite();
    authModal.close();
    if (eventId !== null) {
        router.post(
            `/events/${eventId}/favorite`,
            {},
            { preserveScroll: true, preserveState: true },
        );
    }
};

const submitLogin = () => {
    loginForm.post(route('login'), {
        onSuccess: afterAuth,
        onFinish: () => loginForm.reset('password'),
    });
};

const submitRegister = () => {
    registerForm.post(route('register'), {
        onSuccess: afterAuth,
        onFinish: () => registerForm.reset('password', 'password_confirmation'),
    });
};

// Manual close (backdrop/escape/switch away): drop any pending favorite intent.
const close = () => {
    authModal.consumePendingFavorite();
    authModal.close();
};
</script>

<template>
    <Modal :show="show" max-width="md" @close="close">
        <div class="p-6">
            <!-- Login / Registrieren switch -->
            <div class="mb-6 flex rounded-lg bg-gray-100 p-1 text-sm font-medium">
                <button
                    type="button"
                    class="flex-1 rounded-md px-3 py-2 transition"
                    :class="
                        mode === 'login'
                            ? 'bg-white text-gray-900 shadow'
                            : 'text-gray-500 hover:text-gray-700'
                    "
                    @click="mode = 'login'"
                >
                    Anmelden
                </button>
                <button
                    type="button"
                    class="flex-1 rounded-md px-3 py-2 transition"
                    :class="
                        mode === 'register'
                            ? 'bg-white text-gray-900 shadow'
                            : 'text-gray-500 hover:text-gray-700'
                    "
                    @click="mode = 'register'"
                >
                    Registrieren
                </button>
            </div>

            <!-- Login -->
            <form v-if="mode === 'login'" @submit.prevent="submitLogin">
                <h2 class="mb-4 text-lg font-semibold text-gray-900">Anmelden</h2>

                <div>
                    <InputLabel for="login-email" value="E-Mail" />
                    <TextInput
                        id="login-email"
                        type="email"
                        class="mt-1 block w-full"
                        v-model="loginForm.email"
                        required
                        autofocus
                        autocomplete="username"
                    />
                    <InputError class="mt-2" :message="loginForm.errors.email" />
                </div>

                <div class="mt-4">
                    <InputLabel for="login-password" value="Passwort" />
                    <TextInput
                        id="login-password"
                        type="password"
                        class="mt-1 block w-full"
                        v-model="loginForm.password"
                        required
                        autocomplete="current-password"
                    />
                    <InputError class="mt-2" :message="loginForm.errors.password" />
                </div>

                <div class="mt-4 flex items-center justify-between">
                    <label class="flex items-center">
                        <Checkbox name="remember" v-model:checked="loginForm.remember" />
                        <span class="ms-2 text-sm text-gray-600">Angemeldet bleiben</span>
                    </label>

                    <Link
                        :href="route('password.request')"
                        class="text-sm text-gray-600 underline hover:text-gray-900"
                        @click="close"
                    >
                        Passwort vergessen?
                    </Link>
                </div>

                <div class="mt-6 flex items-center justify-end">
                    <PrimaryButton
                        :class="{ 'opacity-25': loginForm.processing }"
                        :disabled="loginForm.processing"
                    >
                        Anmelden
                    </PrimaryButton>
                </div>
            </form>

            <!-- Registrieren -->
            <form v-else @submit.prevent="submitRegister">
                <h2 class="mb-4 text-lg font-semibold text-gray-900">Konto erstellen</h2>

                <div>
                    <InputLabel for="register-name" value="Name" />
                    <TextInput
                        id="register-name"
                        type="text"
                        class="mt-1 block w-full"
                        v-model="registerForm.name"
                        required
                        autofocus
                        autocomplete="name"
                    />
                    <InputError class="mt-2" :message="registerForm.errors.name" />
                </div>

                <div class="mt-4">
                    <InputLabel for="register-email" value="E-Mail" />
                    <TextInput
                        id="register-email"
                        type="email"
                        class="mt-1 block w-full"
                        v-model="registerForm.email"
                        required
                        autocomplete="username"
                    />
                    <InputError class="mt-2" :message="registerForm.errors.email" />
                </div>

                <div class="mt-4">
                    <InputLabel for="register-password" value="Passwort" />
                    <TextInput
                        id="register-password"
                        type="password"
                        class="mt-1 block w-full"
                        v-model="registerForm.password"
                        required
                        autocomplete="new-password"
                    />
                    <InputError class="mt-2" :message="registerForm.errors.password" />
                </div>

                <div class="mt-4">
                    <InputLabel for="register-password-confirm" value="Passwort bestätigen" />
                    <TextInput
                        id="register-password-confirm"
                        type="password"
                        class="mt-1 block w-full"
                        v-model="registerForm.password_confirmation"
                        required
                        autocomplete="new-password"
                    />
                    <InputError class="mt-2" :message="registerForm.errors.password_confirmation" />
                </div>

                <div class="mt-6 flex items-center justify-end">
                    <PrimaryButton
                        :class="{ 'opacity-25': registerForm.processing }"
                        :disabled="registerForm.processing"
                    >
                        Registrieren
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </Modal>
</template>
