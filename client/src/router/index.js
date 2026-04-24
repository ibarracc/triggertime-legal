import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import i18n from '@/i18n'
import LandingPage from '../views/landing/LandingPage.vue'
import { useAnalytics } from '@/composables/useAnalytics'

const SUPPORTED_LOCALES = ['en', 'es', 'de', 'fr', 'pt', 'eu', 'ca', 'gl']

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
        component: isLandingDomain
            ? LandingPage
            : () => import('../views/dashboard/DashboardHome.vue'),
        meta: { requiresAuth: !isLandingDomain, requiresVerified: !isLandingDomain }
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
        path: '/verify-email',
        name: 'VerifyEmail',
        component: () => import('../views/public/VerifyEmailView.vue'),
        meta: { requiresAuth: true, title: 'Verify Email' }
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
        meta: { requiresAuth: true, requiresVerified: true, title: 'Dashboard' }
    },
    {
        path: '/dashboard/subscription',
        name: 'Subscription',
        component: () => import('../views/dashboard/SubscriptionView.vue'),
        meta: { requiresAuth: true, requiresVerified: true, title: 'Subscription' }
    },
    {
        path: '/dashboard/sessions',
        name: 'sessions',
        component: () => import('../views/dashboard/SessionsView.vue'),
        meta: { requiresAuth: true, requiresVerified: true, title: 'Sessions' }
    },
    {
        path: '/dashboard/sessions/:uuid',
        name: 'session-detail',
        component: () => import('../views/dashboard/SessionDetailView.vue'),
        meta: { requiresAuth: true, requiresVerified: true, title: 'Session Detail' }
    },
    {
        path: '/dashboard/devices',
        name: 'Devices',
        component: () => import('../views/dashboard/DevicesView.vue'),
        meta: { requiresAuth: true, requiresVerified: true, title: 'My Devices' }
    },
    {
        path: '/dashboard/profile',
        name: 'Profile',
        component: () => import('../views/dashboard/ProfileView.vue'),
        meta: { requiresAuth: true, requiresVerified: true, title: 'Profile' }
    },

    // Admin Routes
    {
        path: '/admin',
        component: () => import('../views/admin/AdminLayout.vue'),
        meta: { requiresAuth: true, requiresVerified: true, requiresAdminRole: true },
        children: [
            {
                path: '',
                name: 'AdminRoot',
                redirect: '/admin/dashboard'
            },
            {
                path: 'dashboard',
                name: 'AdminDashboard',
                meta: { title: 'Admin | Dashboard' },
                component: () => import('../views/admin/AdminDashboardView.vue')
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
                meta: { title: 'Admin | Licenses' },
                component: () => import('../views/admin/LicensesView.vue')
            },
            {
                path: 'licenses/import',
                name: 'AdminLicensesImport',
                meta: { title: 'Admin | Import Licenses' },
                component: () => import('../views/admin/LicenseImportView.vue')
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
            },
            {
                path: 'sync-data',
                name: 'AdminSyncData',
                meta: { requiresSuperAdmin: true, title: 'Admin | Sync Data' },
                component: () => import('../views/admin/SyncDataView.vue')
            },
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

const router = createRouter({
    history: createWebHistory('/'),
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

router.beforeEach(async (to, from) => {
    const authStore = useAuthStore()

    // Handle ?lang= query param from external links
    const langParam = to.query.lang
    if (langParam && SUPPORTED_LOCALES.includes(langParam)) {
        if (!authStore.isAuthenticated) {
            // Apply locale for unauthenticated users
            i18n.global.locale.value = langParam
            localStorage.setItem('preferredLanguage', langParam)
            document.documentElement.lang = langParam
        }
        // Always strip ?lang= from URL (clean up regardless of auth state)
        const { lang, ...remainingQuery } = to.query
        return { ...to, query: remainingQuery, replace: true }
    }

    if (to.meta.requiresAuth && !authStore.isAuthenticated) {
        return { name: 'Login', query: { redirect: to.fullPath } }
    } else if (to.meta.requiresGuest && authStore.isAuthenticated) {
        return { name: 'Dashboard' }
    }

    // Ensure user data is loaded before checking roles/verification
    if (authStore.isAuthenticated) {
        await authStore.ensureUser()
    }

    // Redirect unverified users to verification page
    if (to.meta.requiresVerified && authStore.isAuthenticated && !authStore.isVerified) {
        if (authStore.user !== null) {
            return { name: 'VerifyEmail', query: to.query }
        }
    }

    // Redirect verified users away from verify-email page
    if (to.name === 'VerifyEmail' && authStore.isAuthenticated && authStore.isVerified) {
        return { name: 'Dashboard' }
    }

    if (to.meta.requiresAdminRole && !authStore.isAdmin && !authStore.isClubAdmin) {
        return { name: 'Dashboard' }
    }

    if (to.meta.requiresSuperAdmin && !authStore.isAdmin) {
        return { name: 'AdminLicenses' } // redirect club admins trying to poke around
    }
})

router.afterEach((to) => {
    const title = to.meta.title
    document.title = title ? `TriggerTime | ${title}` : 'TriggerTime'

    const { trackEvent } = useAnalytics()
    trackEvent('page_view', {
        page_title: document.title,
        page_path: to.fullPath,
    })
})

export default router
