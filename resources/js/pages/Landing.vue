<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Sword } from 'lucide-vue-next';
</script>

<template>
    <Head title="Welcome" />

    <div class="relative min-h-screen bg-slate-950 flex flex-col items-center justify-center overflow-hidden selection:bg-red-500 selection:text-white">
        <!-- Background Image with Overlay -->
        <div class="absolute inset-0 z-0">
             <img src="https://images.unsplash.com/photo-1542259681-dadcd731560b?q=80&w=2669&auto=format&fit=crop" 
                  alt="Background" 
                  class="w-full h-full object-cover opacity-30" />
             <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/80 to-slate-900/50"></div>
        </div>

        <!-- Content -->
        <div class="relative z-10 w-full max-w-4xl px-6 text-center">
            
            <div class="mb-8 animate-fade-in-down">
                <div class="inline-flex items-center justify-center p-4 bg-red-600/20 rounded-full border border-red-500/50 shadow-[0_0_30px_rgba(239,68,68,0.3)] mb-6">
                    <Sword class="w-12 h-12 text-red-500" />
                </div>
                <h1 class="text-6xl md:text-8xl font-black text-white tracking-tighter drop-shadow-2xl font-serif">
                    RPG <span class="text-transparent bg-clip-text bg-gradient-to-r from-red-500 to-amber-500">ADVENTURE</span>
                </h1>
                <p class="mt-6 text-xl text-slate-400 max-w-2xl mx-auto font-light tracking-wide">
                    Forge your destiny in a persistent world. Craft legendary gear, battle mythical beasts, and rise to glory.
                </p>
            </div>

            <div class="flex flex-col sm:flex-row gap-6 justify-center items-center mt-12 animate-fade-in-up">
                <Link :href="route('login')" 
                    class="px-8 py-4 w-48 bg-slate-800 hover:bg-slate-700 text-white font-bold rounded-lg border border-slate-600 transition-all hover:scale-105 hover:border-slate-400 shadow-xl">
                    Login
                </Link>
                <Link :href="route('register')" 
                    class="px-8 py-4 w-48 bg-red-600 hover:bg-red-500 text-white font-bold rounded-lg transition-all hover:scale-105 hover:shadow-[0_0_20px_rgba(220,38,38,0.5)] shadow-xl">
                    Begin Journey
                </Link>
            </div>
            
            <div class="mt-20 grid grid-cols-1 md:grid-cols-3 gap-8 text-left opacity-80">
                <div class="p-6 bg-slate-900/50 rounded border border-slate-800 backdrop-blur-sm">
                    <h3 class="text-lg font-bold text-white mb-2">Tickless Combat</h3>
                    <p class="text-sm text-slate-400">Time-based simulation engine where every millisecond counts against your dexterity.</p>
                </div>
                <div class="p-6 bg-slate-900/50 rounded border border-slate-800 backdrop-blur-sm">
                    <h3 class="text-lg font-bold text-white mb-2">Dynamic Loot</h3>
                    <p class="text-sm text-slate-400">Billions of item combinations with randomized bonuses and rarity tiers.</p>
                </div>
                <div class="p-6 bg-slate-900/50 rounded border border-slate-800 backdrop-blur-sm">
                    <h3 class="text-lg font-bold text-white mb-2">Persistent World</h3>
                    <p class="text-sm text-slate-400">Your character's journey continues even when you are offline.</p>
                </div>
            </div>

        </div>
    </div>
</template>

<style>
.animate-fade-in-down {
    animation: fadeInDown 1s ease-out;
}
.animate-fade-in-up {
    animation: fadeInUp 1s ease-out 0.5s backwards;
}

@keyframes fadeInDown {
    from { opacity: 0; transform: translateY(-20px); }
    to { opacity: 1; transform: translateY(0); }
}
@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
