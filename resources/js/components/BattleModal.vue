<script setup lang="ts">
import { ref, computed, watch, onUnmounted } from 'vue';
import { usePlayerStore } from '../stores/usePlayerStore';

const store = usePlayerStore();

// --- State ---
const isPlaying = ref(false);
const playbackSpeed = ref(1);
const currentTick = ref(0);
const battleEvents = ref<any[]>([]); // Copy of log
const processedEventIndex = ref(0);
const floatingTexts = ref<any[]>([]);
const logEnd = ref<HTMLElement | null>(null);

// Participant State (Visual)
const heroState = ref({
    hp: 1,
    maxHp: 1,
    shaking: false
});
const enemyState = ref({
    hp: 1,
    maxHp: 1, // Will be set from first hit target max hp or passed prop?
    // We don't have max hp in log usually unless we parse it. 
    // Wait, log contains `target_hp` after damage. Initial Max HP is missing in log.
    // We can infer max HP from the first state or pass it. 
    // Let's assume we start full.
    // Log doesn't give MaxHP. Adapter: `activeBattle.participants` should have it?
    // Store updates needed to pass monster info.
    name: 'Enemy',
    shaking: false
});

const showRewards = ref(false);

// --- Computed ---
const isOpen = computed(() => store.showBattleModal);
const battleData = computed(() => store.activeBattle);

// --- Watchers ---
watch(isOpen, (newVal) => {
    if (newVal && battleData.value) {
        initBattle();
    } else {
        stopPlayback();
    }
});

// --- Methods ---
function initBattle() {
    isPlaying.value = true;
    currentTick.value = 0;
    processedEventIndex.value = 0;
    showRewards.value = false;
    floatingTexts.value = [];

    // Init Hero
    const hero = battleData.value.participants.hero;
    heroState.value = {
        hp: hero.stats.computed_stats.max_hp || 100,
        maxHp: hero.stats.computed_stats.max_hp || 100,
        shaking: false
    };

    // Init Enemy
    // We might need to estimate Enemy Max HP from log if not provided.
    // Or just set arbitrary for visuals if missing.
    // Let's look for the first event targeting enemy and see hp before damage?
    // Or just use 100% relative bar.
    enemyState.value = {
        hp: 100,
        maxHp: 100, // Placeholder
        name: 'Unknown Beast',
        shaking: false
    };

    // Look for first monster interaction to get name/hp?
    const firstEnemyTarget = battleData.value.log.find((e: any) => e.defender_id !== hero.id);
    if (firstEnemyTarget) {
         // This assumes we can deduce data. 
         // Better: Update store to accept 'monster' object in participants.
         // For now, we will live with reactive updates from log.
         // If `target_hp` is in log, we can assume that is "current".
         // Let's assume the first event targeting enemy reveals its "current" HP + damage taken = Max HP roughly?
         // This is imperfect.
    }
    
    // Sort log just in case
    battleEvents.value = [...battleData.value.log].sort((a, b) => a.tick - b.tick);
    
    // Start Loop
    lastFrameTime = performance.now();
    requestAnimationFrame(gameLoop);
}

let lastFrameTime = 0;
const TIME_SCALAR = 1; // Base speed

function gameLoop(timestamp: number) {
    if (!isPlaying.value) return;

    const dt = timestamp - lastFrameTime;
    lastFrameTime = timestamp;

    const deltaTick = dt * playbackSpeed.value;
    currentTick.value += deltaTick;

    // Process Events
    while (
        processedEventIndex.value < battleEvents.value.length &&
        battleEvents.value[processedEventIndex.value].tick <= currentTick.value
    ) {
        const event = battleEvents.value[processedEventIndex.value];
        applyEvent(event);
        processedEventIndex.value++;
    }

    // Check End
    if (processedEventIndex.value >= battleEvents.value.length) {
        // Wait a sec then show rewards
        setTimeout(() => {
            endBattle();
        }, 1000);
        return;
    }

    requestAnimationFrame(gameLoop);
}

function applyEvent(event: any) {
    // 1. FCT
    if (event.type === 'hit' || event.type === 'crit' || event.type === 'miss') {
        spawnFCT(event);
    }

    // 2. Shake & HP Update
    const isHeroTarget = event.defender_id === battleData.value?.participants.hero.id;
    
    if (isHeroTarget) {
        heroState.value.hp = event.target_hp; // Update to exact server value
        triggerShake(heroState);
    } else {
        enemyState.value.hp = event.target_hp;
        // If we didn't know max HP, we might adjust bar? 
        // Let's just track raw HP.
        triggerShake(enemyState);
    }

    // 3. Death
    if (event.type === 'death') {
        // Animation?
    }

    // Scroll Log
    setTimeout(() => {
        logEnd.value?.scrollIntoView({ behavior: 'smooth' });
    }, 10);
}

function spawnFCT(event: any) {
    const isHeroTarget = event.defender_id === battleData.value?.participants.hero.id;
    const text = event.type === 'miss' ? 'Miss' : event.damage.toString();
    const type = event.type; // hit, crit, miss
    
    // ID for component key
    const id = Date.now() + Math.random();
    
    floatingTexts.value.push({
        id,
        text,
        type,
        side: isHeroTarget ? 'left' : 'right', // side of the TARGET
        x: isHeroTarget ? 30 : 70, // percent
        y: 40 // percent
    });

    // Cleanup after animation
    setTimeout(() => {
        floatingTexts.value = floatingTexts.value.filter(t => t.id !== id);
    }, 1000);
}

function triggerShake(targetRef: any) {
    targetRef.value.shaking = true;
    setTimeout(() => targetRef.value.shaking = false, 300);
}

function stopPlayback() {
    isPlaying.value = false;
}

function endBattle() {
    isPlaying.value = false;
    showRewards.value = true;
}

function close() {
    store.closeBattleModal();
}

function skip() {
    playbackSpeed.value = 100; // Hyper speed
}
</script>

<template>
    <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm">
        <div class="relative w-full max-w-4xl p-6 bg-slate-900 border border-slate-700 rounded-xl shadow-2xl overflow-hidden min-h-[500px] flex flex-col">
            
            <!-- Header -->
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-white font-serif">Combat Log</h2>
                <div class="space-x-2">
                    <button v-if="!showRewards" @click="playbackSpeed = playbackSpeed === 1 ? 2 : 1" class="px-3 py-1 bg-slate-800 rounded text-xs hover:bg-slate-700 font-mono">
                        {{ playbackSpeed }}x Speed
                    </button>
                    <button v-if="!showRewards" @click="skip" class="px-3 py-1 bg-indigo-600 rounded text-xs hover:bg-indigo-500 font-bold">
                        Skip
                    </button>
                </div>
            </div>

            <!-- Battlefield -->
            <div class="relative flex-1 flex gap-4 min-h-0">
                <!-- Visual Field -->
                <div class="flex-1 relative flex justify-between items-center px-12 bg-[url('/assets/bg-combat.png')] bg-cover bg-center rounded-lg border border-slate-800">
                    
                    <!-- Hero -->
                    <div class="relative flex flex-col items-center gap-2 transition-transform" :class="{ 'animate-shake': heroState.shaking }">
                        <div class="w-24 h-24 rounded-full border-4 border-indigo-500 bg-slate-800 flex items-center justify-center overflow-hidden shadow-[0_0_20px_rgba(99,102,241,0.5)]">
                            <span class="text-4xl">🧘</span>
                        </div>
                        <div class="w-32 h-4 bg-slate-900 rounded-full border border-slate-700 overflow-hidden relative">
                            <div class="h-full bg-red-600 transition-all duration-300" :style="{ width: (heroState.hp / heroState.maxHp * 100) + '%' }"></div>
                        </div>
                        <span class="text-white font-bold">{{ heroState.hp }} / {{ heroState.maxHp }}</span>
                    </div>

                    <!-- VS -->
                    <div class="text-4xl font-black text-white/20 italic">VS</div>

                    <!-- Enemy -->
                    <div class="relative flex flex-col items-center gap-2 transition-transform" :class="{ 'animate-shake': enemyState.shaking }">
                        <div class="w-32 h-4 bg-slate-900 rounded-full border border-slate-700 overflow-hidden relative">
                             <!-- Hacky max HP logic: Set width relative to arbitrary max if 100 -->
                            <div class="h-full bg-red-600 transition-all duration-300" :style="{ width: Math.min(100, Math.max(0, enemyState.hp)) + '%' }"></div>
                        </div>
                        <span class="text-white font-bold">{{ enemyState.hp }} HP</span>
                         <div class="w-24 h-24 rounded-full border-4 border-red-500 bg-slate-800 flex items-center justify-center overflow-hidden shadow-[0_0_20px_rgba(239,68,68,0.5)]">
                            <span class="text-4xl">🐺</span>
                        </div>
                    </div>

                    <!-- Floating Texts -->
                    <div class="absolute inset-0 pointer-events-none">
                        <div v-for="fct in floatingTexts" :key="fct.id" 
                            class="absolute transition-all duration-1000 ease-out flex flex-col items-center"
                            :style="{ left: fct.x + '%', top: fct.y + '%' }"
                        >
                             <span class="fct-anim text-2xl font-black z-50 drop-shadow-md"
                                :class="{
                                    'text-yellow-400 text-4xl': fct.type === 'crit',
                                    'text-red-500': fct.type === 'hit',
                                    'text-gray-400 text-lg': fct.type === 'miss'
                                }"
                             >
                                {{ fct.text }}
                             </span>
                        </div>
                    </div>
                </div>

                <!-- Text Log Side Panel -->
                <div class="w-64 bg-slate-950/90 border border-slate-800 rounded-lg p-4 flex flex-col overflow-hidden">
                    <h3 class="text-sm font-bold text-slate-400 mb-2 uppercase tracking-wider">Combat Log</h3>
                    <div class="flex-1 overflow-y-auto space-y-1 text-xs font-mono scrollbar-hide" ref="logContainer">
                        <div v-for="(event, i) in battleEvents.slice(0, processedEventIndex)" :key="i" class="text-slate-300 border-b border-slate-800/50 pb-1">
                             <span :class="event.attacker_id === battleData?.participants.hero.id ? 'text-indigo-400' : 'text-red-400'">
                                 {{ event.attacker_id === battleData?.participants.hero.id ? 'Hero' : 'Enemy' }}
                             </span>
                             <span v-if="event.type === 'hit'"> hits for <span class="text-white">{{ event.damage }}</span> dmg.</span>
                             <span v-if="event.type === 'crit'"> crits for <span class="text-yellow-400">{{ event.damage }}</span>!</span>
                             <span v-if="event.type === 'miss'"> misses.</span>
                             <span v-if="event.type === 'death'" class="text-red-600 block pt-1">Target died.</span>
                        </div>
                        <div ref="logEnd"></div>
                    </div>
                </div>
            </div>

            <!-- Reward Overlay -->
            <div v-if="showRewards" class="absolute inset-0 z-50 bg-black/90 flex flex-col items-center justify-center gap-6 animate-fade-in p-8">
                <h3 class="text-4xl font-bold text-yellow-500 font-serif mb-4">Victory!</h3>
                
                <div class="grid grid-cols-2 gap-8 text-center w-full max-w-lg">
                    <div class="p-4 bg-slate-800/50 rounded border border-slate-700">
                        <div class="text-sm text-slate-400">Experience</div>
                        <div class="text-3xl font-bold text-indigo-400">+{{ battleData.rewards.exp }}</div>
                    </div>
                    <div class="p-4 bg-slate-800/50 rounded border border-slate-700">
                        <div class="text-sm text-slate-400">Gold</div>
                        <div class="text-3xl font-bold text-yellow-400">+{{ battleData.rewards.gold }}</div>
                    </div>
                </div>

                <!-- Loot -->
                <div v-if="battleData.rewards.items.length" class="flex gap-4 mt-4">
                    <div v-for="item in battleData.rewards.items" :key="item.id" 
                         class="w-16 h-16 bg-slate-800 border-2 border-green-500 rounded flex items-center justify-center relative group cursor-help">
                         <span class="text-2xl">⚔️</span>
                         <!-- Tooltip -->
                         <div class="absolute bottom-full mb-2 hidden group-hover:block w-48 bg-black border border-slate-600 p-2 text-xs rounded z-50">
                            {{ item.item_template_id }} <!-- Needs name from template, need to persist name in rewards or use generic -->
                         </div>
                    </div>
                </div>

                <button @click="close" class="mt-8 px-8 py-3 bg-indigo-600 hover:bg-indigo-500 text-white font-bold rounded-lg shadow-lg border-t border-indigo-400">
                    Collect & Close
                </button>
            </div>
            
        </div>
    </div>
</template>

<style scoped>
.animate-shake {
    animation: shake 0.3s cubic-bezier(.36,.07,.19,.97) both;
}

@keyframes shake {
  10%, 90% { transform: translate3d(-1px, 0, 0); }
  20%, 80% { transform: translate3d(2px, 0, 0); }
  30%, 50%, 70% { transform: translate3d(-4px, 0, 0); }
  40%, 60% { transform: translate3d(4px, 0, 0); }
}

.fct-anim {
    animation: floatUp 1s ease-out forwards;
}

@keyframes floatUp {
    0% { transform: translateY(0) scale(1); opacity: 1; }
    50% { transform: translateY(-50px) scale(1.2); opacity: 1; }
    100% { transform: translateY(-100px) scale(1); opacity: 0; }
}

.animate-fade-in {
    animation: fadeIn 0.5s ease-out;
}

@keyframes fadeIn {
    from { opacity: 0; transform: scale(0.9); }
    to { opacity: 1; transform: scale(1); }
}
</style>
