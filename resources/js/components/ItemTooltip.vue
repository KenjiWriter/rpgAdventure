<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
    item: any;
    characterLevel?: number;
}>();

const template = computed(() => props.item?.template || props.item?.item_template);
const bonuses = computed(() => {
    // Handle MerchantItem (data.bonuses) vs ItemInstance (bonuses)
    if (props.item?.data?.bonuses) return props.item.data.bonuses;
    return props.item?.bonuses || [];
});

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

const canEquip = computed(() => {
    if (!props.characterLevel) return true;
    return (template.value?.min_level || 1) <= props.characterLevel;
});
</script>

<template>
    <div v-if="template" class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 w-48 bg-slate-900 border border-slate-600 p-2 rounded shadow-xl z-50 pointer-events-none text-left font-sans">
        <!-- Name -->
        <div class="font-bold mb-1 border-b border-slate-700 pb-1" :class="getRarityTextColor(template.rarity)">
            {{ template.name }}
        </div>

        <div class="space-y-1">
            <!-- Required Level -->
            <div class="text-xs" :class="canEquip ? 'text-slate-400' : 'text-red-500 font-bold'">
                Od Poziomu: {{ template.min_level || 1 }}
            </div>

            <!-- Base Stats -->
            <div v-if="template.base_damage_min" class="text-xs text-amber-400 font-bold">
                Wartość Ataku: {{ template.base_damage_min }} - {{ template.base_damage_max }}
            </div>
            <div v-if="template.base_defense" class="text-xs text-amber-400 font-bold">
                Obrona: {{ template.base_defense }}
            </div>

            <!-- Class Restriction -->
            <div v-if="template.class_restriction" class="text-[10px] uppercase font-bold text-slate-500">
                Klasa: {{ template.class_restriction }}
            </div>
        </div>

        <!-- Bonuses Divider -->
        <hr v-if="bonuses.length" class="border-slate-700 my-1.5">

        <!-- Bonuses -->
        <div v-if="bonuses.length" class="space-y-0.5">
            <div v-for="(bonus, idx) in bonuses" :key="idx" class="text-[10px] text-green-400 flex justify-between">
                <span class="capitalize">{{ bonus.type?.replace('_', ' ') }}</span>
                <span>+{{ bonus.value }}</span>
            </div>
        </div>
        
        <div class="mt-2 text-[9px] text-slate-600 italic text-center">
            <slot name="footer"></slot>
        </div>
    </div>
</template>
