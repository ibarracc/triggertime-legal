# Inline Remote Config Key Editing

## Problem

Editing remote config values currently requires uploading an entire replacement JSON file. Admins need to edit individual keys directly from the admin panel for quick tweaks.

## Approach

Frontend-only changes to `RemoteConfigDetailView.vue`. The existing key-value table becomes editable inline. Each row edit sends the full updated `config_data` via the existing `PUT /admin/remote-config/{id}` endpoint. No backend changes needed. The existing JSON upload + diff flow is preserved as an alternative for bulk updates.

## UI Changes to Key-Value Table

Each row gains Edit (pencil) and Delete (trash) action buttons.

**Edit mode** (one row at a time):
- Key name is read-only (delete and re-add to rename)
- Input based on value type:
  - String/Number: text input
  - Boolean: toggle switch
  - Object/Array: textarea with formatted JSON (validated before save)
- Save (checkmark) and Cancel (X) buttons
- Enter saves, Escape cancels (for text/number inputs)

**Delete flow:**
- Inline confirmation on the row ("Delete this key?" with Confirm/Cancel)
- On confirm, key is removed and saved to API immediately

**Add key** button below the table opens an inline form:
- Text input for key name (validated: no duplicates, not empty)
- Dropdown to select type (String, Number, Boolean, JSON)
- Appropriate input for the selected type
- Save/Cancel buttons

## Save Behavior

Each edit/add/delete immediately updates local `currentConfigData` state, then sends the full object via the existing `PUT` endpoint. On API failure, local state reverts and the existing error banner displays.

## Interaction Details

- Only one row in edit mode at a time; editing another cancels the current
- JSON textarea validates before saving; shows inline error if invalid
- Loading state on the row being saved (disabled button, spinner)
- No changes to: metadata sidebar, download JSON, upload JSON + diff flow, backend API, other views

## Component Structure

All changes in `RemoteConfigDetailView.vue`. No new components.

**New state:** `editingKey`, `editValue`, `addingKey`, `newKeyName`, `newKeyType`, `newKeyValue`, `deletingKey`, `savingKey`

**New methods:** `startEdit`, `cancelEdit`, `saveEdit`, `confirmDelete`, `executeDelete`, `cancelDelete`, `startAdd`, `cancelAdd`, `saveAdd`, `detectValueType`, `parseValueByType`

**Styling:** Extends existing `.config-table` styles for edit inputs, inline confirmation, and add-key form. Follows existing conventions (glass-card, monospace, color scheme).
