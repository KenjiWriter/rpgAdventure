<script setup lang="ts">
import { ref, onMounted } from 'vue';
import GameLayout from '@/Layouts/GameLayout.vue';
import { usePlayerStore } from '@/Stores/usePlayerStore';
import { useUIStore } from '@/Stores/useUIStore';
import axios from 'axios';

const playerStore = usePlayerStore();
const uiStore = useUIStore();

const availableMounts = ref<any>({});
const loading = ref(true);
const processing = ref(false);

const fetchMounts = async () => {
    try {
        const response = await axios.get('/api/mounts');
        availableMounts.value = response.data.mounts;
    } catch (error) {
        console.error('Failed to fetch mounts', error);
    } finally {
        loading.value = false;
    }
};

const rentMount = async (type: string, mount: any) => {
    if (playerStore.character.gold < mount.cost) {
        uiStore.addToast('Insufficient Gold!', 'error');
        return;
    }

    if (!confirm(`Rent ${mount.name} for ${mount.cost} gold? This will replace any active mount.`)) return;

    processing.value = true;
    try {
        await playerStore.rentMount(type);
    } catch (error) {
        // Error handled in store
    } finally {
        processing.value = false;
    }
};

onMounted(() => {
    fetchMounts();
});
</script>

<template>
    <GameLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-200 leading-tight">Stable Master</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg border border-gray-700">
                    <div class="p-6 text-gray-100">
                        
                        <div class="mb-8">
                            <h3 class="text-lg font-bold mb-4 text-yellow-500">Welcome to the Stables!</h3>
                            <p class="text-gray-400">Rent a mount to reduce travel time for your missions. Mounts last for 7 days.</p>
                        </div>

                        <div v-if="loading" class="text-center py-10">
                            Loading stable...
                        </div>

                        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                            <div v-for="(mount, type) in availableMounts" :key="type" 
                                class="bg-gray-900 p-4 rounded-lg border border-gray-700 flex flex-col justify-between hover:border-yellow-600 transition duration-200 relative">
                                
                                <div v-if="playerStore.activeMount?.mount_type === type" 
                                    class="absolute top-2 right-2 bg-green-600 text-xs px-2 py-1 rounded text-white font-bold shadow-lg z-10">
                                    ACTIVE
                                </div>

                                <div>
                                    <div class="text-4xl mb-4 text-center">🐎</div>
                                    <h4 class="text-xl font-bold text-center mb-1 text-gray-100">{{ mount.name }}</h4>
                                    <div class="text-center text-blue-400 font-bold mb-4">-{{ mount.reduction_percent }}% Travel Time</div>
                                    <p class="text-sm text-gray-400 text-center mb-4">{{ mount.description }}</p>
                                </div>

                                <div class="mt-auto">
                                    <div class="flex justify-between items-center mb-4 px-4 py-2 bg-gray-950 rounded">
                                        <span class="text-gray-400">Cost:</span>
                                        <span class="text-yellow-500 font-bold">{{ mount.cost.toLocaleString() }} G</span>
                                    </div>
                                    <button 
                                        @click="rentMount(String(type), mount)"
                                        :disabled="processing || playerStore.character.gold < mount.cost"
                                        :class="{'opacity-50 cursor-not-allowed': processing || playerStore.character.gold < mount.cost}"
                                        class="w-full py-2 px-4 bg-yellow-600 hover:bg-yellow-500 text-white font-bold rounded shadow-md transition-colors"
                                    >
                                        {{ processing ? 'Processing...' : (playerStore.activeMount?.mount_type === type ? 'Extend Rental' : 'Rent Now') }}
                                    </button>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </GameLayout>
</template>
