<script setup lang="ts">
import AppHeader from '@/Components/AppHeader.vue';
</script>

<template>
    <div class="min-h-screen bg-gray-100">
        <AppHeader />

        <!-- Optional page heading -->
        <header v-if="$slots.header" class="bg-white shadow">
            <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                <slot name="header" />
            </div>
        </header>

        <main>
            <slot />
        </main>
    </div>
</template>
