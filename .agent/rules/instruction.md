---
trigger: always_on
---

# Project: Browser MMO RPG Engine (Shakes & Fidget Style)
# Tech Stack: Laravel 12, Vue.js 3 (Composition API), Pinia, TailwindCSS, Laravel Reverb (WebSockets)

## 1. Core Architectural Principles
- **State-Action Pattern:** All game logic resides in the Laravel Backend (Services). Vue.js is a reactive reflection of the Database state.
- **Single Source of Truth:** The `players` table and related relations define the UI. Use Pinia in the frontend to sync global state.
- **Tickless Engine:** Use timestamps for cooldowns and durations. Frontend handles visual countdowns; Backend validates on completion.

## 2. Technical Stack Details
- **Backend:** Laravel 11 (PHP 8.3+).
- **Frontend:** Vue.js 3 with Vite.
- **State Management:** Pinia (for real-time updates of Gold, Exp, Stats).
- **Communication:** Axios for actions; Laravel Reverb for real-time broadcasts (combat logs, trade alerts).
- **Database:** PostgreSQL/MySQL with optimized indexing for `player_id`.

## 3. Database & Entity Schema
### A. Player & Classes
- **Classes:** Warrior, Assassin, Mage.
- **Core Stats:** Strength (Siła), Vitality (Witalność), Intelligence (Inteligencja), Dexterity (Zręczność).
- **Stat Points:** 4 points per level up.
### B. Equipment & Bonuses
- **Slots:** Weapon, Armor, Helmet, Boots, Gloves, Amulet, Ring.
- **Bonus System:** 5 random slots per item.
    - Defensive bonuses restricted to Armor types.
    - Offensive bonuses restricted to Weapon types.
    - Range: Min-Max values calculated during "enchanting" or "dropping".
- **Upgrade System [ST. I - X]:** Linear progression using Materials + Gold. Each level increases base item power.
### C. Resistances & Strengths
- **Elements:** Wind, Fire, Water, Earth, Neutral.
- **Modifiers:** Percentage-based reduction (e.g., Wind Resistance 5%) or damage boost (Strong vs Monsters 15%).

## 4. UI Layout Requirements (Main View)
- **Top Bar (Global Stats):**
    - Player Icon, Level, EXP Bar (Reactive).
    - Gold/Premium Currency display.
    - Must listen to `EntityUpdated` WebSocket event to refresh without page reload.
- **Slide-out Mission Tracker:**
    - List of active quests (e.g., "Kill 15 Dogs").
    - Progress bar (e.g., 4/15).
    - "Claim Reward" button appears only when `status == 'completed'`.
- **Inventory Overlay (4 Sections):**
    1. **Equipped:** Visual slots for current gear.
    2. **Backpack:** Grid of items in possession.
    3. **Attributes:** Interactive buttons to spend stat points.
    4. **Detailed Bonuses:** List of all active modifiers from gear (e.g., Total +20% Wind Res).

## 5. Game Systems Logic
### A. Mission & Map System
- **Level Gates:** Map A (1-15lvl), Map B (15-30lvl), etc.
- **Economy Loop:** High-level players needing low-level materials must use the Market or alts.
### B. Combat System (Idle-Turn-Based)
- **Mechanic:** Not strictly turn-based.
- **Attack Speed Logic:** Frequency of hits = `Base_Speed * (Dexterity * Speed_Bonus)`.
- **Balance:** - Tanks: High Defense, Low Attack Speed.
    - Assassins: Low Defense, Extreme Attack Speed.
### C. Drop & RNG
- Item drops must generate UUIDs and unique bonus sets stored in `item_instances` table.

## 6. Developer Guidelines for Agent
- Create `GameService` for shared logic (Leveling, Gold management).
- Use Laravel Policies to ensure a player can only upgrade their own items.
- Use Vue components for modularity: `PlayerStats.vue`, `InventorySlot.vue`, `CombatLog.vue`.
- Ensure all numbers are handled as Integers or Decimals (never Floats for currency).