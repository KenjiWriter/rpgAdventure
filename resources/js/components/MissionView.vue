<script setup lang="ts">
import { ref, onMounted, computed, watch } from 'vue';
import { usePlayerStore } from '../stores/usePlayerStore';
import axios from 'axios';
import { Swords, Clock, X } from 'lucide-vue-next';

const store = usePlayerStore();
const loading = ref(false);

// Maps
const maps = ref<any[]>([]);
const loadingMaps = ref(false);

const activeMission = computed(() => store.activeMission);

const getDifficulty = (lvl: number) => {
    if (lvl <= 2) return 'Easy';
    if (lvl <= 8) return 'Medium';
    return 'Hard';
};

onMounted(async () => {
    await fetchMaps();
    // store.checkActiveMission() is called in Layout/Store init, but good to ensure
    await store.checkActiveMission();
});

// Auto-Claim Logic managed via Store/Layout timer, but we can also trigger here if open
// Actually, Layout handles the Global Timer. Here we just want to visualize it.
// We need a local timer to show the countdown OR just use the diff relative to ends_at reactively?
// A reactive timer is better.

const timeLeft = ref(0);
let timerInterval: any = null;

watch(activeMission, (mission) => {
    if (mission) {
        startTimer(mission.ends_at);
    } else {
        stopTimer();
    }
}, { immediate: true });

function startTimer(endsAtStr: string) {
    stopTimer();
    const endsAt = new Date(endsAtStr).getTime();
    
    // Initial check
    updateTime(endsAt);

    timerInterval = setInterval(() => {
        updateTime(endsAt);
    }, 1000);
}

function updateTime(endsAt: number) {
    const now = Date.now();
    const diff = endsAt - now;
    timeLeft.value = Math.max(0, Math.ceil(diff / 1000));
    
    // Auto Claim Trigger from VIEW if user is staring at it?
    // Or should the STORE handle auto-claim? 
    // If we rely on the View, the user must be on the page.
    // User requirement: "Automatically trigger ... when the countdown finishes". 
    // If they are on Dashboard, the pulse happens. If they click it, they go here.
    // If they are HERE, it should just happen.
    
    if (timeLeft.value <= 0 && activeMission.value && !loading.value) {
        // Attempt claim
        claim();
        stopTimer();
    }
}

function stopTimer() {
    if (timerInterval) clearInterval(timerInterval);
}

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

async function startMission(mapId: number) {
    loading.value = true;
    try {
        await store.startMission(mapId);
    } catch (e: any) {
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
        // BattleModal opens automatically via store
    } catch (e: any) {
        console.error("Failed to claim (might be too early or already claimed)", e);
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
        <div v-if="activeMission" class="p-6 bg-slate-800 rounded border border-slate-600 flex flex-col gap-4 text-center items-center justify-center min-h-[200px]">
            <div class="animate-pulse">
                <div class="font-bold text-2xl text-indigo-300">{{ activeMission.monster.name }}</div>
                <div class="text-sm text-slate-400">Target Level: {{ activeMission.monster.level || '?' }}</div>
            </div>

            <div class="flex flex-col items-center gap-2 text-4xl font-mono text-white">
                <Clock class="w-8 h-8 text-slate-400" :class="{ 'animate-spin': timeLeft > 0 }" />
                {{ formatTime(timeLeft) }}
                <div class="text-xs text-slate-500 font-sans tracking-wide uppercase">Traveling to location...</div>
            </div>
            
            <div v-if="timeLeft <= 0" class="text-yellow-400 font-bold animate-bounce">
                Encounter Imminent!
            </div>
        </div>

        <!-- Map Selection -->
        <div v-else class="grid gap-3">
            <div v-if="loadingMaps" class="text-center text-slate-500 py-4">Loading maps...</div>
            <div v-else-if="maps.length === 0" class="text-center text-red-400 py-4">No maps available. Service unavailable.</div>
            
            <div v-for="map in maps" :key="map.id" class="flex justify-between items-center p-3 bg-slate-800/50 hover:bg-slate-700/50 rounded border border-slate-700 transition-colors">
                <div>
                    <div class="font-semibold text-slate-200">{{ map.name }}</div>
                    <div class="text-xs text-slate-500 flex items-center gap-2">
                        <span>Min Lvl: {{ map.min_level }}</span>
                        <span>•</span>
                        <span>{{ getDifficulty(map.min_level) }}</span>
                        <span>•</span>
                        <!-- Duration Display -->
                         <div class="flex items-center gap-1">
                             <Clock class="w-3 h-3" />
                             <span :class="{'line-through text-slate-600': store.activeMount}">
                                 {{ Math.min(120, 30 + (map.min_level * 5)) }}s
                             </span>
                             <span v-if="store.activeMount" class="text-green-400 font-bold">
                                 {{ Math.round(Math.min(120, 30 + (map.min_level * 5)) * (1 - (store.activeMount.details?.reduction_percent || 0)/100)) }}s
                             </span>
                         </div>
                    </div>
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
