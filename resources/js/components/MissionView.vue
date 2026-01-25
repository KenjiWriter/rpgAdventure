<script setup lang="ts">
import { ref, onMounted, computed, onUnmounted } from 'vue';
import { usePlayerStore } from '../stores/usePlayerStore';
import axios from 'axios';
import { Swords, Clock, X } from 'lucide-vue-next';

const store = usePlayerStore();
const activeMission = ref<any>(null);
const loading = ref(false);
const timeLeft = ref(0);
let timerInterval: any = null;

// Maps
const maps = ref<any[]>([]);
const loadingMaps = ref(false);

const getDifficulty = (lvl: number) => {
    if (lvl <= 2) return 'Easy';
    if (lvl <= 8) return 'Medium';
    return 'Hard';
};

const hasActiveMission = computed(() => !!activeMission.value);
const canClaim = computed(() => timeLeft.value <= 0 && hasActiveMission.value);

onMounted(async () => {
    if (!store.character && store.character?.id) {
         // If character ID known but not loaded? Store naming is characterId usually passed or user known.
         // Actually, how do we know the ID?
         // In web.php, we pass user to view? No, inertia.
         // Maybe we rely on the backend to tell us the character?
         // Or getting it from props?
         // `Game.vue` gets `characterId` prop. `WorldMap.vue` does NOT.
         // We need to pass character info to `WorldMap` or fetch it via a "me" endpoint.
         // `GameLayout` uses `usePage`?
         // Let's assume for MVP we fetch based on what we have or fix the route to pass the character.
         // The `WorldMap.vue` is rendered in `web.php` for `/map` without props.
         // Let's change `web.php` to pass `character` or have `WorldMap` fetch "my character".
         // `QuestController` gets `auth()->user()->characters()->firstOrFail()`.
         // We can add `store.fetchActiveCharacter()`?
         // Or just update `web.php` to pass the character ID to the Map view.
    }
    await Promise.all([checkActiveMission(), fetchMaps()]);
});

onUnmounted(() => {
    if (timerInterval) clearInterval(timerInterval);
});

async function fetchMaps() {
    loadingMaps.value = true;
    try {
        const res = await axios.get('/api/maps');
        maps.value = res.data;
    } catch (e) {
        console.error("Failed to fetch maps", e);
    } finally {
        loadingMaps.value = false;
    }
}

async function checkActiveMission() {
    if (!store.character?.id) return;
    try {
        const res = await axios.get(`/api/mission/active?character_id=${store.character.id}`);
        if (res.data.mission) {
            activeMission.value = res.data.mission;
            startTimer(res.data.mission.ends_at, res.data.server_time);
        } else {
            activeMission.value = null;
        }
    } catch (e) {
        console.error(e);
    }
}

function startTimer(endsAtStr: string, serverTimeStr: string) {
    const endsAt = new Date(endsAtStr).getTime();
    // Calculate offset if needed, but for simplicity use local browser relative time if server time provided
    // Ideally sync with server offset.
    // Let's just count down to endsAt.
    
    if (timerInterval) clearInterval(timerInterval);
    
    timerInterval = setInterval(() => {
        const now = Date.now();
        // Adjust for potential timezone diff if endsAt is UTC? Laravel sends ISO8601 usually.
        // Assuming endsAt is comparable to Date.now().
        // If not, use diff from serverTime vs local time.
        // Simple fallback:
        const diff = endsAt - now;
        timeLeft.value = Math.max(0, Math.ceil(diff / 1000));
        
        if (timeLeft.value <= 0) {
            clearInterval(timerInterval);
        }
    }, 1000);
}

async function startMission(mapId: number) {
    loading.value = true;
    try {
        await axios.post('/api/mission/start', {
            character_id: store.character.id,
            map_id: mapId
        });
        await checkActiveMission();
    } catch (e) {
        alert("Failed to start mission: " + e.response?.data?.message);
    } finally {
        loading.value = false;
    }
}

async function claim() {
    if (!activeMission.value) return;
    loading.value = true;
    try {
        await store.claimMission(activeMission.value.id);
        // On success, Modal opens via store watcher in Layout.
        // Reset local state
        activeMission.value = null;
    } catch (e) {
        alert("Failed to claim: " + e.response?.data?.message);
    } finally {
        loading.value = false;
    }
}

function formatTime(sec: number) {
    const m = Math.floor(sec / 60);
    const s = sec % 60;
    return `${m}:${s.toString().padStart(2, '0')}`;
}
</script>

<template>
    <div class="bg-slate-900/80 border border-slate-700 rounded-xl p-6 backdrop-blur">
        <h2 class="text-xl font-bold text-white mb-4 flex items-center gap-2">
            <Swords class="w-5 h-5 text-red-500" />
            Missions
        </h2>

        <!-- Active Mission State -->
        <div v-if="hasActiveMission" class="p-4 bg-slate-800 rounded border border-slate-600 flex justify-between items-center">
            <div>
                <div class="font-bold text-indigo-300">{{ activeMission.monster.name }}</div>
                <div class="text-xs text-slate-400">Target Level: {{ activeMission.monster.level || '?' }}</div>
            </div>

            <div v-if="!canClaim" class="flex items-center gap-2 text-2xl font-mono text-white">
                <Clock class="w-5 h-5 text-slate-400 animate-pulse" />
                {{ formatTime(timeLeft) }}
            </div>

            <button v-else @click="claim" :disabled="loading" 
                class="px-4 py-2 bg-yellow-600 hover:bg-yellow-500 text-white font-bold rounded animate-bounce shadow-lg shadow-yellow-500/20">
                Battle!
            </button>
        </div>

        <!-- Map Selection -->
        <div v-else class="grid gap-3">
            <div v-if="loadingMaps" class="text-center text-slate-500 py-4">Loading maps...</div>
            <div v-else-if="maps.length === 0" class="text-center text-red-400 py-4">No maps available. Service unavailable.</div>
            
            <div v-for="map in maps" :key="map.id" class="flex justify-between items-center p-3 bg-slate-800/50 hover:bg-slate-700/50 rounded border border-slate-700 transition-colors">
                <div>
                    <div class="font-semibold text-slate-200">{{ map.name }}</div>
                    <div class="text-xs text-slate-500">Min Lvl: {{ map.min_level }} • {{ getDifficulty(map.min_level) }}</div>
                </div>
                
                <button @click="startMission(map.id)" 
                    :disabled="loading || (store.character?.level < map.min_level)"
                    class="px-3 py-1 rounded text-sm transition-all flex items-center gap-2"
                    :class="store.character?.level >= map.min_level 
                        ? 'bg-slate-700 hover:bg-indigo-600 text-slate-300 hover:text-white' 
                        : 'bg-slate-800 text-slate-600 cursor-not-allowed border border-slate-700'"
                >
                    <span v-if="loading">...</span>
                    <span v-else>Start</span>
                    <span v-if="store.character?.level < map.min_level" class="text-[10px] uppercase font-bold text-red-500 ml-1">
                        Locked
                    </span>
                </button>
            </div>
        </div>
    </div>
</template>
