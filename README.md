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
2.  Item Base Stats (from `ItemTemplate`)
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
Run the core logic verification command:
```bash
php artisan verify:core
```

---

## 🗺️ Project Roadmap

-   [x] **Phase 1: Foundation**: DB Schema, Core Services, Seeding.
-   [x] **Phase 2: UI Frame**: Vue 3 + Pinia setup, Inventory Grid, Equipment Slots.
-   [ ] **Phase 3: Gameplay Loop**:
    -   **Missions**: Turn-based/Timer-based questing system.
    -   **Combat**: Idle/Turn-based hybrid logic.
    -   **Merchant**: Buying/Selling items.
-   [ ] **Phase 4: Social & Economy**:
    -   **Auction House**: Player-to-player trading.
    -   **Clans**: Group events and wars.
