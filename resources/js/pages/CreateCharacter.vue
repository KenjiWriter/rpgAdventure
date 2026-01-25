<script setup lang="ts">
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { useForm } from '@inertiajs/vue3';

import { Shield, Sparkles, Sword, Info } from 'lucide-vue-next';

const form = useForm({
    name: '',
    class: '',
});

const selectedClass = ref(''); // Keep for UI selection logic mapping to form.class

const classes = [
    {
        id: 'warrior',
        name: 'Warrior',
        icon: Shield,
        desc: 'A master of defense and raw power.',
        stats: 'High Strength, High Vitality',
        color: 'bg-amber-600'
    },
    {
        id: 'assassin',
        name: 'Assassin',
        icon: Sword,
        desc: 'Swift and deadly strike expert.',
        stats: 'High Dexterity, High Crit',
        color: 'bg-red-600'
    },
    {
        id: 'mage',
        name: 'Mage',
        icon: Sparkles,
        desc: 'Harnesses the elements to destroy foes.',
        stats: 'High Intelligence, High Mana',
        color: 'bg-blue-600'
    }
];

function selectClass(id: string) {
    selectedClass.value = id;
    form.class = id;
}

function submit() {
    if (!form.name || !form.class) {
        return; // Add UI error handling if needed, or rely on html inputs but for button disable
    }
    
    form.post(route('character.store'), {
        onSuccess: () => {
            // Redirect handled by backend
        }
    });
}
</script>

<template>
    <Head title="Create Character" />

    <div class="min-h-screen bg-slate-950 flex flex-col items-center justify-center p-4">
        
        <div class="w-full max-w-5xl">
            <h1 class="text-4xl font-bold text-white text-center mb-2 font-serif">Create Your Hero</h1>
            <p class="text-slate-400 text-center mb-12">Choose your path and begin the adventure.</p>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
                
                <!-- Left: Form -->
                <div class="space-y-8">
                    <!-- Name Input -->
                    <div>
                        <label class="block text-sm font-bold text-slate-300 mb-2">Character Name</label>
                        <input v-model="form.name" type="text" placeholder="Enter name..." 
                            class="w-full bg-slate-900 border border-slate-700 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-indigo-500 transition-colors text-lg" />
                        <div v-if="form.errors.name" class="text-red-500 text-sm mt-1">{{ form.errors.name }}</div>
                    </div>

                    <!-- Class Grid -->
                    <div>
                        <label class="block text-sm font-bold text-slate-300 mb-2">Select Class</label>
                        <div class="grid gap-4">
                            <button v-for="cls in classes" :key="cls.id"
                                @click="selectClass(cls.id)"
                                class="flex items-center gap-4 p-4 rounded-xl border-2 transition-all duration-200 text-left group"
                                :class="selectedClass === cls.id ? 'bg-slate-800 border-indigo-500 shadow-[0_0_15px_rgba(99,102,241,0.3)]' : 'bg-slate-900/50 border-slate-800 hover:bg-slate-800 hover:border-slate-600'"
                            >
                                <div class="w-12 h-12 rounded-lg flex items-center justify-center text-white shadow-lg" :class="cls.color">
                                    <component :is="cls.icon" class="w-6 h-6" />
                                </div>
                                <div class="flex-1">
                                    <div class="font-bold text-white group-hover:text-indigo-300 transition-colors">{{ cls.name }}</div>
                                    <div class="text-xs text-slate-400">{{ cls.stats }}</div>
                                </div>
                                <div v-if="selectedClass === cls.id" class="text-indigo-400">
                                    <div class="w-4 h-4 rounded-full bg-indigo-500"></div>
                                </div>
                            </button>
                            <div v-if="form.errors.class" class="text-red-500 text-sm mt-1">{{ form.errors.class }}</div>
                        </div>
                    </div>
                </div>

                <!-- Right: Preview -->
                <div class="relative bg-slate-900 border border-slate-800 rounded-2xl p-8 flex flex-col items-center justify-center text-center">
                    <div class="absolute inset-0 bg-[radial-gradient(circle_at_center,_var(--tw-gradient-stops))] from-indigo-900/20 via-slate-950/0 to-slate-950/0 pointer-events-none"></div>
                    
                    <div class="w-32 h-32 rounded-full bg-slate-800 border-4 border-slate-700 mb-6 flex items-center justify-center overflow-hidden relative shadow-2xl">
                         <div v-if="!selectedClass" class="text-slate-600">?</div>
                         <component v-else :is="classes.find(c => c.id === selectedClass)?.icon" class="w-16 h-16 text-white" />
                    </div>

                    <h2 class="text-2xl font-bold text-white mb-2">
                        {{ form.name || 'Unknown Hero' }}
                    </h2>
                    <p class="text-indigo-400 font-mono text-sm mb-6">
                        {{ selectedClass ? classes.find(c => c.id === selectedClass)?.name : 'Class Undecided' }}
                    </p>

                    <p class="text-slate-400 text-sm max-w-xs leading-relaxed">
                        {{ selectedClass ? classes.find(c => c.id === selectedClass)?.desc : 'Select a class to see details.' }}
                    </p>
                    
                    <div class="flex-1"></div>

                    <button @click="submit" :disabled="form.processing"
                        class="w-full py-4 bg-indigo-600 hover:bg-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed text-white font-bold rounded-lg shadow-lg border-t border-indigo-400 transition-all">
                        {{ form.processing ? 'Forging...' : 'Create Character' }}
                    </button>
                    
                </div>

            </div>

        </div>

    </div>
</template>
