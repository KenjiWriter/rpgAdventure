<script setup lang="ts">
import { computed, ref } from 'vue';
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
    if (!item) return;
    contextMenu.value.show = false; // Close if open

    if (!item || !item.item_template) return;
    contextMenu.value.show = false; // Close if open
    
    // ... logic checks item.template ...
    
    // ...
    // Note: The original code logic inside handleItemClick was correct but just needed the guard.
    // I am replacing the full function logic with just the guard maintained? No, I am editing `handleItemClick` block.
    // Actually, I'll update the tooltip too in this call if I can, but multiple non-contiguous edits are hard.
    // Let's use MultiReplace or just two calls.
    // Wait, the user asked for:
    // 1. Check in handleItemClick
    // 2. Tooltip update
    // I can stick them in one call if I Replace the whole file or large chunks.
    // I'll stick to targeted replacing.
    
    // Check 1: handleItemClick safety.
    
    if (source === 'backpack') {
        let targetSlot = '';
        
        // Smart Equip Logic
        if (item.template.type === 'weapon') targetSlot = 'main_hand';
        if (item.template.type === 'armor') {
             const name = item.template.name.toLowerCase();
             if (name.includes('armor') || name.includes('tunic') || name.includes('chest')) targetSlot = 'chest';
             if (name.includes('boots') || name.includes('greaves')) targetSlot = 'boots';
             if (name.includes('gloves') || name.includes('gauntlets')) targetSlot = 'gloves';
             if (name.includes('helm') || name.includes('cap')) targetSlot = 'head';
             if (name.includes('legs') || name.includes('pants')) targetSlot = 'legs';
             if (name.includes('shield')) targetSlot = 'off_hand';
             if (name.includes('amulet') || name.includes('neck')) targetSlot = 'amulet';
             if (name.includes('ring')) targetSlot = 'ring';
        }
        
        if (!targetSlot) {
            // Fallback: If stats suggest defense, maybe chest?
             return alert("Cannot auto-equip: Unknown slot for " + item.template.name);
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
        <!-- ... -->
        <!-- Backpack Item Render Update -->
                        <!-- Tooltip -->
                        <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 w-48 bg-slate-900 border border-slate-600 p-2 rounded shadow-xl z-50 hidden group-hover:block pointer-events-none">
                            <div class="font-bold mb-1" :class="getRarityTextColor(getItemInBackpackSlot(slot)?.template?.rarity)">
                                {{ getItemInBackpackSlot(slot)?.template?.name }}
                            </div>
                            <div class="text-[10px] text-slate-400 mb-1 flex justify-between">
                                <span>{{ getItemInBackpackSlot(slot)?.template?.type }}</span>
                                <span class="text-xs font-bold text-white">Lvl {{ getItemInBackpackSlot(slot)?.template?.min_level }}</span>
                            </div>

                            <!-- Base Stats -->
                            <div class="mb-1 border-b border-slate-700 pb-1">
                                <div v-if="getItemInBackpackSlot(slot)?.template?.base_damage" class="text-xs text-red-400 font-bold">
                                    ⚔️ Damage: {{ getItemInBackpackSlot(slot)?.template?.base_damage }}
                                </div>
                                <div v-if="getItemInBackpackSlot(slot)?.template?.base_defense" class="text-xs text-blue-400 font-bold">
                                    🛡️ Defense: {{ getItemInBackpackSlot(slot)?.template?.base_defense }}
                                </div>
                            </div>

                            <!-- Bonuses -->
                            <div class="pt-1">
                                    <div v-for="(val, key) in getItemInBackpackSlot(slot)?.template?.base_stats" class="text-[10px] text-slate-300">
                                        {{ key }}: {{ val }}
                                    </div>
                                    <div v-for="bonus in getItemInBackpackSlot(slot)?.bonuses" class="text-[10px] text-green-400">
                                        +{{ bonus.value }} {{ bonus.type }}
                                    </div>
                            </div>
                            <div class="text-[10px] text-slate-500 mt-1 italic">Left-click to Equip</div>
                        </div>
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
