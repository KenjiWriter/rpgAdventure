# Browser MMO RPG Engine

A robust, scalable browser-based MMORPG engine built with **Laravel 11** and **Vue 3**. This project focuses on a "State-Action" architecture where the backend maintains the single source of truth, and the frontend serves as a high-fidelity reactive interface.

## 🛠️ Tech Stack

### Backend
- **Framework**: Laravel 11 (PHP 8.3+)
- **Database**: PostgreSQL / MySQL
- **Real-time**: Laravel Reverb (WebSockets)
- **API**: RESTful (Sanctum Auth)

### Frontend
- **Framework**: Vue 3 (Composition API)
- **State Management**: Pinia
- **Styling**: TailwindCSS (Dark Mode optimized)
- **Build Tool**: Vite

---

## 🏛️ Core Architecture

### State-Action Pattern
All game logic is strictly validated and executed on the server (Services).
1.  **Action**: User acts (e.g., "Equip Sword") -> `POST /api/inventory/move`.
2.  **Validation**: `InventoryService` checks ownership, class restrictions, and slots within a DB transaction.
3.  **State Update**: DB updates, optional WebSocket event broadcast.
4.  **Reflection**: Frontend (Pinia) re-fetches or optimistic-updates the state.

### Mission System (Idle Loop)
Players can send characters on time-based expeditions to farm XP and Loot.
1.  **Start**: Client requests `POST /mission/start`. Server validates level and starts a timer.
2.  **Wait**: Client shows a reactive timer. **Inventory Lockdown** is active: equipment cannot be changed while on a mission to prevent exploiting restrictions or stats during combat calculation.
3.  **Claim**: Once `ends_at` is reached, Client checks in. Server validates timestamp, runs a Combat Simulation (RNG), and awards loot/XP.

> [!NOTE]
> **Technical Note: Tickless Timer**
> The mission duration is validated by comparing `now()` against the database `ends_at` timestamp. This ensures sub-second accuracy and makes client-side clock manipulation impossible.

### Item Generation Engine
Items are generated with dynamic bonuses using `ItemGeneratorService`:
- **Rarity tiers**: Common (x1.0), Rare (x1.2), Epic (x1.5), Legendary (x2.0).
- **Weighted Random**: Higher rarity increases the number of bonus slots (1-5) and the valid range of checks.
- **Type Filtering**: Weapons roll offensive stats (Str, DMG); Armor rolls defensive stats (Vit, Res).

### Upgrade System (Forge)
Items can be refined from Level 0 to 10 (ST. I - X) using `UpgradeService`.
- **Scaling**: Each level increases the item's **Base Stats** by +10% (cumulative).
- **Dynamic Calculation**: The upgrade level is a multiplier applied during stat calculation in `CharacterService`. The database `base_stats` JSON remains immutable; only the `upgrade_level` column changes.
- **Cost**: Consumes Gold and "Upgrade Stone" materials.

> [!NOTE]
> **Design Note: Integer Rounding**
> Since the engine uses Integers for all stats, low-value stats (e.g., "2 Damage") may not visually increase for the first few upgrade levels (2 * 1.1 = 2.2 -> 2). This is expected behavior; the math works correctly and becomes visible as base stats or upgrade levels increase.

### Polymorphic Inventory
Items are stored in a single `item_instances` table using a polymorphic relationship:
-   **Owner**: Can be `Character` (Backpack/Equipment) or `User` (Warehouse).
-   **Slots**: Defined by `ItemSlot` enum (e.g., `main_hand`) or backpack indices.

### Tickless Engine
Replenishment (HP/Mana) and Cooldowns are calculated based on timestamps (`last_update_at`) rather than a background looper, ensuring scalability.

---

## 📊 Database & Stats System

### Derived Stats
Base stats (Strength, Vitality, etc.) are stored in `character_stats`. "Total" or "Computed" stats are calculated by merging:
1.  Base Stats
2.  Item Base Stats (from `ItemTemplate`) * Upgrade Multiplier
3.  Item Random Bonuses (from `ItemInstance` JSON)

These totals are cached in `character_stats.computed_stats` to avoid expensive recalculation on every read.

#### Formulas
| Stat | Formula | Effect |
| :--- | :--- | :--- |
| **Max HP** | `Vitality * 10` | Total health points. |
| **Max Mana** | `Intelligence * 10` | Total magic points. |
| **Attack Power** | *Dynamic* | Based on STR/DEX dependent on Class. |

---

## 🔌 API Documentation

### Character
-   `GET /api/character/{id}`
    -   Returns full character profile, items, and computed stats.

### Inventory
-   `GET /api/inventory?character_id={id}`
    -   Returns backpack items (items not in equipment slots).
-   `POST /api/inventory/move`
    -   **Body**: `{ "item_id": "uuid", "target_owner_type": "character", "target_slot": "main_hand" }`
    -   Moves an item between slots/owners. Handles swaps automatically.

### Missions
-   `POST /api/mission/start`
    -   **Body**: `{ "character_id": "uuid", "map_id": int }`
    -   Starts a mission timer. Locks inventory.
-   `GET /api/mission/active`
    -   Returns current mission status, `ends_at`, and `server_time`.
-   `POST /api/mission/claim`
    -   **Body**: `{ "mission_id": "uuid" }`
    -   Finalizes mission, runs combat, returns rewards (Gold, Exp, Loot).

### Forge
-   `POST /api/forge/upgrade`
    -   **Body**: `{ "item_instance_id": "uuid" }`
    -   **Logic**: Deducts Gold/Materials -> Upgrades Level -> Returns new stats.

---

## 🚀 Development Guide

### Prerequisites
-   PHP 8.3+
-   Node.js & NPM
-   Composer

### Installation
1.  **Clone & Install Dependencies**
    ```bash
    git clone <repo>
    cd rpgAdventure
    composer install
    npm install
    ```

2.  **Environment Setup**
    ```bash
    cp .env.example .env
    php artisan key:generate
    ```
    *Configure your database credentials in `.env`.*

3.  **Database & Seeding**
    ```bash
    php artisan migrate:fresh --seed
    ```
    *This runs `GameContentSeeder` which creates Maps, Monsters, and Starter Items.*

4.  **Run Development Servers**
    -   Backend: `php artisan serve`
    -   Frontend: `npm run dev`

### Verification
Run the core verification commands:
```bash
php artisan verify:core
php artisan verify:forge
# Verify Mission System
# (Custom command removed after phase 4, use verify:core for base logic)
```

---

## 🗺️ Project Roadmap

-   [x] **Phase 1: Foundation**: DB Schema, Core Services, Seeding.
-   [x] **Phase 2: UI Frame**: Vue 3 + Pinia setup, Inventory Grid, Equipment Slots.
-   [x] **Phase 3: Gameplay Loop**: Item Generator (RNG), Forge (Upgrades), API.
-   [x] **Phase 4: Combat & Missions**:
    -   [x] **Missions**: Turn-based/Timer-based questing system.
    -   [ ] **Combat**: Idle/Turn-based hybrid logic (UI pending).
    -   [ ] **Merchant**: Buying/Selling items.
-   [ ] **Phase 5: Social & Economy**:
    -   **Auction House**: Player-to-player trading.
    -   **Clans**: Group events and wars.
