<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import AppHeader from '@/Components/AppHeader.vue';

const page = usePage();

const linkClass = (path: string) =>
    [
        'block rounded-md px-3 py-2 text-sm font-medium transition',
        page.url.startsWith(path)
            ? 'bg-indigo-50 text-indigo-700'
            : 'text-gray-700 hover:bg-gray-100',
    ].join(' ');
</script>

<template>
    <div class="min-h-screen bg-gray-100">
        <AppHeader />

        <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <h1 class="mb-6 text-xl font-semibold text-gray-800">Einstellungen</h1>

            <div class="grid gap-8 md:grid-cols-[12rem_minmax(0,1fr)]">
                <!-- Left vertical menu -->
                <aside>
                    <nav class="space-y-1">
                        <Link href="/settings/profile" :class="linkClass('/settings/profile')">
                            Profil
                        </Link>
                        <Link href="/settings/favorites" :class="linkClass('/settings/favorites')">
                            Favoriten
                        </Link>
                        <Link
                            :href="route('logout')"
                            method="post"
                            as="button"
                            class="block w-full rounded-md px-3 py-2 text-left text-sm font-medium text-gray-700 transition hover:bg-gray-100"
                        >
                            Abmelden
                        </Link>
                    </nav>
                </aside>

                <!-- Page content -->
                <main>
                    <slot />
                </main>
            </div>
        </div>
    </div>
</template>
