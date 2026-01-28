<script setup lang="ts">
import { ref, computed, watch, onUnmounted } from 'vue';
import { usePlayerStore } from '../stores/usePlayerStore';
import ItemTooltip from './ItemTooltip.vue';



const store = usePlayerStore();

// --- State ---
const isPlaying = ref(false);
const playbackSpeed = ref(1);
const currentTick = ref(0);
const showVs = ref(false);
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
const isVictory = computed(() => battleData.value?.winnerId === store.character?.id);

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
    showVs.value = true; // Show VS initially
    floatingTexts.value = [];

    // Init Hero
    const hero = battleData.value.participants.hero;
    heroState.value = {
        hp: hero.stats.computed_stats.max_hp || 100,
        maxHp: hero.stats.computed_stats.max_hp || 100,
        shaking: false
    };

    // Init Enemy
    const monster = battleData.value.participants.monster;
    enemyState.value = {
        hp: monster?.max_hp || 100,
        maxHp: monster?.max_hp || 100,
        name: monster?.name || 'Monster',
        shaking: false
    };

    // Sort log just in case
    battleEvents.value = [...battleData.value.log].sort((a, b) => a.tick - b.tick);
    
    // Start Loop
    lastFrameTime = performance.now();
    
    // VS Splash logic
    showVs.value = true;
    setTimeout(() => {
        showVs.value = false;
        requestAnimationFrame(gameLoop);
    }, 2000); 
}

let lastFrameTime = 0;

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
        setTimeout(() => {
            endBattle();
        }, 1000);
        return;
    }

    if (processedEventIndex.value < battleEvents.value.length) {
        requestAnimationFrame(gameLoop);
    }
}

function getAtbProgress(actorId: number | 'enemy') {
    // Determine the ID to look for
    const specificId = actorId === 'enemy' ? null : actorId; // Hero ID passed, enemy is finding diff
    
    // Find previous action tick for this actor
    // This is expensive to do every frame if list is huge, but list is small (<50 events).
    // Better: Cache next action tick.
    
    // Simple approach:
    // look backward from current event index to find LAST action tick.
    // look forward to find NEXT action tick.
    // progress = (currentTick - last) / (next - last)
    
    let lastTick = 0;
    let nextTick = 100; // arbitrary future if none

    const heroId = battleData.value?.participants.hero.id;
    const isEnemy = actorId === 'enemy';

    // Find Next
    const nextAction = battleEvents.value.slice(processedEventIndex.value).find(e => {
        if (isEnemy) return e.attacker_id !== heroId;
        return e.attacker_id === heroId;
    });

    if (nextAction) {
        nextTick = nextAction.tick;
    } else {
        // No more actions, full bar?
        return 100;
    }

    // Find Last
    // We search from 0 to processedIndex
    const prevActions = battleEvents.value.slice(0, processedEventIndex.value).filter(e => {
        if (isEnemy) return e.attacker_id !== heroId;
        return e.attacker_id === heroId;
    });
    
    if (prevActions.length > 0) {
        lastTick = prevActions[prevActions.length - 1].tick;
    }

    const totalDuration = nextTick - lastTick;
    if (totalDuration <= 0) return 0;

    const elapsed = currentTick.value - lastTick;
    return Math.min(100, Math.max(0, (elapsed / totalDuration) * 100));
}

function applyEvent(event: any) {
    if (event.type === 'hit' || event.type === 'crit' || event.type === 'miss') {
        spawnFCT(event);
    }

    const isHeroTarget = event.defender_id === battleData.value?.participants.hero.id;
    
    if (isHeroTarget) {
        heroState.value.hp = event.target_hp; 
        triggerShake(heroState);
    } else {
        enemyState.value.hp = event.target_hp;
        triggerShake(enemyState);
    }

    if (event.type === 'death') {
        const isHeroDead = event.defender_id ? 
            event.defender_id === battleData.value?.participants.hero.id :
            event.target === battleData.value?.participants.hero.name; // Fallback

        if (isHeroDead) {
            heroState.value.hp = 0;
        } else {
            enemyState.value.hp = 0;
        }
    }

    setTimeout(() => {
        logEnd.value?.scrollIntoView({ behavior: 'smooth' });
    }, 10);
}

function spawnFCT(event: any) {
    const isHeroTarget = event.defender_id === battleData.value?.participants.hero.id;
    const text = event.type === 'miss' ? 'Miss' : event.damage.toString();
    const type = event.type; 
    
    const id = Date.now() + Math.random();
    
    floatingTexts.value.push({
        id,
        text,
        type,
        side: isHeroTarget ? 'left' : 'right',
        x: isHeroTarget ? 30 : 70, 
        y: 40 
    });

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
    playbackSpeed.value = 1000; // Hyper speed
}

function getHpPercentage(current: number, max: number) {
    if (max <= 0) return 100; // Fallback to full if invalid
    const pct = (current / max) * 100;
    return Math.max(0, Math.min(100, pct));
}
</script>

<template>
    <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm p-4">
        <div class="relative w-full max-w-5xl h-[85vh] bg-slate-900 border border-slate-700 rounded-xl shadow-2xl overflow-hidden flex flex-col">
            
            <!-- Header -->
            <div class="flex justify-between items-center p-4 border-b border-slate-700 bg-slate-900/50">
                <h2 class="text-2xl font-bold text-white font-serif flex items-center gap-2">
                    <span>⚔️</span> Combat Log
                </h2>
                <div class="space-x-2">
                    <button v-if="!showRewards" @click="playbackSpeed = playbackSpeed === 1 ? 2 : 1" class="px-3 py-1 bg-slate-800 rounded text-xs hover:bg-slate-700 font-mono transition-colors border border-slate-700 text-slate-300">
                        {{ playbackSpeed }}x Speed
                    </button>
                    <button v-if="!showRewards" @click="skip" class="px-3 py-1 bg-indigo-600 rounded text-xs hover:bg-indigo-500 font-bold transition-colors shadow-lg shadow-indigo-500/20 text-white">
                        Skip
                    </button>
                </div>
            </div>

            <!-- Battlefield -->
            <div class="relative flex-1 flex gap-0 min-h-0">
                <!-- Visual Field -->
                <div class="flex-[2] relative flex flex-col justify-center items-center bg-cover bg-center border-r border-slate-800" :style="{ backgroundImage: 'url(/bg-combat.png)' }">
                    <!-- Overlay for text clarity -->
                    <div class="absolute inset-0 bg-slate-950/30"></div>

                    <div class="relative z-10 flex justify-around w-full items-center px-12">
                    <div class="relative flex flex-col items-center gap-2 transition-transform" :class="{ 'animate-shake': heroState.shaking }">
                        <div class="w-24 h-24 rounded-full border-4 border-indigo-500 bg-slate-800 flex items-center justify-center overflow-hidden shadow-[0_0_20px_rgba(99,102,241,0.5)] z-10">
                            <span class="text-4xl">🧘</span>
                        </div>
                        
                        <!-- HP Bar -->
                        <div class="w-32 h-4 bg-slate-900 rounded-full border border-slate-700 overflow-hidden relative shadow-inner">
                            <div class="h-full bg-gradient-to-r from-red-600 to-red-500 transition-all duration-300" 
                                 :style="{ width: getHpPercentage(heroState.hp, heroState.maxHp) + '%' }"></div>
                            <span class="absolute inset-0 flex items-center justify-center text-[10px] font-bold text-white shadow-black drop-shadow-md">
                                {{ heroState.hp }} / {{ heroState.maxHp }}
                            </span>
                        </div>

                         <!-- ATB Bar -->
                        <div class="w-24 h-1.5 bg-slate-900 rounded-full border border-slate-700 overflow-hidden relative mt-1">
                            <div class="h-full bg-yellow-400 shadow-[0_0_5px_rgba(250,204,21,0.8)] transition-all duration-100 ease-linear"
                                 :style="{ width: getAtbProgress(battleData?.participants.hero.id) + '%' }"></div>
                        </div>
                    </div>

                    <!-- VS -->
                    <div class="flex flex-col items-center">
                         <div class="text-4xl font-black text-white/20 italic">VS</div>
                    </div>

                    <!-- Enemy -->
                    <div class="relative flex flex-col items-center gap-2 transition-transform" :class="{ 'animate-shake': enemyState.shaking }">
                         <!-- ATB Bar (Top for enemy or bottom? consistency -> bottom) -->
                        
                        <div class="w-32 h-4 bg-slate-900 rounded-full border border-slate-700 overflow-hidden relative shadow-inner order-2">
                             <div class="h-full bg-gradient-to-r from-red-600 to-red-500 transition-all duration-300" 
                                  :style="{ width: getHpPercentage(enemyState.hp, enemyState.maxHp) + '%' }"></div>
                             <span class="absolute inset-0 flex items-center justify-center text-[10px] font-bold text-white shadow-black drop-shadow-md">
                                {{ enemyState.hp }} / {{ enemyState.maxHp }}
                             </span>
                        </div>

                        <!-- ATB -->
                        <div class="w-24 h-1.5 bg-slate-900 rounded-full border border-slate-700 overflow-hidden relative mt-1 order-3">
                            <div class="h-full bg-yellow-400 shadow-[0_0_5px_rgba(250,204,21,0.8)] transition-all duration-100 ease-linear"
                                 :style="{ width: getAtbProgress('enemy') + '%' }"></div>
                        </div>

                         <div class="w-24 h-24 rounded-full border-4 border-red-500 bg-slate-800 flex items-center justify-center overflow-hidden shadow-[0_0_20px_rgba(239,68,68,0.5)] z-10 order-1">
                            <span class="text-4xl">🐺</span>
                        </div>
                        <div class="absolute -top-6 text-red-500 font-bold text-sm bg-black/50 px-2 rounded border border-red-500/30">
                            {{ enemyState.name }}
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
                </div> <!-- End inner wrapper -->
                </div> <!-- End visual field -->

                <!-- Text Log Side Panel -->
                <div class="w-72 bg-slate-950/90 border-l border-slate-700 flex flex-col shrink-0">
                    <div class="p-4 border-b border-slate-800 bg-slate-900/50">
                        <h3 class="text-sm font-bold text-slate-400 uppercase tracking-wider flex items-center gap-2">
                            <span>📜</span> Event Log
                        </h3>
                    </div>
                    <div class="flex-1 overflow-y-auto p-4 space-y-1 text-xs font-mono scrollbar-thin scrollbar-thumb-slate-700 scrollbar-track-slate-900" ref="logContainer">
                        <div v-for="(event, i) in battleEvents.slice(0, processedEventIndex)" :key="i" class="border-b border-slate-800/50 pb-1 last:border-0 animation-slide-in">
                             <span :class="event.attacker_id === battleData?.participants.hero.id ? 'text-green-400' : 'text-red-400'" class="font-bold">
                                 {{ event.attacker_id === battleData?.participants.hero.id ? 'Hero' : enemyState.name }}
                             </span>
                             <span v-if="event.type === 'hit'" :class="event.attacker_id === battleData?.participants.hero.id ? 'text-green-200' : 'text-red-200'">
                                 hits for <span class="font-bold">{{ event.damage }}</span>
                             </span>
                             <span v-if="event.type === 'crit'" class="text-yellow-400 font-bold">
                                 CRITS for {{ event.damage }}!
                             </span>
                             <span v-if="event.type === 'miss'" class="text-slate-500 italic"> misses.</span>
                             <span v-if="event.type === 'death'" class="text-red-600 block pt-1 font-bold">Target defeated.</span>
                        </div>
                        <div ref="logEnd"></div>
                    </div>
                </div>
            </div>

            <!-- Reward Overlay -->
            <div v-if="showRewards" class="absolute inset-0 z-50 bg-black/95 flex flex-col items-center justify-center gap-6 animate-fade-in p-8"
                :class="isVictory ? 'bg-black/90' : 'bg-red-950/90'">
                
                <h3 v-if="isVictory" class="text-5xl font-bold text-yellow-500 font-serif mb-4 drop-shadow-glow">Victory!</h3>
                <h3 v-else class="text-5xl font-bold text-red-600 font-serif mb-4 drop-shadow-md">Defeat</h3>
                
                <div v-if="isVictory" class="grid grid-cols-2 gap-8 text-center w-full max-w-lg">
                    <div class="p-4 bg-slate-800/50 rounded border border-slate-700">
                        <div class="text-sm text-slate-400">Experience</div>
                        <div class="text-3xl font-bold text-indigo-400">+{{ battleData.rewards.exp }}</div>
                    </div>
                    <div class="p-4 bg-slate-800/50 rounded border border-slate-700">
                        <div class="text-sm text-slate-400">Gold</div>
                        <div class="text-3xl font-bold text-yellow-400">+{{ battleData.rewards.gold }}</div>
                    </div>
                </div>
                <div v-else class="text-slate-400 text-lg">
                    You have fallen in battle...<br>
                    <span class="text-sm text-slate-500">No rewards earned.</span>
                </div>

                <!-- Loot -->
                <div v-if="isVictory && battleData.rewards.items.length" class="flex gap-4 mt-4">
                    <div v-for="item in battleData.rewards.items" :key="item.id" 
                         class="w-16 h-16 bg-slate-800 border-2 border-green-500 rounded flex items-center justify-center relative group cursor-help">
                         <span class="text-2xl">⚔️</span>
                         <!-- Tooltip -->
                         <ItemTooltip :item="item" class="hidden group-hover:block" />
                    </div>
                </div>

                <button @click="close" class="mt-8 px-8 py-3 font-bold rounded-lg shadow-lg border-t"
                    :class="isVictory ? 'bg-indigo-600 hover:bg-indigo-500 text-white border-indigo-400' : 'bg-slate-700 hover:bg-slate-600 text-slate-300 border-slate-600'">
                    {{ isVictory ? 'Collect & Close' : 'Close' }}
                </button>
            </div>
            
            <!-- VS Overlay -->
            <div v-if="showVs" class="absolute inset-0 z-50 bg-black flex flex-col items-center justify-center animate-pulse">
                 <h1 class="text-6xl font-black text-red-600 italic tracking-tighter drop-shadow-[0_0_15px_rgba(220,38,38,0.8)] scale-150 transform transition-transform duration-500">
                     VS
                 </h1>
                 <div class="mt-8 flex items-center gap-12 text-white">
                      <div class="text-right">
                          <div class="text-3xl font-bold">{{ battleData?.participants.hero.name }}</div>
                          <div class="text-slate-400">Level {{ battleData?.participants.hero.level }} Hero</div>
                      </div>
                      <div class="h-16 w-1 bg-white/20"></div>
                      <div class="text-left">
                          <div class="text-3xl font-bold text-red-500">{{ battleData?.participants.monster?.name || 'Monster' }}</div>
                          <div class="text-slate-400">Level {{ battleData?.participants.monster?.level || '?' }} Enemy</div>
                      </div>
                 </div>
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
