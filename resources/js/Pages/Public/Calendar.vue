<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

interface CalEvent {
    id: number;
    slug: string;
    title: string;
    date: string; // YYYY-MM-DD
    time: string; // HH:MM
}

const props = defineProps<{
    month: string; // YYYY-MM
    monthLabel: string;
    prevMonth: string;
    nextMonth: string;
    events: CalEvent[];
}>();

const weekdays = ['Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So'];

const today = (() => {
    const d = new Date();
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
})();

const eventsByDate = computed(() => {
    const map: Record<string, CalEvent[]> = {};
    for (const event of props.events) {
        (map[event.date] ??= []).push(event);
    }
    return map;
});

const weeks = computed(() => {
    const [year, month] = props.month.split('-').map(Number);
    const first = new Date(year, month - 1, 1);
    const startOffset = (first.getDay() + 6) % 7; // Monday = 0
    const daysInMonth = new Date(year, month, 0).getDate();
    const numWeeks = Math.ceil((startOffset + daysInMonth) / 7);

    const cursor = new Date(year, month - 1, 1 - startOffset);
    const result: { date: string; day: number; inMonth: boolean; events: CalEvent[] }[][] = [];

    for (let w = 0; w < numWeeks; w++) {
        const days = [];
        for (let d = 0; d < 7; d++) {
            const iso = `${cursor.getFullYear()}-${String(cursor.getMonth() + 1).padStart(2, '0')}-${String(cursor.getDate()).padStart(2, '0')}`;
            days.push({
                date: iso,
                day: cursor.getDate(),
                inMonth: cursor.getMonth() === month - 1,
                events: eventsByDate.value[iso] ?? [],
            });
            cursor.setDate(cursor.getDate() + 1);
        }
        result.push(days);
    }
    return result;
});

function selectMonth(event: Event): void {
    const value = (event.target as HTMLInputElement).value;
    if (value) {
        router.get('/calendar', { month: value }, { preserveScroll: true });
    }
}
</script>

<template>
    <Head title="Kalender" />

    <AppLayout>
        <div class="mx-auto max-w-6xl p-6">
            <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
                <h1 class="text-2xl font-bold capitalize">{{ monthLabel }}</h1>

                <div class="flex items-center gap-2">
                    <Link
                        :href="`/calendar?month=${prevMonth}`"
                        preserve-scroll
                        class="rounded-md border border-gray-300 px-3 py-2 text-sm hover:bg-gray-50"
                        aria-label="Vorheriger Monat"
                    >‹</Link>
                    <input
                        type="month"
                        :value="month"
                        class="rounded-md border-gray-300 text-sm"
                        @change="selectMonth"
                    />
                    <Link
                        :href="`/calendar?month=${nextMonth}`"
                        preserve-scroll
                        class="rounded-md border border-gray-300 px-3 py-2 text-sm hover:bg-gray-50"
                        aria-label="Nächster Monat"
                    >›</Link>
                </div>
            </div>

            <!-- Weekday header -->
            <div class="grid grid-cols-7 gap-px border-b border-gray-200 text-center text-xs font-semibold text-gray-500">
                <div v-for="d in weekdays" :key="d" class="py-2">{{ d }}</div>
            </div>

            <!-- Month grid -->
            <div class="grid grid-cols-7 gap-px overflow-hidden rounded-b-lg bg-gray-200">
                <template v-for="(week, wi) in weeks" :key="wi">
                    <div
                        v-for="cell in week"
                        :key="cell.date"
                        class="min-h-[7rem] bg-white p-1.5 align-top"
                        :class="cell.inMonth ? '' : 'bg-gray-50 text-gray-400'"
                    >
                        <div
                            class="mb-1 text-right text-xs"
                            :class="cell.date === today ? 'font-bold text-indigo-600' : 'text-gray-500'"
                        >
                            {{ cell.day }}
                        </div>
                        <ul class="space-y-1">
                            <li v-for="event in cell.events" :key="event.id">
                                <Link
                                    :href="`/events/${event.slug}?from=calendar&month=${month}`"
                                    class="block truncate rounded bg-indigo-50 px-1.5 py-0.5 text-xs text-indigo-700 hover:bg-indigo-100"
                                    :title="`${event.time} ${event.title}`"
                                >
                                    <span class="text-indigo-400">{{ event.time }}</span> {{ event.title }}
                                </Link>
                            </li>
                        </ul>
                    </div>
                </template>
            </div>

            <p v-if="events.length === 0" class="mt-4 text-center text-sm text-gray-500">
                Keine Events in diesem Monat.
            </p>
        </div>
    </AppLayout>
</template>
