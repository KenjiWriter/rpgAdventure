<script setup lang="ts">
import GameLayout from '../layouts/GameLayout.vue';
import MissionView from '../components/MissionView.vue';
</script>

<template>
    <GameLayout>
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-white drop-shadow-lg font-serif">World Map</h1>
                    <p class="text-slate-400">Choose your expedition destination.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6">
                <!-- Reusing the MissionView component which acts as the Map Selector -->
                <MissionView />
            </div>
        </div>
    </GameLayout>
</template>
