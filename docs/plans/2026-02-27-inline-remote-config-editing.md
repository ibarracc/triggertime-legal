# Inline Remote Config Key Editing — Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Allow admins to edit, add, and delete individual remote config keys inline from the detail view, without uploading a new JSON file.

**Architecture:** Frontend-only changes to `RemoteConfigDetailView.vue`. The existing key-value table becomes editable. Each change updates local state and sends the full `config_data` object via the existing `PUT /admin/remote-config/{id}` endpoint. No backend changes. Existing JSON upload + diff flow preserved.

**Tech Stack:** Vue 3 (Composition API), existing AppButton component, existing adminApi.updateRemoteConfig

**Design doc:** `docs/plans/2026-02-27-inline-remote-config-editing-design.md`

---

### Task 1: Add Actions Column and Inline Edit State

Add an "Actions" column to the config table with Edit/Delete buttons per row. Wire up the reactive state for tracking which row is being edited.

**Files:**
- Modify: `client/src/views/admin/RemoteConfigDetailView.vue`

**Step 1: Add new reactive state to the script section**

After `const fileInputRef = ref(null)` (line 196), add:

```javascript
// Inline editing state
const editingKey = ref(null)
const editValue = ref('')
const editJsonError = ref('')
const deletingKey = ref(null)
const savingKey = ref(null)
const addingKey = ref(false)
const newKeyName = ref('')
const newKeyType = ref('string')
const newKeyValue = ref('')
const addKeyError = ref('')
```

**Step 2: Add helper functions**

After the new state declarations, add:

```javascript
const detectValueType = (value) => {
    if (typeof value === 'boolean') return 'boolean'
    if (typeof value === 'number') return 'number'
    if (typeof value === 'object' && value !== null) return 'json'
    return 'string'
}

const parseValueByType = (rawInput, type) => {
    switch (type) {
        case 'boolean': return rawInput
        case 'number': return Number(rawInput)
        case 'json': return JSON.parse(rawInput)
        default: return String(rawInput)
    }
}
```

**Step 3: Update the table template to add Actions column**

In the config-table `<colgroup>`, add a third `<col>`:

```html
<colgroup>
    <col class="key-col" />
    <col class="value-col" />
    <col class="actions-col" />
</colgroup>
```

Add the Actions header to `<thead>`:

```html
<tr>
    <th>Key</th>
    <th>Value</th>
    <th>Actions</th>
</tr>
```

Update the empty state `colspan` from `2` to `3`.

**Step 4: Add action buttons to each row**

In the `<tr v-for="(value, key) in currentConfigData">`, after the value `<td>`, add:

```html
<td class="actions-cell">
    <div class="flex gap-1">
        <button class="action-btn" @click="startEdit(key)" title="Edit">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
        </button>
        <button class="action-btn action-btn-danger" @click="confirmDelete(key)" title="Delete">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
        </button>
    </div>
</td>
```

**Step 5: Add placeholder methods**

```javascript
const startEdit = (key) => {
    editingKey.value = key
    const value = currentConfigData.value[key]
    const type = detectValueType(value)
    if (type === 'json') {
        editValue.value = JSON.stringify(value, null, 2)
    } else {
        editValue.value = value
    }
    editJsonError.value = ''
}

const cancelEdit = () => {
    editingKey.value = null
    editValue.value = ''
    editJsonError.value = ''
}

const confirmDelete = (key) => {
    deletingKey.value = key
}

const cancelDelete = () => {
    deletingKey.value = null
}
```

**Step 6: Add styles for the actions column**

Add to `<style scoped>`:

```css
.config-table .actions-col {
  width: 80px;
}

.config-table td.actions-cell {
  padding: 10px 8px;
  vertical-align: top;
  border-bottom: 1px solid var(--border-subtle);
  width: 80px;
}

.action-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 28px;
  height: 28px;
  border-radius: 6px;
  border: 1px solid var(--border-subtle);
  background: transparent;
  color: var(--text-secondary);
  cursor: pointer;
  transition: all 0.15s;
}

.action-btn:hover {
  background: rgba(255,255,255,0.08);
  color: var(--primary);
  border-color: var(--primary);
}

.action-btn-danger:hover {
  color: #f87171;
  border-color: #f87171;
}
```

**Step 7: Verify manually**

Run: `cd client && npm run dev`

Open the remote config detail page in the browser. Confirm:
- Three-column table: Key, Value, Actions
- Each row has pencil and trash icon buttons
- Buttons have hover effects
- Existing value display is unchanged

**Step 8: Commit**

```bash
git add client/src/views/admin/RemoteConfigDetailView.vue
git commit -m "feat: add actions column and edit state to remote config detail"
```

---

### Task 2: Inline Edit Mode for Rows

Make rows switch between view mode and edit mode. When editing, the value cell shows an appropriate input based on the value type.

**Files:**
- Modify: `client/src/views/admin/RemoteConfigDetailView.vue`

**Step 1: Replace the static row template with conditional edit/view modes**

Replace the existing `<tr v-for>` block (the one iterating `currentConfigData`) with:

```html
<tr v-for="(value, key) in currentConfigData" :key="key">
    <td class="key-cell">{{ key }}</td>

    <!-- Edit mode -->
    <td v-if="editingKey === key" class="value-cell edit-mode-cell">
        <!-- Boolean toggle -->
        <label v-if="typeof currentConfigData[key] === 'boolean'" class="boolean-toggle">
            <input type="checkbox" v-model="editValue" />
            <span class="toggle-label" :class="editValue ? 'text-green-400' : 'text-red-400'">{{ editValue }}</span>
        </label>
        <!-- JSON textarea -->
        <div v-else-if="detectValueType(currentConfigData[key]) === 'json'" class="json-edit-wrapper">
            <textarea v-model="editValue" class="edit-textarea" rows="6" spellcheck="false"></textarea>
            <div v-if="editJsonError" class="edit-json-error">{{ editJsonError }}</div>
        </div>
        <!-- String/Number input -->
        <input v-else type="text" v-model="editValue" class="edit-input"
            @keydown.enter="saveEdit" @keydown.escape="cancelEdit" />
    </td>

    <!-- View mode -->
    <td v-else class="value-cell">
        <pre v-if="typeof value === 'object'" class="value-pre">{{ JSON.stringify(value, null, 2) }}</pre>
        <span v-else-if="typeof value === 'boolean'" :class="value ? 'text-green-400 font-bold' : 'text-red-400 font-bold'">{{ value }}</span>
        <span v-else>{{ value }}</span>
    </td>

    <!-- Actions -->
    <td class="actions-cell">
        <div v-if="editingKey === key" class="flex gap-1">
            <button class="action-btn action-btn-save" @click="saveEdit" :disabled="savingKey === key" title="Save">
                <svg v-if="savingKey === key" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                <svg v-else class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            </button>
            <button class="action-btn" @click="cancelEdit" title="Cancel">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        <div v-else-if="deletingKey === key" class="delete-confirm">
            <span class="text-[10px] text-red-400">Delete?</span>
            <button class="action-btn action-btn-danger" @click="executeDelete" :disabled="savingKey === key" title="Confirm delete">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            </button>
            <button class="action-btn" @click="cancelDelete" title="Cancel">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        <div v-else class="flex gap-1">
            <button class="action-btn" @click="startEdit(key)" title="Edit">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
            </button>
            <button class="action-btn action-btn-danger" @click="confirmDelete(key)" title="Delete">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
            </button>
        </div>
    </td>
</tr>
```

**Step 2: Add styles for edit mode inputs**

Add to `<style scoped>`:

```css
.edit-mode-cell {
  max-width: 0;
  overflow: visible !important;
}

.edit-input {
  width: 100%;
  padding: 6px 10px;
  font-family: monospace;
  font-size: 11px;
  background: var(--bg-base);
  border: 1px solid var(--border-subtle);
  border-radius: 6px;
  color: var(--text-primary);
  outline: none;
  transition: border-color 0.15s;
}

.edit-input:focus {
  border-color: var(--primary);
  box-shadow: 0 0 0 2px rgba(var(--primary-rgb, 99,102,241), 0.15);
}

.edit-textarea {
  width: 100%;
  padding: 8px 10px;
  font-family: monospace;
  font-size: 11px;
  background: var(--bg-base);
  border: 1px solid var(--border-subtle);
  border-radius: 6px;
  color: var(--text-primary);
  outline: none;
  resize: vertical;
  line-height: 1.6;
  transition: border-color 0.15s;
}

.edit-textarea:focus {
  border-color: var(--primary);
}

.edit-json-error {
  margin-top: 4px;
  font-size: 10px;
  color: #f87171;
}

.boolean-toggle {
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
  font-family: monospace;
  font-size: 11px;
}

.boolean-toggle input[type="checkbox"] {
  width: 16px;
  height: 16px;
  accent-color: var(--primary);
  cursor: pointer;
}

.action-btn-save:hover {
  color: #4ade80;
  border-color: #4ade80;
}

.delete-confirm {
  display: flex;
  align-items: center;
  gap: 4px;
}
```

**Step 3: Verify manually**

In the browser:
- Click the pencil icon on a string key → text input appears with current value, actions change to checkmark/X
- Click pencil on a boolean key → checkbox appears
- Click pencil on a JSON key → textarea appears with formatted JSON
- Press Escape or X → reverts to view mode
- Click trash → "Delete?" inline confirmation appears with confirm/cancel

**Step 4: Commit**

```bash
git add client/src/views/admin/RemoteConfigDetailView.vue
git commit -m "feat: add inline edit mode with type-aware inputs"
```

---

### Task 3: Save Edit and Delete Operations

Wire up the actual save and delete methods that update `currentConfigData` and call the API.

**Files:**
- Modify: `client/src/views/admin/RemoteConfigDetailView.vue`

**Step 1: Implement `saveEdit` method**

Replace the placeholder `startEdit`/`cancelEdit` functions and add `saveEdit`. The full set of edit methods should be:

```javascript
const startEdit = (key) => {
    cancelDelete()
    cancelAdd()
    editingKey.value = key
    const value = currentConfigData.value[key]
    const type = detectValueType(value)
    if (type === 'json') {
        editValue.value = JSON.stringify(value, null, 2)
    } else {
        editValue.value = value
    }
    editJsonError.value = ''
}

const cancelEdit = () => {
    editingKey.value = null
    editValue.value = ''
    editJsonError.value = ''
}

const saveEdit = async () => {
    const key = editingKey.value
    if (!key) return

    const type = detectValueType(currentConfigData.value[key])
    let parsedValue
    try {
        parsedValue = parseValueByType(editValue.value, type)
    } catch (e) {
        editJsonError.value = 'Invalid JSON'
        return
    }

    // Snapshot for rollback
    const previousData = { ...currentConfigData.value }

    // Optimistic update
    currentConfigData.value = { ...currentConfigData.value, [key]: parsedValue }
    savingKey.value = key

    try {
        await adminApi.updateRemoteConfig(configId, { config_data: currentConfigData.value })
        cancelEdit()
    } catch (err) {
        currentConfigData.value = previousData
        error.value = err.response?.data?.message || 'Failed to save changes'
    } finally {
        savingKey.value = null
    }
}
```

**Step 2: Implement `executeDelete` method**

```javascript
const executeDelete = async () => {
    const key = deletingKey.value
    if (!key) return

    const previousData = { ...currentConfigData.value }
    const updated = { ...currentConfigData.value }
    delete updated[key]
    currentConfigData.value = updated
    savingKey.value = key

    try {
        await adminApi.updateRemoteConfig(configId, { config_data: currentConfigData.value })
        cancelDelete()
    } catch (err) {
        currentConfigData.value = previousData
        error.value = err.response?.data?.message || 'Failed to delete key'
    } finally {
        savingKey.value = null
    }
}
```

**Step 3: Verify manually**

In the browser:
- Edit a string key, change value, click checkmark → value updates, API call succeeds
- Edit a boolean, toggle checkbox, save → value updates
- Edit a JSON value, save valid JSON → updates; save invalid JSON → inline error shown
- Delete a key → key removed from table
- Open browser DevTools Network tab to confirm PUT calls with full `config_data`

**Step 4: Commit**

```bash
git add client/src/views/admin/RemoteConfigDetailView.vue
git commit -m "feat: implement save and delete for inline config editing"
```

---

### Task 4: Add Key Form

Add the "Add Key" button and inline form below the table for creating new config keys.

**Files:**
- Modify: `client/src/views/admin/RemoteConfigDetailView.vue`

**Step 1: Add the "Add Key" button and form below the table**

After the closing `</table>` tag (within the View Mode `<div>`), add:

```html
<!-- Add Key -->
<div v-if="!addingKey" class="add-key-trigger">
    <button class="add-key-btn" @click="startAdd">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
        Add Key
    </button>
</div>
<div v-else class="add-key-form">
    <div class="add-key-fields">
        <div class="add-key-field">
            <label class="add-key-label">Key Name</label>
            <input v-model="newKeyName" type="text" class="edit-input" placeholder="key_name"
                @keydown.escape="cancelAdd" />
        </div>
        <div class="add-key-field">
            <label class="add-key-label">Type</label>
            <select v-model="newKeyType" class="edit-input">
                <option value="string">String</option>
                <option value="number">Number</option>
                <option value="boolean">Boolean</option>
                <option value="json">JSON</option>
            </select>
        </div>
        <div class="add-key-field add-key-value-field">
            <label class="add-key-label">Value</label>
            <label v-if="newKeyType === 'boolean'" class="boolean-toggle">
                <input type="checkbox" v-model="newKeyValue" />
                <span class="toggle-label" :class="newKeyValue ? 'text-green-400' : 'text-red-400'">{{ newKeyValue }}</span>
            </label>
            <textarea v-else-if="newKeyType === 'json'" v-model="newKeyValue" class="edit-textarea" rows="4" placeholder="{}" spellcheck="false"></textarea>
            <input v-else type="text" v-model="newKeyValue" class="edit-input" placeholder="value"
                @keydown.enter="saveAdd" @keydown.escape="cancelAdd" />
        </div>
    </div>
    <div v-if="addKeyError" class="edit-json-error mb-2">{{ addKeyError }}</div>
    <div class="flex gap-2 justify-end">
        <AppButton variant="secondary" size="sm" @click="cancelAdd">Cancel</AppButton>
        <AppButton size="sm" @click="saveAdd" :loading="savingKey === '__new__'">Add</AppButton>
    </div>
</div>
```

**Step 2: Implement add key methods**

```javascript
const startAdd = () => {
    cancelEdit()
    cancelDelete()
    addingKey.value = true
    newKeyName.value = ''
    newKeyType.value = 'string'
    newKeyValue.value = ''
    addKeyError.value = ''
}

const cancelAdd = () => {
    addingKey.value = false
    newKeyName.value = ''
    newKeyType.value = 'string'
    newKeyValue.value = ''
    addKeyError.value = ''
}

const saveAdd = async () => {
    addKeyError.value = ''
    const keyName = newKeyName.value.trim()

    if (!keyName) {
        addKeyError.value = 'Key name is required'
        return
    }
    if (keyName in currentConfigData.value) {
        addKeyError.value = 'Key already exists'
        return
    }

    let parsedValue
    try {
        if (newKeyType.value === 'boolean') {
            parsedValue = !!newKeyValue.value
        } else {
            parsedValue = parseValueByType(newKeyValue.value || (newKeyType.value === 'json' ? '{}' : ''), newKeyType.value)
        }
    } catch (e) {
        addKeyError.value = 'Invalid JSON value'
        return
    }

    const previousData = { ...currentConfigData.value }
    currentConfigData.value = { ...currentConfigData.value, [keyName]: parsedValue }
    savingKey.value = '__new__'

    try {
        await adminApi.updateRemoteConfig(configId, { config_data: currentConfigData.value })
        cancelAdd()
    } catch (err) {
        currentConfigData.value = previousData
        error.value = err.response?.data?.message || 'Failed to add key'
    } finally {
        savingKey.value = null
    }
}
```

**Step 3: Add styles for the add key form**

```css
.add-key-trigger {
  padding: 12px;
  border-top: 1px dashed var(--border-subtle);
}

.add-key-btn {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 6px 12px;
  font-size: 12px;
  font-weight: 500;
  color: var(--text-secondary);
  background: transparent;
  border: 1px dashed var(--border-subtle);
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.15s;
}

.add-key-btn:hover {
  color: var(--primary);
  border-color: var(--primary);
  background: rgba(255,255,255,0.04);
}

.add-key-form {
  padding: 16px;
  border-top: 1px solid var(--border-subtle);
  background: rgba(255,255,255,0.02);
}

.add-key-fields {
  display: flex;
  gap: 12px;
  margin-bottom: 12px;
  align-items: flex-start;
}

.add-key-field {
  display: flex;
  flex-direction: column;
  gap: 4px;
  min-width: 0;
}

.add-key-value-field {
  flex: 1;
}

.add-key-label {
  font-size: 10px;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: var(--text-secondary);
  font-weight: 600;
}
```

**Step 4: Verify manually**

In the browser:
- Click "Add Key" → form appears with key name, type dropdown, value input
- Select Boolean type → value changes to checkbox
- Select JSON type → value changes to textarea
- Submit with empty key → "Key name is required" error
- Submit with duplicate key → "Key already exists" error
- Submit valid key → key appears in table, API call confirms

**Step 5: Commit**

```bash
git add client/src/views/admin/RemoteConfigDetailView.vue
git commit -m "feat: add 'Add Key' form for new config entries"
```

---

### Task 5: Final Polish

Ensure edit/delete/add operations are mutually exclusive, and clean up any edge cases.

**Files:**
- Modify: `client/src/views/admin/RemoteConfigDetailView.vue`

**Step 1: Update `confirmDelete` to cancel other modes**

The `confirmDelete` method should cancel any active edit or add:

```javascript
const confirmDelete = (key) => {
    cancelEdit()
    cancelAdd()
    deletingKey.value = key
}
```

**Step 2: Hide action buttons during compare mode**

The actions column should not show edit/delete when the diff view is active. This is already handled because the entire config-table is inside `v-if="!isComparing"`.

No change needed — verify this is the case.

**Step 3: Verify full flow manually**

Test the complete flow:
1. Edit a string value → save → value updates
2. Edit a boolean → toggle → save → value updates
3. Edit a JSON object → modify → save → value updates
4. Edit JSON with invalid syntax → save → inline error, not saved
5. Delete a key → confirm → key gone
6. Add a new string key → appears in table
7. Add a new boolean key → appears with colored true/false
8. Add a new JSON key → appears with formatted JSON
9. Try adding duplicate key → error shown
10. Upload a JSON file → diff view still works as before
11. Download JSON → file includes all changes

**Step 4: Commit**

```bash
git add client/src/views/admin/RemoteConfigDetailView.vue
git commit -m "feat: finalize inline remote config editing with mutual exclusion"
```
