import { defineStore } from 'pinia';
import axios from 'axios';

export const usePlayerStore = defineStore('player', {
    state: () => ({
        character: null as any,
        inventory: [] as any[],
        activeBattle: null as any,
        showBattleModal: false,
        logs: [] as any[],
        quests: [] as any[],
    }),

    getters: {
        equipment: (state) => {
            if (!state.character?.items) return [];
            const equipmentSlots = ['head', 'chest', 'legs', 'boots', 'gloves', 'main_hand', 'off_hand', 'amulet', 'ring'];
            return state.character.items.filter((item: any) => equipmentSlots.includes(item.slot_id));
        },
        backpack: (state) => state.inventory,
        backpackSlotsUsed: (state) => state.inventory.length,
        backpackSlotsTotal: (state) => state.character?.inventory_slots || 30, // Default if missing
        computedStats: (state) => state.character?.stats?.computed_stats || {},
        maxHp: (state) => state.character?.stats?.computed_stats?.max_hp || 100,
        maxMana: (state) => state.character?.stats?.computed_stats?.max_mana || 50,
        experiencePercent: (state) => {
            if (!state.character) return 0;
            const nextLevelExp = state.character.level * 1000;
            return Math.min(100, (state.character.experience / nextLevelExp) * 100);
        }
    },

    actions: {
        async fetchPlayerData(characterId: string) {
            if (!characterId) {
                console.warn('fetchPlayerData called with no ID');
                return;
            }
            try {
                // Parallel fetch
                const [charRes, invRes, logsRes, questsRes] = await Promise.all([
                    axios.get(`/api/character/${characterId}`),
                    axios.get(`/api/inventory?character_id=${characterId}`),
                    axios.get(`/api/character/${characterId}/logs`),
                    axios.get(`/api/quests`)
                ]);

                this.character = charRes.data.character;
                if (charRes.data.computed_stats) {
                    this.character.stats.computed_stats = charRes.data.computed_stats;
                }

                this.inventory = invRes.data.items;
                this.logs = logsRes.data;
                this.quests = questsRes.data;
            } catch (error) {
                console.error('Failed to fetch player data:', error);
            }
        },

        async fetchLogs(characterId: string) {
            try {
                const response = await axios.get(`/api/character/${characterId}/logs`);
                this.logs = response.data;
            } catch (error) {
                console.error('Failed to fetch logs:', error);
            }
        },

        async claimQuest(questId: number) {
            try {
                const response = await axios.post(`/api/quests/${questId}/claim`);
                // Update specific quest state or refetch
                await this.fetchPlayerData(this.character.id);
                return response.data;
            } catch (error) {
                console.error('Failed to claim quest:', error);
                throw error;
            }
        },

        async moveItem(itemId: string, targetOwnerType: 'character' | 'user', targetSlot: string) {
            try {
                await axios.post('/api/inventory/move', {
                    item_id: itemId,
                    target_owner_type: targetOwnerType,
                    target_owner_id: this.character.id,
                    target_slot: targetSlot
                });
                await this.fetchPlayerData(this.character.id);
            } catch (error) {
                console.error('Failed to move item:', error);
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
                        winnerId: rewards.won ? this.character.id : null,
                        participants: {
                            hero: this.character,
                        },
                        rewards: {
                            gold: rewards.gold,
                            exp: rewards.exp,
                            items: rewards.items
                        }
                    };
                    this.showBattleModal = true;
                }

                // Refresh player data (gold/exp/items/logs/quests)
                await this.fetchPlayerData(this.character.id);

                return response.data;
            } catch (error) {
                console.error('Failed to claim mission:', error);
                throw error;
            }
        },

        closeBattleModal() {
            this.showBattleModal = false;
            this.activeBattle = null;
        }
    }
});
