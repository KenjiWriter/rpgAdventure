<script setup lang="ts">
import { onMounted, computed } from 'vue';
import GameLayout from '../layouts/GameLayout.vue';
import { usePlayerStore } from '../stores/usePlayerStore';
import { Scroll, CheckCircle2, Circle } from 'lucide-vue-next';

const store = usePlayerStore();
const quests = computed(() => store.quests);

onMounted(() => {
    // We assume layout or previous page loaded char, but fetchLogs/Quests might need refresh
    if (store.character?.id) {
        store.fetchPlayerData(store.character.id);
    }
});

const handleClaim = async (questId: number) => {
    await store.claimQuest(questId);
};
</script>

<template>
    <GameLayout>
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-white drop-shadow-lg font-serif">Quest Log</h1>
                    <p class="text-slate-400">Track your active objectives.</p>
                </div>
            </div>

            <div v-if="quests.length === 0" class="p-12 text-center text-slate-500">
                No active quests. Visit the town board or explore the world to find new missions.
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div v-for="quest in quests" :key="quest.id" 
                    class="bg-slate-900/50 border border-slate-700/50 rounded-xl p-6 relative overflow-hidden group hover:border-slate-500 transition-colors">
                    
                    <div class="absolute top-0 right-0 p-4 opacity-50">
                        <Scroll class="w-12 h-12 text-slate-700 group-hover:text-slate-600 transition-colors" />
                    </div>

                    <div class="relative z-10">
                        <div class="flex items-center gap-3 mb-2">
                             <component :is="quest.completed ? CheckCircle2 : Circle" 
                                class="w-5 h-5"
                                :class="quest.completed ? 'text-green-500' : 'text-amber-500'" 
                             />
                            <h3 class="text-xl font-bold text-slate-200">{{ quest.title }}</h3>
                        </div>
                        
                        <p class="text-slate-400 mb-4 ml-8">{{ quest.objective }}</p>

                        <div class="ml-8">
                            <div class="flex justify-between text-xs text-slate-500 mb-1">
                                <span>Progress</span>
                                <span>{{ quest.progress }} / {{ quest.total }}</span>
                            </div>
                            <div class="h-2 w-full bg-slate-800 rounded-full overflow-hidden">
                                <div class="h-full bg-amber-600 transition-all duration-500"
                                    :style="{ width: `${(Math.min(quest.progress, quest.total) / quest.total) * 100}%` }"
                                    :class="quest.completed ? 'bg-green-600' : 'bg-amber-600'"
                                ></div>
                            </div>
                        </div>

                        <div v-if="quest.completed" class="mt-4 ml-8">
                             <button 
                                v-if="!quest.claimed"
                                @click="handleClaim(quest.id)"
                                class="px-4 py-2 bg-green-600/20 text-green-400 border border-green-600/50 rounded text-sm hover:bg-green-600/30 transition-colors"
                             >
                                Collect Reward
                             </button>
                             <div v-else class="text-xs text-slate-500 italic mt-2">
                                 Reward Claimed
                             </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </GameLayout>
</template>
