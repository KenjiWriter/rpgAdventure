<script setup lang="ts">
import { computed, ref } from 'vue';
import { usePlayerStore } from '../stores/usePlayerStore';
import { Sword, Shield, Shirt, Footprints, Gamepad2 } from 'lucide-vue-next';
import ItemTooltip from './ItemTooltip.vue';

const store = usePlayerStore();
const backpack = computed(() => store.backpack); // Array of items
const equipment = computed(() => store.equipment); // Array of equipped items for finding specific slots

// Helper to check if a slot has an item
const getEquippedItem = (slotId: string) => {
    return equipment.value.find((item: any) => item.slot_id === slotId);
};

// Slots definition for visual layout
const equipmentSlots = [
    { id: 'head', icon: Shield, label: 'Head' }, 
    { id: 'amulet', icon: Shield, label: 'Amulet' },
    { id: 'start_bdy', type: 'divider'},
    { id: 'main_hand', icon: Sword, label: 'Main Hand' },
    { id: 'chest', icon: Shirt, label: 'Chest' },
    { id: 'off_hand', icon: Shield, label: 'Off Hand' },
    { id: 'end_bdy', type: 'divider'},
    { id: 'legs', icon: Gamepad2, label: 'Legs' },
    { id: 'boots', icon: Footprints, label: 'Boots' },
    { id: 'gloves', icon: Shield, label: 'Gloves' },
    { id: 'ring', icon: Shield, label: 'Ring' },
];

// Backpack Grid
const backpackSlots = Array.from({ length: 42 }, (_, i) => i + 1);

// Context Menu State
const contextMenu = ref({
    show: false,
    x: 0,
    y: 0,
    item: null as any
});

const handleRightClick = (event: MouseEvent, item: any) => {
    if (!item || store.activeMission) return;
    contextMenu.value = {
        show: true,
        x: event.clientX,
        y: event.clientY,
        item
    };
};

const closeContextMenu = () => {
    contextMenu.value.show = false;
};

// Rarity Colors
const getRarityColor = (rarity: string) => {
    switch (rarity) {
        case 'common': return 'bg-slate-800 border-slate-600 text-slate-300';
        case 'uncommon': return 'bg-green-900/30 border-green-600 text-green-300';
        case 'rare': return 'bg-blue-900/30 border-blue-600 text-blue-300';
        case 'epic': return 'bg-purple-900/30 border-purple-600 text-purple-300 text-shadow-purple';
        case 'legendary': return 'bg-amber-900/30 border-amber-600 text-amber-300 text-shadow-amber';
        default: return 'bg-slate-800 border-slate-600 text-slate-300';
    }
};

const getRarityTextColor = (rarity: string) => {
    switch (rarity) {
        case 'common': return 'text-slate-300';
        case 'uncommon': return 'text-green-400';
        case 'rare': return 'text-blue-400';
        case 'epic': return 'text-purple-400';
        case 'legendary': return 'text-amber-400';
        default: return 'text-slate-300';
    }
};

const handleItemClick = async (item: any, source: 'backpack' | 'equipment') => {
    console.log('Interacting with item:', item);
    if (!item) return;
    contextMenu.value.show = false; 

    // Guard Clause for Safety
    if (!item?.template) return;

    if (source === 'backpack') {
        let targetSlot = '';
        
        // Smart Equip Logic
        if (item.template.type === 'weapon') targetSlot = 'main_hand';
        if (item.template.type === 'armor') {
             const name = item.template.name.toLowerCase();
             if (name.includes('armor') || name.includes('tunic') || name.includes('chest') || name.includes('robe')) targetSlot = 'chest';
             if (name.includes('boots') || name.includes('greaves')) targetSlot = 'boots';
             if (name.includes('gloves') || name.includes('gauntlets')) targetSlot = 'gloves';
             if (name.includes('helm') || name.includes('cap')) targetSlot = 'head';
             if (name.includes('legs') || name.includes('pants')) targetSlot = 'legs';
             if (name.includes('shield') || name.includes('buckler')) targetSlot = 'off_hand';
             if (name.includes('amulet') || name.includes('neck')) targetSlot = 'amulet';
             if (name.includes('ring')) targetSlot = 'ring';
        }

        // Dagger Logic: Assign to offhand if mainhand occupied? Or just mainhand.
        // For now default to mainhand for generic weapon.
        if (item.template.name.toLowerCase().includes('dagger')) {
             if (getEquippedItem('main_hand') && !getEquippedItem('off_hand')) {
                 // Dual wield logic not fully implemented yet, but let's assume Main Hand for now.
             }
        }
        
        if (!targetSlot) {
             return alert("Cannot auto-equip: Unknown slot for " + item.template.name);
        }
        
        // Level Lock
        if ((item.template.min_level || 1) > store.character.level) {
            return alert(`Level too low! Required: ${item.template.min_level}`);
        }
        
        // Class Restriction Check
        if (item.template.class_restriction && item.template.class_restriction !== store.character.class) {
            return alert(`Cannot equip! This item is for ${item.template.class_restriction}s only.`);
        }
        
        await store.moveItem(item.id, 'character', targetSlot);
    } else {
        // Unequip to first empty slot
        const takenSlots = backpack.value.map((i: any) => i.slot_id);
        let freeSlot = 1;
        while (takenSlots.includes(`backpack_${freeSlot}`)) {
            freeSlot++;
        }
        await store.moveItem(item.id, 'character', `backpack_${freeSlot}`);
    }
};

const getItemInBackpackSlot = (slotIndex: number) => {
    return backpack.value.find((item: any) => item.slot_id === `backpack_${slotIndex}`);
};

</script>

<template>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Equipment Panel -->
        <div class="relative bg-slate-900/80 p-6 rounded-xl border border-slate-800 backdrop-blur-sm">
            
            <!-- Lock Overlay (Equipment) -->
            <div v-if="store.activeMission" class="absolute inset-0 z-50 bg-slate-950/50 backdrop-blur-[1px] cursor-not-allowed"></div>

            <h2 class="text-xl font-bold text-zinc-100 mb-6 border-b border-slate-700 pb-2">Equipment</h2>
            <div class="flex flex-col items-center gap-4">
                
                <div class="grid grid-cols-3 gap-4">
                    <template v-for="slot in equipmentSlots" :key="slot.id">
                         <div v-if="slot.type === 'divider'" class="col-span-3 h-0"></div>
                         <div v-else class="relative group">
                             <!-- Slot BG -->
                            <div 
                                class="w-16 h-16 rounded-lg bg-slate-950 border-2 border-slate-800 flex items-center justify-center transition-colors"
                                :class="store.activeMission ? 'opacity-50 grayscale' : 'hover:border-indigo-500/50 cursor-pointer'"
                                @click="!store.activeMission && handleItemClick(getEquippedItem(slot.id), 'equipment')"
                            >
                                <component :is="slot.icon" v-if="!getEquippedItem(slot.id)" class="w-6 h-6 text-slate-700" />
                                
                                <!-- Item Render -->
                                <div v-else class="w-full h-full p-1 relative">
                                    <div class="w-full h-full bg-slate-800 rounded border border-slate-600 flex items-center justify-center text-xs text-center leading-tight">
                                        {{ getEquippedItem(slot.id)?.template?.name }}
                                    </div>
                                    <!-- Tooltip -->
                                    <ItemTooltip 
                                        :item="getEquippedItem(slot.id)" 
                                        :character-level="store.character.level"
                                        class="hidden group-hover:block"
                                    />
                                </div>
                            </div>
                         </div>
                    </template>
                </div>
            </div>
            
             <!-- Detailed Stats Panel -->
             <div class="mt-8 p-4 bg-slate-950/50 rounded-lg border border-slate-800 font-mono text-xs">
                 <h3 class="text-zinc-400 font-bold mb-3 uppercase tracking-wider text-[10px]">Attributes</h3>
                 <div class="grid grid-cols-2 gap-y-2 mb-4">
                     <div class="flex justify-between pr-4 border-r border-slate-800">
                         <span class="text-slate-500">Strength</span>
                         <span class="text-amber-500">{{ store.computedStats.strength }}</span>
                     </div>
                     <div class="flex justify-between pl-4">
                         <span class="text-slate-500">Dexterity</span>
                         <span class="text-emerald-500">{{ store.computedStats.dexterity }}</span>
                     </div>
                     <div class="flex justify-between pr-4 border-r border-slate-800">
                         <span class="text-slate-500">Intellect</span>
                         <span class="text-blue-500">{{ store.computedStats.intelligence }}</span>
                     </div>
                     <div class="flex justify-between pl-4">
                         <span class="text-slate-500">Vitality</span>
                         <span class="text-red-500">{{ store.computedStats.vitality }}</span>
                     </div>
                 </div>

                 <h3 class="text-zinc-400 font-bold mb-3 uppercase tracking-wider text-[10px] pt-2 border-t border-slate-800">Combat Stats</h3>
                 <div class="space-y-2">
                     <div class="flex justify-between items-center">
                         <span class="text-slate-400">⚔️ Attack Power</span>
                         <span class="text-amber-400 font-bold">{{ store.derivedStats.attack_min }} - {{ store.derivedStats.attack_max }}</span>
                     </div>
                     <div class="flex justify-between items-center">
                         <span class="text-slate-400">🛡️ Defense</span>
                         <span class="text-slate-200 font-bold">{{ store.derivedStats.defense }}</span>
                     </div>
                     <div class="flex justify-between items-center">
                         <span class="text-slate-400">💨 Dodge Chance</span>
                         <span class="text-emerald-400 font-bold">{{ store.derivedStats.dodge }}%</span>
                     </div>
                 </div>

                 <!-- Active Bonuses -->
                 <div v-if="store.activeBonuses.length > 0" class="mt-4 pt-2 border-t border-slate-800">
                    <h3 class="text-zinc-400 font-bold mb-2 uppercase tracking-wider text-[10px]">Active Enchantments</h3>
                    <div class="space-y-1">
                        <div v-for="bonus in store.activeBonuses" :key="bonus.type" class="flex justify-between text-[10px]">
                             <span class="text-slate-500 capitalize">{{ bonus.type.replace('_', ' ') }}</span>
                             <span class="text-green-400">+{{ bonus.value }}</span>
                        </div>
                    </div>
                 </div>
             </div>
        </div>

        <!-- Backpack Panel -->
        <div class="relative lg:col-span-2 bg-slate-900/80 p-6 rounded-xl border border-slate-800 backdrop-blur-sm" @click="closeContextMenu">
            
            <!-- Lock Overlay -->
            <div v-if="store.activeMission" class="absolute inset-0 z-50 bg-slate-950/80 backdrop-blur-[2px] flex flex-col items-center justify-center border border-red-900/30 rounded-xl">
                <div class="bg-slate-900 p-4 rounded-lg border border-red-600 shadow-2xl flex flex-col items-center gap-2">
                    <span class="text-3xl">🔒</span>
                    <h3 class="text-red-500 font-bold uppercase tracking-wider">Inventory Locked</h3>
                    <p class="text-slate-400 text-xs text-center max-w-[200px]">
                        You cannot change equipment while on a mission.
                    </p>
                </div>
            </div>

            <h2 class="text-xl font-bold text-zinc-100 mb-6 border-b border-slate-700 pb-2">Backpack</h2>
            
            <div class="grid grid-cols-7 gap-2 relative">
                <div 
                    v-for="slot in backpackSlots" 
                    :key="slot"
                    class="aspect-square bg-slate-950 border border-slate-800 rounded shadow-inner hover:bg-slate-800 transition-colors relative group"
                    @contextmenu.prevent="handleRightClick($event, getItemInBackpackSlot(slot))"
                >
                    <div 
                        v-if="getItemInBackpackSlot(slot)"
                        class="w-full h-full p-1 cursor-pointer"
                        @click="!store.activeMission && handleItemClick(getItemInBackpackSlot(slot), 'backpack')"
                    >
                        <!-- Item Rendering with Rarity Colors -->
                         <div class="w-full h-full rounded border flex items-center justify-center text-[10px] text-center leading-tight overflow-hidden break-words p-0.5 transition-colors"
                            :class="getRarityColor(getItemInBackpackSlot(slot)?.template?.rarity)">
                            {{ getItemInBackpackSlot(slot)?.template?.name }}
                        </div>
                        
                            <!-- Tooltip -->
                            <ItemTooltip 
                                :item="getItemInBackpackSlot(slot)" 
                                :character-level="store.character.level"
                                class="hidden group-hover:block"
                            >
                                <template #footer>Kliknij by założyć</template>
                            </ItemTooltip>
                    </div>
                </div>
            </div>
            
            <!-- Context Menu Backdrop -->
            <Teleport to="body">
                <div v-if="contextMenu.show" class="fixed inset-0 z-[60] bg-transparent" @click="closeContextMenu" @contextmenu.prevent="closeContextMenu"></div>
                
                <!-- Context Menu -->
                <div v-if="contextMenu.show" 
                     class="fixed z-[70] bg-slate-800 border border-slate-600 rounded shadow-2xl py-1 text-sm min-w-[120px]"
                     :style="{ top: contextMenu.y + 'px', left: contextMenu.x + 'px' }">
                     <button class="w-full text-left px-4 py-2 hover:bg-slate-700 text-white flex items-center gap-2" @click="handleItemClick(contextMenu.item, 'backpack')">
                        ⚔️ Equip
                     </button>
                     <button class="w-full text-left px-4 py-2 hover:bg-slate-700 text-red-400 flex items-center gap-2 cursor-not-allowed opacity-50">
                        🗑️ Trash (Soon)
                     </button>
                </div>
            </Teleport>

            <div class="mt-4 flex justify-between items-center text-xs text-slate-500">
                <span>{{ store.backpackSlotsUsed }} / {{ store.backpackSlotsTotal }} Slots Used</span>
                <button class="text-indigo-400 hover:text-indigo-300">Sort Items</button>
            </div>
        </div>
    </div>
</template>
