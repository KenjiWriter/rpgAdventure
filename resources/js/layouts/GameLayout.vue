<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { usePlayerStore } from '../stores/usePlayerStore';
import { Link, router } from '@inertiajs/vue3';
import { Shield, Map as MapIcon, User, Package, ShoppingBag, Scroll, Loader2, AlertCircle } from 'lucide-vue-next';
import BattleModal from '../components/BattleModal.vue';

const store = usePlayerStore();

const characterName = computed(() => store.character?.name || 'Loading...');
const level = computed(() => store.character?.level || 1);
const gold = computed(() => store.character?.gold || 0);
const expPercent = computed(() => store.experiencePercent);
const maxHp = computed(() => store.maxHp);
const maxMana = computed(() => store.maxMana);

// Mission Timer Logic
const missionTimeLeft = ref(0);
let missionInterval: any = null;

const activeMission = computed(() => store.activeMission);

// Watch for mission changes to start/stop timer handled in layout to be global
// Or just reactively update from store if store handles the timer? 
// Store just holds data. Layout handles UI timer.
import { watch } from 'vue';

watch(activeMission, (newMission) => {
    if (newMission) {
        startTimer(newMission.ends_at);
        // If on Map page, and mission starts, redirect? 
        // User requested: "If a player clicks "Map" while a mission is active, automatically redirected to the active mission view" -> This implies limiting nav or handling inside Map view.
    } else {
        stopTimer();
    }
}, { immediate: true });

function startTimer(endsAtStr: string) {
    stopTimer();
    const endsAt = new Date(endsAtStr).getTime();
    
    // Initial update
    const now = Date.now();
    const diff = endsAt - now;
    missionTimeLeft.value = Math.max(0, Math.ceil(diff / 1000));
    updateDocumentTitle();

    missionInterval = setInterval(() => {
        const now = Date.now();
        const diff = endsAt - now;
        missionTimeLeft.value = Math.max(0, Math.ceil(diff / 1000));
        updateDocumentTitle();
        
        if (missionTimeLeft.value <= 0) {
            stopTimer(); // Timer done, wait for claim
        }
    }, 1000);
}

function stopTimer() {
    if (missionInterval) clearInterval(missionInterval);
    missionTimeLeft.value = 0;
    updateDocumentTitle();
}


    function updateDocumentTitle() {
        if (activeMission.value) {
            if (missionTimeLeft.value > 0) {
                const minutes = Math.floor(missionTimeLeft.value / 60).toString().padStart(2, '0');
                const seconds = (missionTimeLeft.value % 60).toString().padStart(2, '0');
                document.title = `RPG Game | [${minutes}:${seconds}] In Combat`;
            } else {
                 document.title = `RPG Game | [READY] Battle!`;
            }
        } else {
            document.title = 'RPG Game | Dashboard';
        }
    }
    
    const missionReady = computed(() => !!activeMission.value && missionTimeLeft.value <= 0);

    // Navigation Interception
    function handleNav(href: string) {
        if (activeMission.value && href.includes('map')) {
            // Logic placeholder
        }
    }
    
    // Tutorial Pulse Logic
    const showMerchantPulse = computed(() => {
        return store.character?.gold === 100 && (store.equipment.length === 0 || store.equipment.length === undefined); 
    });

    const isMerchantFresh = computed(() => {
        if (!store.merchantExpiry) return false;
        // If expiry > 55 mins from now, it was refreshed in last 5 mins
        const expiry = new Date(store.merchantExpiry).getTime();
        const now = Date.now();
        const diff = expiry - now;
        // 60 mins = 3600000 ms. 
        // If diff > 55 mins (3300000), then it's fresh.
        return diff > 3300000;
    });

    const navItems = [
        { name: 'Dashboard', icon: User, href: route('home') },
        { name: 'Map', icon: MapIcon, href: route('map') },
        { name: 'Quests', icon: Scroll, href: route('quests') },
        { name: 'Merchant', icon: ShoppingBag, href: route('merchant.index') },
    ];
    </script>
    
<template>
    <div class="min-h-screen bg-slate-950 text-zinc-100 font-sans selection:bg-red-900 selection:text-white">
        <!-- Top Bar -->
        <header class="sticky top-0 z-50 border-b border-slate-800 bg-slate-900/90 backdrop-blur-md shadow-lg">
            <div class="container mx-auto px-4 h-16 flex items-center justify-between">
                
                <!-- Player Info -->
                <div class="flex items-center gap-4">
                    <div class="relative group">
                        <div class="w-12 h-12 rounded-full bg-slate-800 border-2 border-slate-600 overflow-hidden shadow-inner">
                            <!-- Avatar Placeholder -->
                             <img src="https://api.dicebear.com/9.x/adventurer/svg?seed=Felix" alt="Avatar" class="w-full h-full object-cover" />
                        </div>
                        <div class="absolute -bottom-1 -right-1 w-6 h-6 bg-amber-600 rounded-full flex items-center justify-center text-xs font-bold border-2 border-slate-900">
                            {{ level }}
                        </div>
                    </div>
                    
                    <div class="flex flex-col">
                        <span class="font-bold text-lg tracking-wide text-zinc-100">{{ characterName }}</span>
                        <span class="text-xs text-amber-400 font-mono">{{ gold.toLocaleString() }} Gold</span>
                    </div>
                </div>

                <!-- Bars -->
                <div class="flex-1 max-w-xl mx-8 flex flex-col gap-2">
                     <!-- EXP Bar -->
                    <div class="relative h-2 w-full bg-slate-800 rounded-full overflow-hidden">
                        <div 
                            class="absolute top-0 left-0 h-full bg-gradient-to-r from-violet-600 to-indigo-400 transition-all duration-500 ease-out shadow-[0_0_10px_rgba(139,92,246,0.5)]"
                            :style="{ width: `${expPercent}%` }"
                        ></div>
                        <span class="absolute top-0 w-full text-center text-[8px] leading-3 text-white/50 font-mono tracking-widest mix-blend-plus-lighter">EXP</span>
                    </div>

                    <div class="flex gap-2">
                        <!-- HP Bar -->
                         <div class="relative h-4 flex-1 bg-slate-800 rounded-sm overflow-hidden border border-slate-700/50">
                            <div 
                                class="absolute top-0 left-0 h-full bg-gradient-to-r from-red-900 to-red-600 transition-all duration-300 ease-out"
                                :style="{ width: '100%' }" 
                            ></div>
                             <!-- Using 100% for now as current_hp not yet tracked separately from max, prompt implied use computed stats for max, but current HP usually variable. assuming full for UI demo -->
                            <span class="absolute inset-0 flex items-center justify-center text-[10px] font-bold text-white drop-shadow-md z-10 font-mono">
                                {{ maxHp }} / {{ maxHp }} HP
                            </span>
                        </div>

                        <!-- Mana Bar -->
                        <div class="relative h-4 flex-1 bg-slate-800 rounded-sm overflow-hidden border border-slate-700/50">
                            <div 
                                class="absolute top-0 left-0 h-full bg-gradient-to-r from-blue-900 to-blue-600 transition-all duration-300 ease-out"
                                :style="{ width: '100%' }"
                            ></div>
                             <span class="absolute inset-0 flex items-center justify-center text-[10px] font-bold text-white drop-shadow-md z-10 font-mono">
                                {{ maxMana }} / {{ maxMana }} MP
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Right Actions -->
                <div class="flex gap-2">
                    <button class="p-2 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white transition-colors">
                        <Shield class="w-5 h-5" />
                    </button>
                </div>
            </div>
        </header>

        <div class="flex h-[calc(100vh-64px)] overflow-hidden">
            <!-- Sidebar -->
            <aside class="w-20 bg-slate-900 border-r border-slate-800 flex flex-col items-center py-6 gap-6 z-40">
                    <Link 
                        v-for="item in navItems" 
                        :key="item.name"
                        :href="item.href"
                        class="group relative flex items-center justify-center w-12 h-12 rounded-xl transition-all duration-200 text-slate-400 hover:bg-indigo-600 hover:text-white hover:shadow-lg hover:shadow-indigo-500/30"
                        :class="{ 
                            'bg-indigo-600 text-white shadow-lg shadow-indigo-500/30': route().current() === item.href.split('/').pop(),
                            'animate-pulse ring-2 ring-amber-400 ring-offset-2 ring-offset-slate-900': item.name === 'Merchant' && showMerchantPulse
                        }" 
                    >
                    <component :is="item.icon" class="w-6 h-6" />
                    
                    <!-- Sidebar Badges -->
                    <div v-if="item.name === 'Map' && activeMission" 
                         class="absolute -top-1 -right-1 w-5 h-5 rounded-full flex items-center justify-center text-[10px] font-bold border-2 border-slate-900"
                         :class="missionReady ? 'bg-red-500 text-white animate-bounce' : 'bg-indigo-400 text-white'">
                        <span v-if="missionReady">!</span>
                        <span v-else>{{ missionTimeLeft }}</span>
                    </div>

                    <div v-if="item.name === 'Merchant' && isMerchantFresh"
                         class="absolute -top-1 -right-1 w-fit px-1 h-5 rounded-full flex items-center justify-center text-[10px] font-bold border-2 border-slate-900 bg-emerald-500 text-white animate-pulse">
                        NEW
                    </div>

                    <span class="absolute left-14 bg-slate-800 text-white text-xs px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none whitespace-nowrap border border-slate-700">
                        {{ item.name }}
                    </span>
                </Link>
            </aside>

            <!-- Main Content Area -->
            <main class="flex-1 overflow-auto bg-[url('https://images.unsplash.com/photo-1605806616949-1e87b487bc2a?q=80&w=2574&auto=format&fit=crop')] bg-cover bg-center bg-no-repeat relative">
                <!-- Overlay Gradient -->
                 <div class="absolute inset-0 bg-slate-950/80 backdrop-blur-sm z-0"></div>
                 
                 <div class="relative z-10 p-8 container mx-auto">
                    <slot />
                 </div>
            </main>
        </div>
        
        <BattleModal />
    </div>
</template>
