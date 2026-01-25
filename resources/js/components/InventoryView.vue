<script setup lang="ts">
import { computed } from 'vue';
import { usePlayerStore } from '../stores/usePlayerStore';
import { Sword, Shield, Shirt, Footprints, Gamepad2 } from 'lucide-vue-next';

const store = usePlayerStore();
const backpack = computed(() => store.backpack); // Array of items
const equipment = computed(() => store.equipment); // Array of equipped items for finding specific slots

// Helper to check if a slot has an item
const getEquippedItem = (slotId: string) => {
    return equipment.value.find((item: any) => item.slot_id === slotId);
};

// Slots definition for visual layout
const equipmentSlots = [
    { id: 'head', icon: Shield, label: 'Head' }, // Using Shield as placeholder, could find better icons
    { id: 'amulet', icon: Shield, label: 'Amulet' },
    { id: 'start_bdy', type: 'divider'},
    { id: 'main_hand', icon: Sword, label: 'Main Hand' },
    { id: 'chest', icon: Shirt, label: 'Chest' },
    { id: 'off_hand', icon: Shield, label: 'Off Hand' },
    { id: 'end_bdy', type: 'divider'},
    { id: 'legs', icon: Gamepad2, label: 'Legs' }, // Placeholder icon
    { id: 'boots', icon: Footprints, label: 'Boots' },
    { id: 'gloves', icon: Shield, label: 'Gloves' },
    { id: 'ring', icon: Shield, label: 'Ring' },
];

// Backpack Grid
const backpackSlots = Array.from({ length: 42 }, (_, i) => i + 1);

// Move Logic
const handleItemClick = (item: any, source: 'backpack' | 'equipment') => {
    if (!item) return;

    // Simple auto-equip / unequip logic
    let targetSlot = '';
    
    if (source === 'backpack') {
        // Needs mapping from Item Type or logic to determine valid slot.
        // For simplicity, if it's weapon -> main_hand (unless occupied?), armor -> chest/boots etc based on template type?
        // Or we just try to move to a default slot based on backend logic?
        // Backend move requires a specific slot.
        // Let's deduce slot from valid mappings in item.template? Ideally backend sends that info.
        // Or we guess.
        // "Rusty Sword" -> main_hand.
        // "Worn Tunic" -> chest.
        // We really need 'equip_slot' on item template to know where it goes by default.
        // Assuming user knows or we hardcode for now based on name/type for prototype.
        
        if (item.template.type === 'weapon') targetSlot = 'main_hand';
        if (item.template.type === 'armor') {
             // Heuristic based on name?
             if (item.template.name.includes('Tunic') || item.template.name.includes('Armor')) targetSlot = 'chest';
             if (item.template.name.includes('Boots')) targetSlot = 'boots';
        }
        
        if (!targetSlot) return alert("Cannot auto-equip this item type yet");
        
        store.moveItem(item.id, 'character', targetSlot);
    } else {
        // Unequip to first empty backpack slot?
        // For now, just try 'backpack_1'.
        // Optimally, find first empty slot in backpack array.
        // But store.backpack might not maintain "slots" array logic, just list of items.
        // We need to find `backpack_N` that is not taken.
        const takenSlots = backpack.value.map((i: any) => i.slot_id);
        let freeSlot = 1;
        while (takenSlots.includes(`backpack_${freeSlot}`)) {
            freeSlot++;
        }
        store.moveItem(item.id, 'character', `backpack_${freeSlot}`);
    }
};

const getItemInBackpackSlot = (slotIndex: number) => {
    return backpack.value.find((item: any) => item.slot_id === `backpack_${slotIndex}`);
};

</script>

<template>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Equipment Panel -->
        <div class="bg-slate-900/80 p-6 rounded-xl border border-slate-800 backdrop-blur-sm">
            <h2 class="text-xl font-bold text-zinc-100 mb-6 border-b border-slate-700 pb-2">Equipment</h2>
            <div class="flex flex-col items-center gap-4">
                
                <!-- We can layout slots in a "Doll" shape or just grid -->
                <div class="grid grid-cols-3 gap-4">
                    <template v-for="slot in equipmentSlots" :key="slot.id">
                         <div v-if="slot.type === 'divider'" class="col-span-3 h-0"></div>
                         <div v-else class="relative group">
                             <!-- Slot BG -->
                            <div 
                                class="w-16 h-16 rounded-lg bg-slate-950 border-2 border-slate-800 flex items-center justify-center hover:border-indigo-500/50 transition-colors cursor-pointer"
                                @click="handleItemClick(getEquippedItem(slot.id), 'equipment')"
                            >
                                <component :is="slot.icon" v-if="!getEquippedItem(slot.id)" class="w-6 h-6 text-slate-700" />
                                
                                <!-- Item Render -->
                                <div v-else class="w-full h-full p-1 relative">
                                    <div class="w-full h-full bg-slate-800 rounded border border-slate-600 flex items-center justify-center text-xs text-center leading-tight">
                                        {{ getEquippedItem(slot.id)?.template?.name }}
                                    </div>
                                    <!-- Tooltip -->
                                    <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 w-48 bg-slate-900 border border-amber-500/30 p-2 rounded shadow-xl z-50 hidden group-hover:block pointer-events-none">
                                        <div class="text-amber-400 font-bold mb-1">{{ getEquippedItem(slot.id)?.template?.name }}</div>
                                        <div class="text-[10px] text-slate-300">Level {{ getEquippedItem(slot.id)?.upgrade_level }}</div>
                                        <div class="mt-1 border-t border-slate-800 pt-1">
                                             <div v-for="bonus in getEquippedItem(slot.id)?.bonuses" class="text-[10px] text-green-400">
                                                 +{{ bonus.value }} {{ bonus.type }}
                                             </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                         </div>
                    </template>
                </div>
            </div>
            
             <!-- Stats Summary (Quick View) -->
             <div class="mt-8 p-4 bg-slate-950/50 rounded-lg border border-slate-800 font-mono text-xs">
                 <div class="flex justify-between mb-1">
                     <span class="text-slate-400">Strength</span>
                     <span class="text-green-400">{{ store.computedStats.strength }}</span>
                 </div>
                 <div class="flex justify-between mb-1">
                     <span class="text-slate-400">Dexterity</span>
                     <span class="text-green-400">{{ store.computedStats.dexterity }}</span>
                 </div>
                 <div class="flex justify-between">
                     <span class="text-slate-400">Intelligence</span>
                     <span class="text-green-400">{{ store.computedStats.intelligence }}</span>
                 </div>
             </div>
        </div>

        <!-- Backpack Panel -->
        <div class="lg:col-span-2 bg-slate-900/80 p-6 rounded-xl border border-slate-800 backdrop-blur-sm">
            <h2 class="text-xl font-bold text-zinc-100 mb-6 border-b border-slate-700 pb-2">Backpack</h2>
            
            <div class="grid grid-cols-7 gap-2">
                <div 
                    v-for="slot in backpackSlots" 
                    :key="slot"
                    class="aspect-square bg-slate-950 border border-slate-800 rounded shadow-inner hover:bg-slate-800 transition-colors relative group"
                >
                    <div 
                        v-if="getItemInBackpackSlot(slot)"
                        class="w-full h-full p-1 cursor-pointer"
                        @click="handleItemClick(getItemInBackpackSlot(slot), 'backpack')"
                    >
                         <div class="w-full h-full bg-slate-800 rounded border border-slate-700 flex items-center justify-center text-[8px] text-center leading-tight overflow-hidden break-words p-0.5">
                            {{ getItemInBackpackSlot(slot)?.template?.name }}
                        </div>
                        
                         <!-- Tooltip -->
                        <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 w-48 bg-slate-900 border border-slate-600 p-2 rounded shadow-xl z-50 hidden group-hover:block pointer-events-none">
                            <div class="text-white font-bold mb-1">{{ getItemInBackpackSlot(slot)?.template?.name }}</div>
                            <div class="text-[10px] text-slate-400">{{ getItemInBackpackSlot(slot)?.template?.type }}</div>
                            <div class="mt-1 border-t border-slate-800 pt-1">
                                    <div v-for="(val, key) in getItemInBackpackSlot(slot)?.template?.base_stats" class="text-[10px] text-slate-300">
                                        {{ key }}: {{ val }}
                                    </div>
                                    <div v-for="bonus in getItemInBackpackSlot(slot)?.bonuses" class="text-[10px] text-green-400">
                                        +{{ bonus.value }} {{ bonus.type }}
                                    </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="mt-4 flex justify-between items-center text-xs text-slate-500">
                <span>{{ store.backpackSlotsUsed }} / {{ store.backpackSlotsTotal }} Slots Used</span>
                <button class="text-indigo-400 hover:text-indigo-300">Sort Items</button>
            </div>
        </div>
    </div>
</template>
