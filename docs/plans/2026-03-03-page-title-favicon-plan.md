# Page Title & Favicon Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Replace the placeholder `<title>client</title>` and Vite SVG favicon with the TriggerTime branding, and add dynamic per-page titles via Vue Router.

**Architecture:** Two file changes only — update `index.html` for the favicon and static fallback title, then add `meta.title` to every route in `router/index.js` plus a single `router.afterEach` guard that writes `document.title`.

**Tech Stack:** Vue 3, Vue Router 4, static HTML

---

### Task 1: Update `index.html` — favicon and base title

**Files:**
- Modify: `client/index.html`

**Step 1: Open the file and make the changes**

Replace the contents of `client/index.html` with:

```html
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <link rel="icon" type="image/png" href="/triggertime.png" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>TriggerTime</title>
  </head>
  <body>
    <div id="app"></div>
    <script type="module" src="/src/main.js"></script>
  </body>
</html>
```

Changes:
- `href="/vite.svg"` → `href="/triggertime.png"` and `type="image/svg+xml"` → `type="image/png"`
- `<title>client</title>` → `<title>TriggerTime</title>`

**Step 2: Verify in browser**

Run `npm run dev` from `client/` and open the app. The browser tab should show the TriggerTime logo as favicon and "TriggerTime" as the tab title.

**Step 3: Commit**

```bash
git add client/index.html
git commit -m "feat: update favicon and base page title"
```

---

### Task 2: Add `meta.title` to all routes

**Files:**
- Modify: `client/src/router/index.js`

**Step 1: Add `title` to each route's `meta`**

Update the `routes` array. Add or extend `meta` on every route as shown below. Routes that already have `meta` should have `title` added to the existing object.

```js
const routes = [
    // Marketing / Landing
    {
        path: '/',
        name: 'Home',
        component: () => isLandingDomain
            ? import('../views/landing/LandingPage.vue')
            : import('../views/dashboard/DashboardHome.vue'),
        meta: { requiresAuth: !isLandingDomain }
        // No title — defaults to bare "TriggerTime"
    },
    {
        path: '/privacy',
        name: 'Privacy',
        component: () => import('../views/public/PrivacyPolicy.vue'),
        meta: { requiresAuth: false, title: 'Privacy Policy' }
    },
    {
        path: '/terms',
        name: 'Terms',
        component: () => import('../views/public/TermsOfService.vue'),
        meta: { requiresAuth: false, title: 'Terms of Service' }
    },

    // Public App Routes
    {
        path: '/login',
        name: 'Login',
        component: () => import('../views/public/LoginView.vue'),
        meta: { requiresGuest: true, title: 'Login' }
    },
    {
        path: '/register',
        name: 'Register',
        component: () => import('../views/public/RegisterView.vue'),
        meta: { requiresGuest: true, title: 'Register' }
    },
    {
        path: '/forgot-password',
        name: 'ForgotPassword',
        component: () => import('../views/public/ForgotPasswordView.vue'),
        meta: { requiresGuest: true, title: 'Forgot Password' }
    },
    {
        path: '/reset-password/:token',
        name: 'ResetPassword',
        component: () => import('../views/public/ResetPasswordView.vue'),
        meta: { requiresGuest: true, title: 'Reset Password' }
    },
    {
        path: '/upgrade',
        name: 'UpgradeRedirect',
        redirect: to => {
            const token = to.query.token
            if (token) return `/checkout/${token}`
            return '/login'
        }
        // No meta — redirect route, never rendered
    },
    {
        path: '/checkout/:token',
        name: 'CheckoutLanding',
        component: () => import('../views/public/CheckoutLanding.vue'),
        meta: { title: 'Checkout' }
    },
    {
        path: '/checkout-success',
        name: 'CheckoutSuccess',
        component: () => import('../views/public/CheckoutSuccessView.vue'),
        meta: { title: 'Checkout Complete' }
    },

    // Authenticated Dashboard Routes
    {
        path: '/dashboard',
        name: 'Dashboard',
        component: () => import('../views/dashboard/DashboardHome.vue'),
        meta: { requiresAuth: true, title: 'Dashboard' }
    },
    {
        path: '/dashboard/subscription',
        name: 'Subscription',
        component: () => import('../views/dashboard/SubscriptionView.vue'),
        meta: { requiresAuth: true, title: 'Subscription' }
    },
    {
        path: '/dashboard/devices',
        name: 'Devices',
        component: () => import('../views/dashboard/DevicesView.vue'),
        meta: { requiresAuth: true, title: 'My Devices' }
    },
    {
        path: '/dashboard/profile',
        name: 'Profile',
        component: () => import('../views/dashboard/ProfileView.vue'),
        meta: { requiresAuth: true, title: 'Profile' }
    },

    // Admin Routes
    {
        path: '/admin',
        component: () => import('../views/admin/AdminLayout.vue'),
        meta: { requiresAuth: true, requiresAdminRole: true },
        children: [
            {
                path: '',
                name: 'AdminRoot',
                redirect: '/admin/dashboard'
            },
            {
                path: 'dashboard',
                name: 'AdminDashboard',
                component: () => import('../views/admin/AdminDashboardView.vue'),
                meta: { title: 'Admin | Dashboard' }
            },
            {
                path: 'users',
                name: 'AdminUsers',
                meta: { requiresSuperAdmin: true, title: 'Admin | Users' },
                component: () => import('../views/admin/UsersView.vue')
            },
            {
                path: 'licenses',
                name: 'AdminLicenses',
                component: () => import('../views/admin/LicensesView.vue'),
                meta: { title: 'Admin | Licenses' }
            },
            {
                path: 'licenses/import',
                name: 'AdminLicensesImport',
                component: () => import('../views/admin/LicenseImportView.vue'),
                meta: { title: 'Admin | Import Licenses' }
            },
            {
                path: 'devices',
                name: 'AdminDevices',
                meta: { requiresSuperAdmin: true, title: 'Admin | Devices' },
                component: () => import('../views/admin/DevicesView.vue')
            },
            {
                path: 'instances',
                name: 'AdminInstances',
                meta: { requiresSuperAdmin: true, title: 'Admin | Instances' },
                component: () => import('../views/admin/InstancesView.vue')
            },
            {
                path: 'versions',
                name: 'AdminVersions',
                meta: { requiresSuperAdmin: true, title: 'Admin | Versions' },
                component: () => import('../views/admin/VersionsView.vue')
            },
            {
                path: 'subscriptions',
                name: 'AdminSubscriptions',
                meta: { requiresSuperAdmin: true, title: 'Admin | Subscriptions' },
                component: () => import('../views/admin/SubscriptionsView.vue')
            },
            {
                path: 'remote-configs',
                name: 'AdminRemoteConfigs',
                meta: { requiresSuperAdmin: true, title: 'Admin | Remote Configs' },
                component: () => import('../views/admin/RemoteConfigsView.vue')
            },
            {
                path: 'remote-configs/:id',
                name: 'AdminRemoteConfigDetail',
                meta: { requiresSuperAdmin: true, title: 'Admin | Remote Config' },
                component: () => import('../views/admin/RemoteConfigDetailView.vue')
            }
        ]
    },
    // Catch-all 404
    {
        path: '/:pathMatch(.*)*',
        name: 'NotFound',
        component: () => import('../views/public/NotFoundView.vue'),
        meta: { title: 'Not Found' }
    }
]
```

**Note on child route meta:** For nested admin routes, Vue Router merges parent and child `meta`. The child `meta.title` will be the value on `to.meta.title` in the guard — no special handling needed.

**Step 2: Add the `afterEach` guard**

After the `router.beforeEach(...)` block (around line 232), add:

```js
router.afterEach((to) => {
    const title = to.meta.title
    document.title = title ? `TriggerTime | ${title}` : 'TriggerTime'
})
```

**Step 3: Verify manually**

With `npm run dev` running, navigate to several pages and confirm the tab title updates correctly:
- `/` → `TriggerTime`
- `/login` → `TriggerTime | Login`
- `/dashboard` → `TriggerTime | Dashboard`
- `/admin/dashboard` → `TriggerTime | Admin | Dashboard`
- `/admin/licenses` → `TriggerTime | Admin | Licenses`
- A nonexistent URL → `TriggerTime | Not Found`

**Step 4: Commit**

```bash
git add client/src/router/index.js
git commit -m "feat: add dynamic page titles via router meta"
```
