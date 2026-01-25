<script setup lang="ts">
import { onMounted } from 'vue';
import GameLayout from '../layouts/GameLayout.vue';
import InventoryView from '../components/InventoryView.vue';
import { usePlayerStore } from '../stores/usePlayerStore';
import { LogIn, Sword, Sparkles } from 'lucide-vue-next';

const props = defineProps<{
    characterId: string
}>();

const store = usePlayerStore();

onMounted(() => {
    if (props.characterId) {
        store.fetchPlayerData(props.characterId);
    }
});
</script>

<template>
    <GameLayout>
        <div class="space-y-8">
            <!-- Welcome / Context Header -->
            <div class="flex items-center justify-between">
                <div>
                   <h1 class="text-3xl font-bold text-white drop-shadow-lg font-serif">Character Overview</h1> 
                   <p class="text-slate-400">Manage your equipment and inventory.</p>
                </div>
                
                <div class="px-4 py-2 bg-slate-900/50 rounded border border-slate-700 text-xs text-slate-300">
                    <span class="text-indigo-400 font-bold">Tip:</span> Click items in backpack to auto-equip. Equip is locked during missions.
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Main Inventory (Expanded to full width for now) -->
                <div class="lg:col-span-2">
                    <InventoryView />
                </div>

                <!-- Side Panel (Activity Log) -->
                <div class="space-y-6">
                    <div class="p-6 bg-slate-900/50 border border-slate-800 rounded-xl relative overflow-hidden">
                        <div class="absolute inset-0 bg-gradient-to-br from-indigo-900/10 to-transparent pointer-events-none"></div>
                        <h3 class="font-bold text-white mb-4 flex items-center gap-2">
                            Recent Activity
                        </h3>
                        <div class="space-y-4 text-sm text-slate-400 relative z-10">
                            
                            <!-- Login Event -->
                            <div class="flex items-start gap-3 p-3 rounded-lg bg-slate-900/50 border border-slate-800/50">
                                <div class="mt-0.5 text-green-400">
                                    <LogIn class="w-4 h-4" />
                                </div>
                                <div class="flex-1">
                                    <p class="text-slate-200">Adventurer <span class="text-white font-bold">{{ store.character?.name }}</span> returned.</p>
                                    <span class="text-xs text-slate-500">Just now</span>
                                </div>
                            </div>

                            <!-- Combat Event Example -->
                            <div class="flex items-start gap-3 p-3 rounded-lg bg-slate-900/50 border border-slate-800/50 opacity-75">
                                <div class="mt-0.5 text-red-400">
                                    <Sword class="w-4 h-4" />
                                </div>
                                <div class="flex-1">
                                    <p class="text-slate-300">Defeated <span class="text-red-300">Wild Boar</span></p>
                                    <span class="text-xs text-slate-500">2 hours ago</span>
                                </div>
                            </div>

                             <!-- Loot Event Example -->
                            <div class="flex items-start gap-3 p-3 rounded-lg bg-slate-900/50 border border-slate-800/50 opacity-50">
                                <div class="mt-0.5 text-amber-400">
                                    <Sparkles class="w-4 h-4" />
                                </div>
                                <div class="flex-1">
                                    <p class="text-slate-300">Found <span class="text-amber-300">Rusty Dagger</span></p>
                                    <span class="text-xs text-slate-500">Yesterday</span>
                                </div>
                            </div>

                        </div>
                         <div class="mt-6 text-center text-xs text-slate-600 font-mono">
                             -- End of Log --
                         </div>
                    </div>
                </div>
            </div>
        </div>
    </GameLayout>
</template>
