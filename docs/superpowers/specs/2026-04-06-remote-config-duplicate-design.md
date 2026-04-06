# Remote Config Duplicate Feature — Design Spec

## Overview

Add the ability to duplicate an existing remote config to a different instance/version combination. The feature consists of a dedicated backend endpoint and a modal in the config detail view.

## Backend — New Duplicate Endpoint

**Route:** `POST /admin/remote-config/:id/duplicate`

**Request body:**

```json
{
  "instance_id": "uuid-string",
  "version_id": 123
}
```

- `instance_id` — required UUID, target instance
- `version_id` — optional bigint, target version. `null` means global (all versions)

**Controller logic (`RemoteConfigController::duplicate`):**

1. Load the source config by `:id` (404 if not found)
2. Validate `instance_id` exists (422 if missing or invalid)
3. Validate `version_id` belongs to the selected instance, if provided (422 if mismatch)
4. Check uniqueness — no existing config for that `instance_id + version_id` pair (422 if duplicate)
5. Create a new `RemoteConfig` with:
   - `instance_id` from request
   - `version_id` from request (or null)
   - `config_data` copied from source config
   - `app_instance` auto-populated from instance name
6. Return the new config with `201 Created`

**Error responses:**

| Status | Condition |
|--------|-----------|
| 404 | Source config not found |
| 422 | Missing `instance_id`, version doesn't belong to instance, or duplicate instance/version pair |

## Frontend — Duplicate Button & Modal

### Button Placement

In `RemoteConfigDetailView.vue`, next to the "Download JSON" button in the header's `flex gap-2` container. Visible only when not in compare mode (same condition as Download JSON). Uses a copy/duplicate SVG icon.

### Modal Contents

- **Instance selector** — dropdown of all instances, pre-selected to the current config's instance
- **Version selector** — dropdown filtered by selected instance, disabled until instance is selected. Includes a "Global (All Versions)" option that sends `null` for `version_id`
- **Submit button** — "Duplicate" label, shows loading state during request
- **Error display** — inline error messages for validation failures (e.g., "A config already exists for this instance/version")

### User Flow

1. User clicks "Duplicate" button
2. Modal opens with instance pre-selected, versions loaded for that instance
3. Changing instance reloads the version dropdown
4. User selects version (or leaves as Global)
5. User clicks "Duplicate"
6. `POST /admin/remote-config/{id}/duplicate` with `{ instance_id, version_id }`
7. On success → navigate to the new config's detail view
8. On error → show validation message in modal

### API Module

Add to `client/src/api/admin.js`:

```javascript
duplicateRemoteConfig: (id, data) => api.post(`/admin/remote-config/${id}/duplicate`, data)
```

## Testing

Backend tests in `RemoteConfigControllerTest`:

- Duplicate succeeds — creates new config with copied `config_data`, returns 201
- Duplicate with non-existent source — returns 404
- Duplicate to existing instance/version pair — returns 422
- Duplicate with version not belonging to selected instance — returns 422
- Duplicate without `instance_id` — returns 422
- Duplicate to same instance but different version — succeeds
- Duplicate to different instance — succeeds, `app_instance` field updated to new instance name

## Files to Modify

**Backend:**
- `config/routes.php` — add custom `duplicate` route for RemoteConfig
- `src/Controller/Api/V1/Admin/RemoteConfigController.php` — add `duplicate()` method

**Frontend:**
- `client/src/api/admin.js` — add `duplicateRemoteConfig` method
- `client/src/views/admin/RemoteConfigDetailView.vue` — add duplicate button + modal + logic
