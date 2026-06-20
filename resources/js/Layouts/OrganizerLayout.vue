<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import AppHeader from '@/Components/AppHeader.vue';

const page = usePage();

const items = [
    { label: 'Übersicht', href: '/organizer/dashboard' },
    { label: 'Stammdaten', href: '/organizer/profile' },
    { label: 'Events', href: '/organizer/events' },
    { label: 'Venues', href: '/organizer/venues' },
    { label: 'Teacher', href: '/organizer/teachers' },
];

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
            <h1 class="mb-6 text-xl font-semibold text-gray-800">Veranstalter:innen-Bereich</h1>

            <div class="grid gap-8 md:grid-cols-[14rem_minmax(0,1fr)]">
                <aside>
                    <nav class="space-y-1">
                        <Link
                            v-for="item in items"
                            :key="item.href"
                            :href="item.href"
                            :class="linkClass(item.href)"
                        >
                            {{ item.label }}
                        </Link>
                    </nav>
                </aside>

                <main>
                    <slot />
                </main>
            </div>
        </div>
    </div>
</template>
