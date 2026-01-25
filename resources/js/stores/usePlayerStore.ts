import { defineStore } from 'pinia';
import axios from 'axios';
import { usePage } from '@inertiajs/vue3';

export const usePlayerStore = defineStore('player', {
    state: () => ({
        character: null as any,
        inventory: [] as any[],
        activeBattle: null as any,
        showBattleModal: false,
        activeMission: null as any, // Added global active mission
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
        backpackSlotsTotal: (state) => state.character?.inventory_slots || 30,
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
        async initialize() {
            // Hydrate from Inertia Props if available
            // @ts-ignore
            const pageProps = usePage().props;
            if (pageProps.auth?.character) {
                this.character = pageProps.auth.character;
                if (this.character.id) {
                    await Promise.all([
                        this.fetchPlayerData(this.character.id),
                        this.checkActiveMission()
                    ]);
                }
            }
        },

        async fetchPlayerData(characterId: string) {
            if (!characterId) return;
            try {
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

        async checkActiveMission() {
            if (!this.character?.id) return;
            try {
                const res = await axios.get(`/api/mission/active?character_id=${this.character.id}`);
                if (res.data.mission) {
                    this.activeMission = res.data.mission;
                } else {
                    this.activeMission = null;
                }
            } catch (e) {
                console.error("Failed to check active mission:", e);
                this.activeMission = null;
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

        async startMission(mapId: number) {
            try {
                await axios.post('/api/mission/start', {
                    character_id: this.character.id,
                    map_id: mapId
                });
                await this.checkActiveMission();
            } catch (error) {
                console.error('Failed to start mission:', error);
                throw error;
            }
        },

        async claimMission(missionId: string) {
            try {
                // Capture monster details BEFORE claim (as activeMission might be cleared)
                const monster = this.activeMission?.monster;

                const response = await axios.post('/api/mission/claim', { mission_id: missionId });
                const rewards = response.data.rewards;

                if (rewards.type === 'combat_result' && rewards.combat_log) {
                    this.activeBattle = {
                        log: rewards.combat_log,
                        seed: rewards.seed,
                        winnerId: rewards.won ? this.character.id : null,
                        participants: {
                            hero: this.character,
                            // Pass monster details for VS screen
                            monster: monster || { name: 'Unknown', level: '?', max_hp: 100 }
                        },
                        rewards: {
                            gold: rewards.gold,
                            exp: rewards.exp,
                            items: rewards.items
                        }
                    };
                    this.showBattleModal = true;
                }

                // Clear active mission locally immediately (it's claimed)
                this.activeMission = null;

                // Refresh player data
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
