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

// Maps (Placeholder - In real app fetch from API)
const maps = [
    { id: 1, name: 'Whispering Fields', minLevel: 1, difficulty: 'Easy' },
    { id: 2, name: 'Dark Forest', minLevel: 5, difficulty: 'Medium' }
];

const hasActiveMission = computed(() => !!activeMission.value);
const canClaim = computed(() => timeLeft.value <= 0 && hasActiveMission.value);

onMounted(() => {
    checkActiveMission();
});

onUnmounted(() => {
    if (timerInterval) clearInterval(timerInterval);
});

async function checkActiveMission() {
    try {
        const res = await axios.get(`/api/mission/active?character_id=${store.character?.id}`);
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
            <div v-for="map in maps" :key="map.id" class="flex justify-between items-center p-3 bg-slate-800/50 hover:bg-slate-700/50 rounded border border-slate-700 transition-colors">
                <div>
                    <div class="font-semibold text-slate-200">{{ map.name }}</div>
                    <div class="text-xs text-slate-500">Min Lvl: {{ map.minLevel }} • {{ map.difficulty }}</div>
                </div>
                
                <button @click="startMission(map.id)" :disabled="loading"
                    class="px-3 py-1 bg-slate-700 hover:bg-indigo-600 text-slate-300 hover:text-white rounded text-sm transition-all">
                    Start
                </button>
            </div>
        </div>
    </div>
</template>
