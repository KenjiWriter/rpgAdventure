<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { usePlayerStore } from '../stores/usePlayerStore';
import GameLayout from '../layouts/GameLayout.vue';
import { RefreshCw, Coins, ShoppingBag } from 'lucide-vue-next';
import axios from 'axios';
import { router } from '@inertiajs/vue3';

const props = defineProps<{
    characterId: string,
    stock: any[]
}>();

const store = usePlayerStore();
const currentStock = ref(props.stock || []);
const loading = ref(false);

const refreshCost = ref(0);

onMounted(() => {
    store.fetchPlayerData(props.characterId);
    refreshCost.value = (store.character?.level || 1) * 10;
});

const getRarityColor = (rarity: string) => {
    switch (rarity) {
        case 'common': return 'border-slate-600 text-slate-300';
        case 'uncommon': return 'border-green-600 text-green-300 bg-green-900/10';
        case 'rare': return 'border-blue-600 text-blue-300 bg-blue-900/10';
        case 'epic': return 'border-purple-600 text-purple-300 bg-purple-900/10 shadow-purple';
        case 'legendary': return 'border-amber-600 text-amber-300 bg-amber-900/10 shadow-amber';
        default: return 'border-slate-600 text-slate-300';
    }
};

async function refreshStock() {
    if (store.character.gold < refreshCost.value) return;
    loading.value = true;
    try {
        const res = await axios.post('/api/merchant/refresh', { character: store.character });
        currentStock.value = res.data.stock;
        store.fetchPlayerData(store.character.id); // Sync gold
    } catch (e) {
        alert(e.response?.data?.error || 'Failed to refresh');
    } finally {
        loading.value = false;
    }
}

async function buyItem(item: any) {
    if (!confirm(`Buy ${item.template.name} for ${item.cost} Gold?`)) return;
    
    loading.value = true;
    try {
        await axios.post('/api/merchant/buy', { 
            character: store.character,
            merchant_item_id: item.id 
        });
        // Remove item locally or refresh
        currentStock.value = currentStock.value.filter(i => i.id !== item.id);
        store.fetchPlayerData(store.character.id);
        
        // Show success logic?
    } catch (e) {
        alert(e.response?.data?.error || 'Failed to buy');
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <GameLayout>
        <div class="max-w-4xl mx-auto space-y-8">
            <div class="flex items-center justify-between">
                <div>
                     <h1 class="text-3xl font-bold text-white drop-shadow-lg font-serif flex items-center gap-3">
                        <ShoppingBag class="w-8 h-8 text-amber-500" />
                        Merchant
                    </h1>
                    <p class="text-slate-400">Spend your gold on random wares. Stock refreshes hourly.</p>
                </div>

                <div class="flex items-center gap-4">
                    <div class="text-right">
                        <div class="text-xs text-slate-500 uppercase tracking-widest">Your Gold</div>
                        <div class="text-xl font-bold text-amber-400 font-mono">{{ store.character?.gold || 0 }}g</div>
                    </div>
                </div>
            </div>

            <!-- Stock Grid -->
            <div class="bg-slate-900/80 border border-slate-800 rounded-xl p-6 backdrop-blur-sm relative min-h-[400px]">
                <div v-if="loading" class="absolute inset-0 bg-slate-950/50 z-50 flex items-center justify-center">
                    <RefreshCw class="w-8 h-8 text-indigo-500 animate-spin" />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div v-for="item in currentStock" :key="item.id" 
                         class="group relative bg-slate-950 border-2 rounded-lg p-4 transition-all hover:-translate-y-1 hover:shadow-lg cursor-pointer"
                         :class="getRarityColor(item.data.rarity)"
                         @click="buyItem(item)"
                    >
                        <div class="flex justify-between items-start mb-2">
                             <div class="font-bold truncate pr-2">{{ item.template.name }}</div>
                             <div class="text-[10px] uppercase font-bold px-1.5 py-0.5 rounded bg-black/30 border border-white/10">
                                {{ item.data.rarity }}
                             </div>
                        </div>
                        
                        <!-- Stats Preview -->
                        <div class="space-y-1 text-xs mb-4 min-h-[60px]">
                            <div v-for="bonus in item.data.bonuses" :key="bonus.type" class="flex justify-between text-slate-300">
                                <span class="capitalize text-slate-400">{{ bonus.type }}</span>
                                <span class="font-mono font-bold text-green-400">+{{ bonus.value }}</span>
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-t border-slate-800/50 flex justify-between items-center">
                            <div class="text-amber-400 font-bold font-mono flex items-center gap-1">
                                {{ item.cost }} <span class="text-[10px]">GOLD</span>
                            </div>
                            <button class="px-3 py-1 bg-slate-800 hover:bg-slate-700 rounded text-xs text-white border border-slate-700 transition-colors">
                                Buy
                            </button>
                        </div>
                    </div>
                    
                    <!-- Empty Slots Fillers if less than 6 -->
                    <div v-for="n in (6 - currentStock.length)" :key="'empty-'+n" class="bg-slate-950/30 border border-slate-800 border-dashed rounded-lg flex items-center justify-center h-[180px] text-slate-700 text-sm">
                        Empty Slot
                    </div>
                </div>
            </div>

            <!-- Refresh Actions -->
            <div class="flex justify-center">
                <button @click="refreshStock" 
                        class="group flex items-center gap-3 px-6 py-3 bg-slate-800 hover:bg-indigo-900 border border-slate-700 hover:border-indigo-500 rounded-lg transition-all text-slate-300 hover:text-white">
                    <RefreshCw class="w-5 h-5 group-hover:rotate-180 transition-transform duration-500" />
                    <div class="text-left">
                        <div class="font-bold text-sm">Force Refresh</div>
                        <div class="text-[10px] text-slate-400 group-hover:text-indigo-200">Cost: {{ refreshCost }} Gold</div>
                    </div>
                </button>
            </div>
        </div>
    </GameLayout>
</template>
