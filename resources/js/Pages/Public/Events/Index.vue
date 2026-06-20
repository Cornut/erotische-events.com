<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import FavoriteHeart from '@/Components/FavoriteHeart.vue';
import CityAutocomplete from '@/Components/CityAutocomplete.vue';

interface EventItem {
    id: number;
    title: string;
    slug: string;
    short_description: string | null;
    start_date: string;
    is_favorited?: boolean;
    organizer?: { company_name: string } | null;
    venue?: { city: string | null } | null;
}

interface Paginator {
    data: EventItem[];
    current_page: number;
    last_page: number;
    total: number;
}

interface CategoryOption {
    slug: string;
    name_de: string;
}

interface CountryOption {
    code: string;
    name: string;
}

const props = defineProps<{
    events: Paginator;
    filters: {
        q: string;
        category: string;
        teacher: string;
        date_from: string;
        date_to: string;
        price_min: string;
        price_max: string;
        countries: string[];
        city: string;
        radius_km: string;
        lat: string;
        lng: string;
    };
    categories: CategoryOption[];
    countryOptions: CountryOption[];
}>();

const form = reactive({
    q: props.filters.q ?? '',
    category: props.filters.category ?? '',
    teacher: props.filters.teacher ?? '',
    date_from: props.filters.date_from ?? '',
    date_to: props.filters.date_to ?? '',
    price_min: props.filters.price_min ?? '',
    price_max: props.filters.price_max ?? '',
    countries: [...(props.filters.countries ?? [])] as string[],
    city: props.filters.city ?? '',
    radius_km: props.filters.radius_km ?? '',
    lat: props.filters.lat ?? '',
    lng: props.filters.lng ?? '',
});

// Filter panel is open by default.
const showFilters = ref(true);

type FilterValue = string | number | string[];

// Build the request payload from the active filters, omitting empty values.
function filterData(extra: Record<string, FilterValue> = {}): Record<string, FilterValue> {
    const data: Record<string, FilterValue> = { ...extra };
    if (form.q) data.q = form.q;
    if (form.category) data.category = form.category;
    if (form.teacher) data.teacher = form.teacher;
    if (form.date_from) data.date_from = form.date_from;
    if (form.date_to) data.date_to = form.date_to;
    if (form.price_min) data.price_min = form.price_min;
    if (form.price_max) data.price_max = form.price_max;
    if (form.countries.length) data.countries = form.countries;
    // Radius search only kicks in once a city has been resolved to coordinates.
    // The city label travels as `near` (display/rehydration only) — it must NOT
    // be sent as `city`, which the search treats as an exact venue-city match.
    if (form.lat && form.lng && form.radius_km) {
        data.lat = form.lat;
        data.lng = form.lng;
        data.radius_km = form.radius_km;
        data.near = form.city;
    }
    return data;
}

// Carry the currently applied filters (the URL query) onto the event link, so the
// detail page can offer a back-link that restores them (handled server-side via
// `from=events`). Uses the applied URL, not the live form, so it reflects the
// filters that actually produced this list.
function eventHref(slug: string): string {
    const search = typeof window !== 'undefined' ? window.location.search.replace(/^\?/, '') : '';
    return `/events/${slug}?${search ? search + '&' : ''}from=events`;
}

function formatDate(date: string): string {
    return new Date(date).toLocaleDateString('de-DE', {
        weekday: 'short',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
}

function addCountry(code: string): void {
    if (code && ! form.countries.includes(code)) {
        form.countries.push(code);
    }
}

function removeCountry(code: string): void {
    form.countries = form.countries.filter((c) => c !== code);
}

function countryName(code: string): string {
    return props.countryOptions.find((o) => o.code === code)?.name ?? code;
}

// City suggestions are limited to the selected countries, or — if none are
// selected — to the countries the catalog actually offers.
const allowedCityCountries = computed(() =>
    form.countries.length ? form.countries : props.countryOptions.map((o) => o.code),
);

function onCityTyping(): void {
    // Editing the text invalidates any previously resolved coordinates.
    form.lat = '';
    form.lng = '';
}

function onCitySelect(s: { label: string; lat: number; lng: number }): void {
    form.city = s.label;
    form.lat = String(s.lat);
    form.lng = String(s.lng);
    if (! form.radius_km) {
        form.radius_km = '50';
    }
}

function resetFilters(): void {
    form.category = '';
    form.teacher = '';
    form.date_from = '';
    form.date_to = '';
    form.price_min = '';
    form.price_max = '';
    form.countries = [];
    form.city = '';
    form.radius_km = '';
    form.lat = '';
    form.lng = '';
    submit();
}

// Accumulated list shown in the grid (grows as more pages load in).
const items = ref<EventItem[]>([...props.events.data]);
const page = ref(props.events.current_page);
const lastPage = ref(props.events.last_page);
const loading = ref(false);
const sentinel = ref<HTMLElement | null>(null);
let observer: IntersectionObserver | null = null;

function hasMore(): boolean {
    return page.value < lastPage.value;
}

function loadMore(): void {
    if (loading.value || ! hasMore()) {
        return;
    }
    loading.value = true;

    router.reload({
        only: ['events'],
        data: filterData({ page: page.value + 1 }),
        // Load the next page without writing ?page= into the address bar.
        preserveUrl: true,
        onSuccess: () => {
            items.value.push(...props.events.data);
            page.value = props.events.current_page;
            lastPage.value = props.events.last_page;
        },
        onFinish: () => {
            loading.value = false;
        },
    });
}

function submit(): void {
    router.get(window.location.pathname, filterData(), {
        preserveState: true,
        replace: true,
        onSuccess: () => {
            items.value = [...props.events.data];
            page.value = props.events.current_page;
            lastPage.value = props.events.last_page;
        },
    });
}

onMounted(() => {
    observer = new IntersectionObserver(
        (entries) => {
            if (entries[0]?.isIntersecting) {
                loadMore();
            }
        },
        { rootMargin: '300px' },
    );
    if (sentinel.value) {
        observer.observe(sentinel.value);
    }
});

onBeforeUnmount(() => observer?.disconnect());
</script>

<template>
    <Head title="Events" />

    <AppLayout>
    <div class="mx-auto max-w-5xl p-6">
        <h1 class="text-2xl font-bold">Events</h1>
        <p class="mb-6 mt-1 text-gray-600">Finde deine erotische Veranstaltung</p>

        <form class="mb-6" @submit.prevent="submit">
            <div class="flex flex-wrap gap-2">
                <input
                    v-model="form.q"
                    type="search"
                    placeholder="Suche…"
                    class="flex-1 rounded-md border-gray-300"
                />
                <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 font-semibold text-white hover:bg-indigo-700">
                    Suchen
                </button>
                <button
                    type="button"
                    class="inline-flex items-center gap-1 rounded-md border border-gray-300 px-4 py-2 font-medium text-gray-700 hover:bg-gray-50"
                    :aria-expanded="showFilters"
                    @click="showFilters = ! showFilters"
                >
                    Filter
                    <svg class="h-4 w-4 transition" :class="{ 'rotate-180': showFilters }" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
            </div>

            <!-- Collapsible filter panel -->
            <div
                v-show="showFilters"
                class="mt-4 grid gap-4 rounded-lg border border-gray-200 bg-gray-50 p-4 sm:grid-cols-2"
            >
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Datumsbereich</label>
                    <div class="flex items-center gap-2">
                        <input v-model="form.date_from" type="date" class="w-full rounded-md border-gray-300 text-sm" />
                        <span class="text-gray-400">–</span>
                        <input v-model="form.date_to" type="date" class="w-full rounded-md border-gray-300 text-sm" />
                    </div>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Hauptkategorie</label>
                    <select v-model="form.category" class="w-full rounded-md border-gray-300 text-sm">
                        <option value="">Alle Kategorien</option>
                        <option v-for="category in categories" :key="category.slug" :value="category.slug">
                            {{ category.name_de }}
                        </option>
                    </select>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Preisbereich (€)</label>
                    <div class="flex items-center gap-2">
                        <input v-model="form.price_min" type="number" min="0" placeholder="von" class="w-full rounded-md border-gray-300 text-sm" />
                        <span class="text-gray-400">–</span>
                        <input v-model="form.price_max" type="number" min="0" placeholder="bis" class="w-full rounded-md border-gray-300 text-sm" />
                    </div>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Facilitator suchen</label>
                    <input v-model="form.teacher" type="text" placeholder="Name der:des Facilitator:in…" class="w-full rounded-md border-gray-300 text-sm" />
                </div>

                <!-- Umkreissuche: Länder-Tags und/oder Stadt + Entfernung -->
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-sm font-medium text-gray-700">Umkreissuche</label>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <span class="mb-1 block text-xs text-gray-500">Länder</span>
                            <div v-if="form.countries.length" class="mb-2 flex flex-wrap gap-1">
                                <span
                                    v-for="code in form.countries"
                                    :key="code"
                                    class="inline-flex items-center gap-1 rounded-full bg-indigo-100 px-2 py-0.5 text-xs text-indigo-800"
                                >
                                    {{ countryName(code) }}
                                    <button
                                        type="button"
                                        class="leading-none text-indigo-500 hover:text-indigo-900"
                                        aria-label="Land entfernen"
                                        @click="removeCountry(code)"
                                    >&times;</button>
                                </span>
                            </div>
                            <select
                                class="w-full rounded-md border-gray-300 text-sm"
                                @change="addCountry(($event.target as HTMLSelectElement).value); ($event.target as HTMLSelectElement).value = ''"
                            >
                                <option value="">Land hinzufügen…</option>
                                <option
                                    v-for="opt in countryOptions.filter((o) => !form.countries.includes(o.code))"
                                    :key="opt.code"
                                    :value="opt.code"
                                >{{ opt.name }}</option>
                            </select>
                        </div>

                        <div>
                            <span class="mb-1 block text-xs text-gray-500">Stadt &amp; Entfernung</span>
                            <CityAutocomplete
                                v-model="form.city"
                                :allowed-countries="allowedCityCountries"
                                @typing="onCityTyping"
                                @select="onCitySelect"
                            />
                            <div class="mt-2 flex items-center gap-2">
                                <input
                                    v-model="form.radius_km"
                                    type="number"
                                    min="1"
                                    placeholder="km"
                                    class="w-24 rounded-md border-gray-300 text-sm"
                                />
                                <span class="text-xs text-gray-500">km Umkreis (Stadt wählen)</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2 sm:col-span-2">
                    <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                        Filter anwenden
                    </button>
                    <button type="button" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100" @click="resetFilters">
                        Zurücksetzen
                    </button>
                    <span class="ml-auto text-sm font-medium text-gray-600">
                        {{ events.total }} {{ events.total === 1 ? 'Event' : 'Events' }}
                    </span>
                </div>
            </div>
        </form>

        <div v-if="items.length === 0" class="text-gray-500">
            Noch keine veröffentlichten Events.
        </div>

        <ul class="grid gap-4 sm:grid-cols-2">
            <li
                v-for="event in items"
                :key="event.id"
                class="rounded-lg border border-gray-200 p-4 hover:shadow"
            >
                <div class="flex items-start justify-between gap-2">
                    <Link
                        :href="eventHref(event.slug)"
                        class="text-lg font-semibold text-indigo-700 hover:underline"
                    >
                        {{ event.title }}
                    </Link>
                    <FavoriteHeart
                        class="-mt-1 shrink-0"
                        :event-id="event.id"
                        :favorited="event.is_favorited"
                    />
                </div>
                <p class="mt-1 text-sm font-medium text-gray-700">{{ formatDate(event.start_date) }}</p>
                <p class="mt-1 text-sm text-gray-600">{{ event.short_description }}</p>
                <p class="mt-2 text-xs text-gray-500">
                    <span v-if="event.organizer">{{ event.organizer.company_name }}</span>
                    <span v-if="event.organizer && event.venue?.city"> · </span>
                    <span v-if="event.venue?.city">{{ event.venue.city }}</span>
                </p>
            </li>
        </ul>

        <!-- Infinite-scroll trigger -->
        <div ref="sentinel" class="h-10"></div>
        <p v-if="loading" class="py-4 text-center text-sm text-gray-500">Lade weitere Events…</p>
    </div>
    </AppLayout>
</template>
