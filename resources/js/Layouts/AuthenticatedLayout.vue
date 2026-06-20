<script setup lang="ts">
import AppHeader from '@/Components/AppHeader.vue';
</script>

<template>
    <div>
        <div class="min-h-screen bg-gray-100">
            <AppHeader />

            <!-- Page Heading -->
            <header class="bg-white shadow" v-if="$slots.header">
                <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </header>

            <!-- Page Content -->
            <main>
                <slot />
            </main>
        </div>
    </div>
</template>
