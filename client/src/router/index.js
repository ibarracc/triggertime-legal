import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const isLandingDomain = [
    'triggertime.es',
    'www.triggertime.es',
    'localhost',
    '127.0.0.1',
    'ddev.site'
].some(domain => window.location.hostname.includes(domain))

const routes = [
    // Marketing / Landing (triggertime.es)
    {
        path: '/',
        name: 'Home',
        component: () => isLandingDomain
            ? import('../views/landing/LandingPage.vue')
            : import('../views/dashboard/DashboardHome.vue'),
        meta: { requiresAuth: !isLandingDomain }
    },
    {
        path: '/privacy',
        name: 'Privacy',
        component: () => import('../views/public/PrivacyPolicy.vue'),
        meta: { requiresAuth: false }
    },

    // Public App Routes
    {
        path: '/login',
        name: 'Login',
        component: () => import('../views/public/LoginView.vue'),
        meta: { requiresGuest: true }
    },
    {
        path: '/register',
        name: 'Register',
        component: () => import('../views/public/RegisterView.vue'),
        meta: { requiresGuest: true }
    },
    {
        path: '/forgot-password',
        name: 'ForgotPassword',
        component: () => import('../views/public/ForgotPasswordView.vue'),
        meta: { requiresGuest: true }
    },
    {
        path: '/reset-password/:token',
        name: 'ResetPassword',
        component: () => import('../views/public/ResetPasswordView.vue'),
        meta: { requiresGuest: true }
    },
    {
        path: '/upgrade',
        name: 'UpgradeRedirect',
        redirect: to => {
            const token = to.query.token
            if (token) return `/checkout/${token}`
            return '/login'
        }
    },
    {
        path: '/checkout/:token',
        name: 'CheckoutLanding',
        component: () => import('../views/public/CheckoutLanding.vue')
    },
    {
        path: '/checkout-success',
        name: 'CheckoutSuccess',
        component: () => import('../views/public/CheckoutSuccessView.vue')
    },

    // Authenticated Dashboard Routes
    {
        path: '/dashboard',
        name: 'Dashboard',
        component: () => import('../views/dashboard/DashboardHome.vue'),
        meta: { requiresAuth: true }
    },
    {
        path: '/dashboard/subscription',
        name: 'Subscription',
        component: () => import('../views/dashboard/SubscriptionView.vue'),
        meta: { requiresAuth: true }
    },
    {
        path: '/dashboard/devices',
        name: 'Devices',
        component: () => import('../views/dashboard/DevicesView.vue'),
        meta: { requiresAuth: true }
    },
    {
        path: '/dashboard/profile',
        name: 'Profile',
        component: () => import('../views/dashboard/ProfileView.vue'),
        meta: { requiresAuth: true }
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
                component: () => import('../views/admin/AdminDashboardView.vue')
            },
            {
                path: 'users',
                name: 'AdminUsers',
                meta: { requiresSuperAdmin: true },
                component: () => import('../views/admin/UsersView.vue')
            },
            {
                path: 'licenses',
                name: 'AdminLicenses',
                component: () => import('../views/admin/LicensesView.vue')
            },
            {
                path: 'licenses/import',
                name: 'AdminLicensesImport',
                component: () => import('../views/admin/LicenseImportView.vue')
            },
            {
                path: 'devices',
                name: 'AdminDevices',
                meta: { requiresSuperAdmin: true },
                component: () => import('../views/admin/DevicesView.vue')
            },
            {
                path: 'instances',
                name: 'AdminInstances',
                meta: { requiresSuperAdmin: true },
                component: () => import('../views/admin/InstancesView.vue')
            },
            {
                path: 'versions',
                name: 'AdminVersions',
                meta: { requiresSuperAdmin: true },
                component: () => import('../views/admin/VersionsView.vue')
            },
            {
                path: 'subscriptions',
                name: 'AdminSubscriptions',
                meta: { requiresSuperAdmin: true },
                component: () => import('../views/admin/SubscriptionsView.vue')
            },
            {
                path: 'remote-configs',
                name: 'AdminRemoteConfigs',
                meta: { requiresSuperAdmin: true },
                component: () => import('../views/admin/RemoteConfigsView.vue')
            },
            {
                path: 'remote-configs/:id',
                name: 'AdminRemoteConfigDetail',
                meta: { requiresSuperAdmin: true },
                component: () => import('../views/admin/RemoteConfigDetailView.vue')
            }
        ]
    },
    // Catch-all 404
    {
        path: '/:pathMatch(.*)*',
        name: 'NotFound',
        component: () => import('../views/public/NotFoundView.vue')
    }
]

const router = createRouter({
    history: createWebHistory(),
    routes,
    scrollBehavior(to, from, savedPosition) {
        if (to.hash) {
            return {
                el: to.hash,
                behavior: 'smooth',
                top: 80,
            }
        }
        return { top: 0 }
    }
})

router.beforeEach((to, from) => {
    const authStore = useAuthStore()

    if (to.meta.requiresAuth && !authStore.isAuthenticated) {
        return { name: 'Login', query: { redirect: to.fullPath } }
    } else if (to.meta.requiresGuest && authStore.isAuthenticated) {
        return { name: 'Dashboard' }
    }

    if (to.meta.requiresAdminRole && !authStore.isAdmin && !authStore.isClubAdmin) {
        return { name: 'Dashboard' }
    }

    if (to.meta.requiresSuperAdmin && !authStore.isAdmin) {
        return { name: 'AdminLicenses' } // redirect club admins trying to poke around
    }
})

export default router
