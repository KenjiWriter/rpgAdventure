<script setup lang="ts">
import { onMounted } from 'vue';
import GameLayout from '../layouts/GameLayout.vue';
import InventoryView from '../components/InventoryView.vue';
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
                    <span class="text-indigo-400 font-bold">Tip:</span> Click items in backpack to auto-equip. Click equipment to unequip.
                </div>
            </div>

            <!-- The Main Inventory View -->
            <InventoryView />
        </div>
    </GameLayout>
</template>
