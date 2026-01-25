import { defineStore } from 'pinia';
import axios from 'axios';

export const usePlayerStore = defineStore('player', {
    state: () => ({
        character: null as any,
        inventory: [] as any[],
        activeBattle: null as any,
        showBattleModal: false,
    }),

    getters: {
        equipment: (state) => {
            if (!state.character?.items) return [];
            // Assuming the API returns all items in character.items
            // We filter for equipment slots
            const equipmentSlots = ['head', 'chest', 'legs', 'boots', 'gloves', 'main_hand', 'off_hand', 'amulet', 'ring'];
            return state.character.items.filter((item: any) => equipmentSlots.includes(item.slot_id));
        },
        backpack: (state) => {
            // If the inventory API returns items separately, we use state.inventory
            // If the character API returns everything, we might assume backpack items are also in character.items?
            // The prompt asked for:
            // "GET /api/inventory: Returns the character's backpack items."
            // So we likely populate `state.inventory` from that endpoint.
            return state.inventory;
        },
        computedStats: (state) => state.character?.stats?.computed_stats || {},
        maxHp: (state) => state.character?.stats?.computed_stats?.max_hp || 100,
        maxMana: (state) => state.character?.stats?.computed_stats?.max_mana || 50,
        experiencePercent: (state) => {
            // Simple placeholder logic for EXP bar.
            // Need max exp for level. For now assume linear or just use 0-100 logic if backend sends it.
            // Backend sends `experience`.
            // Let's assume next level exp is Level * 1000 for simplicity or just return 0 if unknown.
            if (!state.character) return 0;
            const nextLevelExp = state.character.level * 1000;
            return Math.min(100, (state.character.experience / nextLevelExp) * 100);
        }
    },

    actions: {
        async fetchPlayerData(characterId: string) {
            try {
                // Parallel fetch
                const [charRes, invRes] = await Promise.all([
                    axios.get(`/api/character/${characterId}`),
                    axios.get(`/api/inventory?character_id=${characterId}`)
                ]);

                this.character = charRes.data.character;
                // Merge computed stats into stats if separated in response, but Controller sends it in `stats` already roughly.
                // Controller response: { character: {...}, computed_stats: {...} }
                // So we can merge it.
                if (charRes.data.computed_stats) {
                    this.character.stats.computed_stats = charRes.data.computed_stats;
                }

                this.inventory = invRes.data.items;
            } catch (error) {
                console.error('Failed to fetch player data:', error);
            }
        },

        async moveItem(itemId: string, targetOwnerType: 'character' | 'user', targetSlot: string) {
            // Optimistic Update Logic could be complex due to swaps.
            // For now, let's do the API call and then refresh. 
            // Optimistic updates for swapping items require knowing exactly what's in the target slot.
            // We have that in state.

            try {
                await axios.post('/api/inventory/move', {
                    item_id: itemId,
                    target_owner_type: targetOwnerType,
                    target_owner_id: this.character.id, // Assuming moving to self/warehouse of self
                    target_slot: targetSlot
                });

                // Success - Refresh Data
                // Refetching is safer to ensure sync with server-side restrictions validation
                await this.fetchPlayerData(this.character.id);
            } catch (error) {
                console.error('Failed to move item:', error);
                // Revert optimistic changes if we did any
            }
        },

        async claimMission(missionId: string) {
            try {
                const response = await axios.post('/api/mission/claim', { mission_id: missionId });
                const rewards = response.data.rewards;

                if (rewards.type === 'combat_result' && rewards.combat_log) {
                    this.activeBattle = {
                        log: rewards.combat_log,
                        seed: rewards.seed,
                        winnerId: rewards.won ? this.character.id : null, // Assuming if won, Hero won
                        participants: {
                            hero: this.character,
                            // We don't have full monster data here unless we pass it or response has it.
                            // The claim response `Mission::with(...)` might return monster logic, but `rewards` is just JSON.
                            // We should probably ensure the response includes minimal monster info for UI.
                            // But usually Mission view has Monster info.
                            // Let's assume we can pass monster details from the component that calls this.
                        },
                        rewards: {
                            gold: rewards.gold,
                            exp: rewards.exp,
                            items: rewards.items
                        }
                    };
                    this.showBattleModal = true;
                }

                // Refresh player data (gold/exp/items)
                await this.fetchPlayerData(this.character.id);

                return response.data;
            } catch (error) {
                console.error('Failed to claim mission:', error);
                throw error;
            }
        },

        closeBattleModal() {
            this.showBattleModal = false;
            this.activeBattle = null; // Memory Cleanup
        }
    }
});
