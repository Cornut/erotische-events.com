<script setup lang="ts">
import { ref, watch } from 'vue';

interface Suggestion {
    label: string;
    lat: number;
    lng: number;
    country: string;
}

const props = withDefaults(
    defineProps<{ modelValue: string; allowedCountries?: string[] }>(),
    { allowedCountries: () => [] },
);
const emit = defineEmits<{
    'update:modelValue': [value: string];
    typing: [];
    select: [suggestion: Suggestion];
}>();

const query = ref(props.modelValue);
const suggestions = ref<Suggestion[]>([]);
const open = ref(false);
let timer: ReturnType<typeof setTimeout> | undefined;

watch(
    () => props.modelValue,
    (v) => {
        if (v !== query.value) {
            query.value = v;
        }
    },
);

function onInput(): void {
    emit('update:modelValue', query.value);
    emit('typing');
    clearTimeout(timer);
    if (query.value.trim().length < 3) {
        suggestions.value = [];
        open.value = false;
        return;
    }
    timer = setTimeout(fetchSuggestions, 300);
}

// Builds "City, …, Country" while dropping repeated administrative names.
// Photon often returns name=city=state for a place (e.g. the city Bern sits in
// the canton Bern), which would otherwise render as "Bern, Bern, Bern, Schweiz".
function buildLabel(p: Record<string, string>): string {
    const seen = new Set<string>();
    return [p.name, p.city, p.state, p.country]
        .filter(Boolean)
        .filter((part) => {
            const key = part.toLowerCase();
            if (seen.has(key)) {
                return false;
            }
            seen.add(key);
            return true;
        })
        .join(', ');
}

async function fetchSuggestions(): Promise<void> {
    try {
        const url = `https://photon.komoot.io/api/?q=${encodeURIComponent(query.value)}&limit=15&lang=de`;
        const res = await fetch(url);
        const json = await res.json();
        const allowed = (props.allowedCountries ?? []).map((c) => c.toUpperCase());

        // Collapse multiple Photon features that resolve to the same label
        // (city / district / canton "Bern"), keeping the most relevant first hit.
        const byLabel = new Map<string, Suggestion>();
        for (const f of (json.features ?? []) as Array<{
            geometry: { coordinates: [number, number] };
            properties: Record<string, string>;
        }>) {
            const suggestion: Suggestion = {
                lat: f.geometry.coordinates[1],
                lng: f.geometry.coordinates[0],
                country: (f.properties.countrycode ?? '').toUpperCase(),
                label: buildLabel(f.properties),
            };
            if (allowed.length > 0 && ! allowed.includes(suggestion.country)) {
                continue;
            }
            if (suggestion.label !== '' && ! byLabel.has(suggestion.label)) {
                byLabel.set(suggestion.label, suggestion);
            }
        }

        suggestions.value = [...byLabel.values()].slice(0, 6);
        open.value = suggestions.value.length > 0;
    } catch {
        suggestions.value = [];
        open.value = false;
    }
}

function choose(s: Suggestion): void {
    query.value = s.label;
    emit('update:modelValue', s.label);
    emit('select', s);
    open.value = false;
}
</script>

<template>
    <div class="relative">
        <input
            v-model="query"
            type="text"
            placeholder="Stadt oder Adresse…"
            class="w-full rounded-md border-gray-300 text-sm"
            autocomplete="off"
            @input="onInput"
            @focus="open = suggestions.length > 0"
            @blur="open = false"
        />
        <ul
            v-if="open"
            class="absolute z-20 mt-1 max-h-56 w-full overflow-auto rounded-md border border-gray-200 bg-white text-sm shadow-lg"
        >
            <li
                v-for="(s, i) in suggestions"
                :key="i"
                class="cursor-pointer px-3 py-2 hover:bg-indigo-50"
                @mousedown.prevent="choose(s)"
            >
                {{ s.label }}
            </li>
        </ul>
    </div>
</template>
