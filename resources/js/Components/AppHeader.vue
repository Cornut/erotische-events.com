<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import AuthModal from '@/Components/AuthModal.vue';
import { useAuthModalStore } from '@/stores/authModal';

const page = usePage();
const authModal = useAuthModalStore();

const navLinks = [
    { label: 'Events', href: '/', match: ['/', '/events'] },
    { label: 'Kalender', href: '/calendar', match: ['/calendar'] },
    { label: 'Veranstalter:innen', href: '/organizers', match: ['/organizers'] },
    { label: 'Facilitator', href: '/teachers', match: ['/teachers', '/teacher'] },
    { label: 'Kontakt', href: '/contact', match: ['/contact'] },
];

function isActive(matchers: string[]): boolean {
    const url = page.url.split('?')[0];
    return matchers.some((m) => (m === '/' ? url === '/' : url.startsWith(m)));
}
</script>

<template>
    <nav class="border-b border-gray-100 bg-white">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex h-16 items-center justify-between">
                <div class="flex items-center gap-6">
                    <!-- Logo -->
                    <Link href="/" class="flex shrink-0 items-center">
                        <img
                            src="/storage/logo.png"
                            alt="erotische-events.com"
                            class="block h-10 w-auto"
                        />
                    </Link>

                    <!-- Main menu -->
                    <div class="hidden items-center gap-1 md:flex">
                        <Link
                            v-for="link in navLinks"
                            :key="link.href"
                            :href="link.href"
                            class="rounded-md px-3 py-2 text-sm transition"
                            :class="isActive(link.match) ? 'font-semibold text-indigo-700' : 'text-gray-600 hover:text-gray-900'"
                        >
                            {{ link.label }}
                        </Link>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <!-- Logged in: name + menu symbol -->
                    <template v-if="page.props.auth.user">
                        <span class="hidden text-sm font-medium text-gray-700 sm:block">
                            {{ page.props.auth.user.name }}
                        </span>

                        <Dropdown align="right" width="48">
                            <template #trigger>
                                <button
                                    type="button"
                                    class="inline-flex items-center justify-center rounded-md p-2 text-gray-500 transition hover:bg-gray-100 hover:text-gray-700 focus:outline-none"
                                    aria-label="Menü"
                                >
                                    <svg
                                        class="h-6 w-6"
                                        stroke="currentColor"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M4 6h16M4 12h16M4 18h16"
                                        />
                                    </svg>
                                </button>
                            </template>

                            <template #content>
                                <div class="border-b border-gray-100 px-4 py-2 sm:hidden">
                                    <div class="text-sm font-medium text-gray-800">
                                        {{ page.props.auth.user.name }}
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        {{ page.props.auth.user.email }}
                                    </div>
                                </div>
                                <div class="border-b border-gray-100 md:hidden">
                                    <DropdownLink
                                        v-for="link in navLinks"
                                        :key="link.href"
                                        :href="link.href"
                                    >
                                        {{ link.label }}
                                    </DropdownLink>
                                </div>
                                <DropdownLink
                                    v-if="page.props.auth.user.role === 'organizer'"
                                    href="/organizer/dashboard"
                                >
                                    Veranstalter:innen-Bereich
                                </DropdownLink>
                                <DropdownLink href="/settings">Einstellungen</DropdownLink>
                                <DropdownLink :href="route('logout')" method="post" as="button">
                                    Abmelden
                                </DropdownLink>
                            </template>
                        </Dropdown>
                    </template>

                    <!-- Guest: Anmelden opens the popup -->
                    <button
                        v-else
                        type="button"
                        class="inline-flex items-center rounded-md border border-transparent bg-gray-800 px-4 py-2 text-sm font-semibold text-white transition hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2"
                        @click="authModal.open('login')"
                    >
                        Anmelden
                    </button>
                </div>
            </div>
        </div>

        <AuthModal
            :show="authModal.show"
            :initial-mode="authModal.mode"
        />
    </nav>
</template>
