<script setup lang="ts">
import { useUIStore } from '../stores/useUIStore';
import { XCircle, CheckCircle, Info } from 'lucide-vue-next';

const ui = useUIStore();

const getIcon = (type: string) => {
    switch(type) {
        case 'error': return XCircle;
        case 'success': return CheckCircle;
        default: return Info;
    }
}

const getColors = (type: string) => {
    switch(type) {
        case 'error': return 'bg-red-950/90 border-red-500 text-red-200';
        case 'success': return 'bg-green-950/90 border-green-500 text-green-200';
        default: return 'bg-slate-900/90 border-indigo-500 text-indigo-200';
    }
}
</script>

<template>
    <div class="fixed top-20 right-4 z-[100] flex flex-col gap-2 pointer-events-none">
        <TransitionGroup 
            enter-active-class="transform ease-out duration-300 transition" 
            enter-from-class="translate-x-8 opacity-0" 
            enter-to-class="translate-x-0 opacity-100"
            leave-active-class="transition ease-in duration-200" 
            leave-from-class="opacity-100" 
            leave-to-class="opacity-0 scale-95"
        >
            <div 
                v-for="toast in ui.toasts" 
                :key="toast.id" 
                class="pointer-events-auto flex items-start gap-3 p-4 rounded-lg border shadow-xl max-w-sm backdrop-blur-md"
                :class="getColors(toast.type)"
                @click="ui.removeToast(toast.id)"
            >
                <component :is="getIcon(toast.type)" class="w-5 h-5 flex-shrink-0 mt-0.5" />
                <div class="text-sm font-medium pr-4">{{ toast.message }}</div>
            </div>
        </TransitionGroup>
    </div>
</template>
