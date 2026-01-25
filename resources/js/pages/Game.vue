<script setup lang="ts">
import { onMounted } from 'vue';
import GameLayout from '../layouts/GameLayout.vue';
import InventoryView from '../components/InventoryView.vue';
import MissionView from '../components/MissionView.vue';
import { usePlayerStore } from '../stores/usePlayerStore';

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
                <!-- Main Inventory (2 Cols) -->
                <div class="lg:col-span-2">
                    <InventoryView />
                </div>

                <!-- Side Panel (Missions) -->
                <div class="space-y-6">
                    <MissionView />
                    
                    <!-- Placeholder stats block or something? -->
                    <div class="p-6 bg-slate-900/50 border border-slate-800 rounded-xl text-center text-slate-500 text-sm">
                        More widgets coming soon...
                    </div>
                </div>
            </div>
        </div>
    </GameLayout>
</template>
