# Landing Page Updates & Admin Sync Entity Management — Design Spec

## Overview

Two related updates to the TriggerTime web application (`triggertime-site`):

1. **Landing page** — Refresh feature presentation to reflect newly shipping features (Cloud Sync, Inventory, Competitions, Ammo Tracking). Replace emoji icons with SVGs. Fix iOS Safari hero background issue.
2. **Admin panel** — Add a "Sync Data" view that lets admins browse, edit, and soft-delete any user's synced entities (Weapons, Ammo, Competitions, Reminders, Ammo Transactions). Edits sync back to users via the existing last-modified-wins pull mechanism.

---

## Part 1: Landing Page Updates

### 1.1 Core Features Section (Free)

Add 2 new feature cards to the existing 4-card grid:

| Card | Icon (SVG) | i18n title key | i18n desc key |
|------|-----------|----------------|---------------|
| Inventory Management | `package` (Lucide) | `landing.feat_inventory_title` | `landing.feat_inventory_desc` |
| Competition Calendar | `trophy` (Lucide) | `landing.feat_competitions_title` | `landing.feat_competitions_desc` |

The grid goes from 4 to 6 cards. The existing `features-grid` CSS uses `grid-template-columns: repeat(2, 1fr)` which accommodates 6 cards naturally (3 rows of 2). On mobile it collapses to 1 column as before.

### 1.2 Premium Features Section

**Cloud Sync card:**
- Remove `coming-soon-card` class (which applies `opacity: 0.6`)
- Remove `<span class="coming-soon-badge">{{ $t('subscription.coming_soon') }}</span>` from the header
- No other changes — the card content and styling remain

**New Ammo Tracking card:**
Add a new premium card:

| Card | Icon (SVG) | i18n title key | i18n desc key |
|------|-----------|----------------|---------------|
| Ammo Tracking | `crosshair` (Lucide) or bullet-like icon | `landing.prem_ammo_title` | `landing.prem_ammo_desc` |

This card uses the same `border-warning-subtle` styling as other premium cards.

**Analytics & Charts card** — No change (stays as coming soon).

The premium grid goes from 6 to 7 cards (3.5 rows of 2 on desktop, 7 rows on mobile).

### 1.3 Pricing Card Updates

**Pro+ pricing card** — Add to the `<ul class="pricing-features">` list:
- `✓ Cloud Sync` — uses `landing.prem_sync_title`
- `✓ Ammo Tracking` — uses `landing.prem_ammo_title`

These appear after the existing 4 items ("Unlimited Sessions", "Shot Timer", "Export to Excel", "Custom Disciplines").

### 1.4 Replace Emoji Icons with SVGs

Every feature card currently uses an emoji span (e.g., `<span class="text-2xl mr-2">🎯</span>`). Replace all with inline SVG icons for cross-platform consistency and accessibility.

Mapping:
| Current Emoji | Replacement SVG (Lucide) | Context |
|--------------|-------------------------|---------|
| 🎯 | `target` | Precision Tracking |
| 🔒 | `shield-check` | Private by Default |
| ⏱️ | `timer` | Stopwatch |
| 🏆 | `award` | ISSF Included |
| ⚡ | `zap` | Shot Timer (premium) |
| 📥 | `file-spreadsheet` | Export to Excel (premium) |
| 💎 | `infinity` | Unlimited Sessions (premium) |
| 🛠️ | `settings` | Custom Disciplines (premium) |
| ☁️ | `cloud` | Cloud Sync (premium) |
| 📊 | `bar-chart-3` | Analytics & Charts (premium) |

SVGs should be 24×24, use `currentColor` for fill/stroke, and be wrapped in a `<span>` with the same `text-2xl mr-2` classes (or equivalent sizing). For premium cards, the existing `text-warning` class applies the accent color.

Implementation approach: Inline SVG directly in the template (no icon library dependency needed). Each icon is a small `<svg>` element with the Lucide path data.

### 1.5 Hero Background Fix

Remove `background-attachment: fixed` from the `.hero` CSS rule. This property:
- Does not work on iOS Safari (silently ignored)
- Causes scroll jank on some Android browsers
- Creates inconsistent experience across platforms

Replace with a static background. The parallax effect is not worth the cross-platform issues.

### 1.6 i18n Keys

New keys added to all 8 locale files (`en.json`, `es.json`, `de.json`, `ca.json`, `eu.json`, `fr.json`, `gl.json`, `pt.json`):

```
landing.feat_inventory_title     — "Inventory Management"
landing.feat_inventory_desc      — "Track your firearms and ammunition in one place."
landing.feat_competitions_title  — "Competition Calendar"
landing.feat_competitions_desc   — "Plan and manage your competition schedule with status tracking and countdown timers."
landing.prem_ammo_title          — "Ammo Tracking"
landing.prem_ammo_desc           — "Track ammo purchases, usage per session, and stock levels."
```

---

## Part 2: Admin Sync Entity Management

### 2.1 Architecture

A single admin view (`SyncDataView.vue`) with user selection and tabbed entity browsing, rather than 5 separate views. This keeps the admin nav uncluttered and matches the admin mental model: "show me User X's synced data."

**Backend:** One new controller `SyncDataController.php` with type-parameterized endpoints:
- `GET /admin/sync-data?user_id={uuid}&type={weapons|ammo|competitions|competition_reminders|ammo_transactions}` — List records
- `PUT /admin/sync-data/{uuid}?type={type}` — Update record fields + set `modified_at` to now
- `DELETE /admin/sync-data/{uuid}?type={type}` — Soft delete (set `deleted_at` + update `modified_at`)

**Sync-back mechanism:** When an admin edits a record, the server sets `modified_at = now()`. On the user's next pull, the sync client's `_isRemoteNewer()` check sees the server timestamp is newer and applies the change. No special admin-push mechanism needed.

### 2.2 Backend: SyncDataController

**File:** `src/Controller/Api/V1/Admin/SyncDataController.php`

**Route registration** in `config/routes.php` inside the existing admin scope:
```php
$admin->get('/sync-data', ['controller' => 'SyncData', 'action' => 'index']);
$admin->put('/sync-data/{id}', ['controller' => 'SyncData', 'action' => 'edit'])->setPass(['id']);
$admin->delete('/sync-data/{id}', ['controller' => 'SyncData', 'action' => 'delete'])->setPass(['id']);
```

**Controller behavior:**
- `ensureAdmin()` check on every action (same pattern as `UsersController`)
- `index()`: Requires `user_id` and `type` query params. Maps `type` to the CakePHP Table class name (e.g., `weapons` → `SyncWeapons`). Returns all non-deleted records for that user, ordered by `modified_at DESC`.
- `edit()`: Requires `type` query param. Loads record by UUID, patches allowed fields (see §2.4), sets `modified_at` to `now()`, saves.
- `delete()`: Requires `type` query param. Loads record by UUID, sets `deleted_at` to `now()` and `modified_at` to `now()`, saves. Does NOT hard-delete — the sync client needs the tombstone.

**Type-to-table mapping:**
```
weapons              → SyncWeapons
ammo                 → SyncAmmo
competitions         → SyncCompetitions
competition_reminders → SyncCompetitionReminders
ammo_transactions    → SyncAmmoTransactions
```

**Ownership validation:** The controller verifies that the record belongs to the specified `user_id`. Ownership strategy per entity:
- **Direct** (have `user_id` FK): `SyncWeapons`, `SyncAmmo`, `SyncCompetitions` — filter by `user_id` column
- **Via parent** (no `user_id` FK): `SyncCompetitionReminders` (via `SyncCompetitions.user_id` through `competition_uuid`), `SyncAmmoTransactions` (via `SyncAmmo.user_id` through `ammo_uuid`)

This prevents editing records belonging to other users even with admin access.

### 2.3 Backend: Tests

**File:** `tests/TestCase/Controller/Api/V1/Admin/SyncDataControllerTest.php`

Test cases:
- List records for a user+type (returns correct records)
- List with invalid type returns 400
- List without user_id returns 400
- Edit updates fields and sets `modified_at`
- Edit with invalid type returns 400
- Edit record belonging to different user returns 403
- Delete soft-deletes and updates `modified_at`
- Non-admin user gets 403

### 2.4 Editable Fields Per Entity

| Entity | Editable Fields |
|--------|----------------|
| SyncWeapons | `name`, `caliber`, `notes`, `is_favorite`, `is_archived` |
| SyncAmmo | `brand`, `name`, `caliber`, `grain_weight`, `current_stock`, `notes`, `is_archived` |
| SyncCompetitions | `name`, `date`, `end_date`, `location`, `discipline_id`, `status`, `notes` |
| SyncCompetitionReminders | `reminder_date`, `type` |
| SyncAmmoTransactions | `type`, `quantity`, `notes` |

Fields NOT editable by admin: `id` (UUID PK), `user_id`, `created_at`, `modified_at` (auto-set), `deleted_at` (managed by delete action), and FK UUIDs (e.g., `ammo_uuid`, `competition_uuid`, `session_uuid`).

### 2.5 Frontend: API Module

**File:** `client/src/api/admin.js` — Add:
```javascript
getSyncData: (userId, type) => api.get('/admin/sync-data', { params: { user_id: userId, type } }),
updateSyncData: (id, type, data) => api.put(`/admin/sync-data/${id}`, { ...data, type }),
deleteSyncData: (id, type) => api.delete(`/admin/sync-data/${id}`, { params: { type } }),
```

### 2.6 Frontend: SyncDataView.vue

**File:** `client/src/views/admin/SyncDataView.vue`

**Layout:**
1. **Header** — "Sync Data" title
2. **User selector** — Dropdown populated from `adminApi.getUsers()`. Shows email + name.
3. **Entity tabs** — Horizontal tab bar: Weapons | Ammo | Competitions | Reminders | Transactions
4. **Data table** — Standard admin table (follows `DevicesView.vue` pattern) showing records for the selected user+type
5. **Edit modal** — `AppModal` with form fields matching §2.4 editable fields
6. **Delete confirmation modal** — Same pattern as other admin views

**Table columns per entity type:**

| Tab | Columns |
|-----|---------|
| Weapons | Name, Caliber, Favorite, Archived, Modified |
| Ammo | Brand, Name, Caliber, Grain, Stock, Archived, Modified |
| Competitions | Name, Date, Location, Status, Modified |
| Reminders | Competition (UUID), Date, Type, Modified |
| Transactions | Ammo (UUID), Type, Qty, Modified |

**Behavior:**
- Selecting a user triggers loading data for the active tab
- Switching tabs triggers a new API call for that type
- Edit modal pre-fills with current values; save calls `updateSyncData()`
- Delete shows confirmation, calls `deleteSyncData()`, refreshes table
- Loading/error/empty states follow existing admin view patterns

### 2.7 Frontend: Router + Nav

**Router** (`client/src/router/index.js`):
```javascript
{
    path: 'sync-data',
    name: 'AdminSyncData',
    meta: { requiresSuperAdmin: true, title: 'Admin | Sync Data' },
    component: () => import('../views/admin/SyncDataView.vue')
}
```

**Nav** (`AdminLayout.vue`): Add tab after "Remote Configs":
```html
<router-link v-if="auth.isAdmin" to="/admin/sync-data" class="admin-tab" active-class="active">Sync Data</router-link>
```

### 2.8 i18n Keys

New keys in all 8 locale files under the `admin` namespace:

```
admin.sync_data_title        — "Sync Data"
admin.sync_data_subtitle     — "Browse and edit synced user data"
admin.select_user            — "Select User"
admin.tab_weapons            — "Weapons"
admin.tab_ammo               — "Ammo"
admin.tab_competitions       — "Competitions"
admin.tab_reminders          — "Reminders"
admin.tab_transactions       — "Transactions"
admin.edit_sync_record       — "Edit Record"
admin.delete_sync_record     — "Delete Record"
admin.delete_sync_confirm    — "Are you sure you want to delete this record? It will be removed from the user's device on next sync."
admin.no_sync_data           — "No synced data found for this user."
admin.sync_modified_at       — "Last Modified"
```

---

## Out of Scope

- Admin creating new sync records (only edit/delete existing ones)
- Bulk operations (select multiple records and delete)
- Real-time sync push to the user's device (relies on next pull)
- Changes to the existing SyncService push/pull logic
- Changes to the Flutter app
