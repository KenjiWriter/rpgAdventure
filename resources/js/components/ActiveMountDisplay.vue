<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { usePlayerStore } from '@/Stores/usePlayerStore';
import { Clock } from 'lucide-vue-next';

const playerStore = usePlayerStore();
const timeLeft = ref<string>('');
const isExpired = ref(false);
let timerInterval: any = null;

const activeMount = computed(() => playerStore.activeMount);

const updateTimeLeft = () => {
    if (!activeMount.value) {
        timeLeft.value = '';
        return;
    }

    const expiresAt = new Date(activeMount.value.expires_at).getTime();
    const now = new Date().getTime();
    const diff = expiresAt - now;

    if (diff <= 0) {
        timeLeft.value = 'Expired';
        isExpired.value = true;
        // Optionally trigger refresh logic
        return;
    }

    const days = Math.floor(diff / (1000 * 60 * 60 * 24));
    const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
    const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));

    if (days > 0) {
        timeLeft.value = `${days}d ${hours}h`;
    } else {
        timeLeft.value = `${hours}h ${minutes}m`;
    }
};

watch(activeMount, () => {
    updateTimeLeft();
    if (activeMount.value) {
        if (timerInterval) clearInterval(timerInterval);
        timerInterval = setInterval(updateTimeLeft, 60000); // Update every minute
    }
}, { immediate: true });

onMounted(() => {
    updateTimeLeft();
    timerInterval = setInterval(updateTimeLeft, 60000);
});

onUnmounted(() => {
    if (timerInterval) clearInterval(timerInterval);
});
</script>

<template>
    <div v-if="activeMount" class="bg-gray-800 border border-gray-700 rounded-lg p-3 flex items-center space-x-3 shadow-md">
        <div class="bg-blue-900/50 p-2 rounded-full">
            <span class="text-xl">🐎</span>
        </div>
        <div>
            <div class="text-sm font-bold text-gray-200">{{ activeMount.details?.name || activeMount.mount_type }}</div>
            <div class="text-xs flex items-center space-x-1" :class="isExpired ? 'text-red-400' : 'text-blue-300'">
                <Clock class="w-3 h-3" />
                <span>{{ timeLeft }}</span>
            </div>
            <div class="text-xs text-green-400 mt-1">
                -{{ activeMount.details?.reduction_percent || 0 }}% Travel Time
            </div>
        </div>
    </div>
</template>
