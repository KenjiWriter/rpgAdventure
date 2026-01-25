import { defineStore } from 'pinia';
import { ref } from 'vue';

export interface Toast {
    id: number;
    message: string;
    type: 'success' | 'error' | 'info';
}

export const useUIStore = defineStore('ui', () => {
    const toasts = ref<Toast[]>([]);
    let nextId = 1;

    function addToast(message: string, type: 'success' | 'error' | 'info' = 'info') {
        const id = nextId++;
        toasts.value.push({ id, message, type });

        setTimeout(() => {
            removeToast(id);
        }, 5000);
    }

    function removeToast(id: number) {
        toasts.value = toasts.value.filter(t => t.id !== id);
    }

    return {
        toasts,
        addToast,
        removeToast
    };
});
