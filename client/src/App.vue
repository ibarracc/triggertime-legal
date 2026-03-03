<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'
import PublicLayout from '@/components/layout/PublicLayout.vue'
import DashboardLayout from '@/components/layout/DashboardLayout.vue'
import CookieConsent from '@/components/CookieConsent.vue'

const route = useRoute()
const auth = useAuthStore()
const { locale } = useI18n()

const SUPPORTED_LOCALES = ['en', 'es', 'de', 'fr', 'pt', 'eu', 'ca', 'gl']

watch(() => auth.user, (user) => {
  if (user?.language && SUPPORTED_LOCALES.includes(user.language)) {
    locale.value = user.language
    localStorage.setItem('preferredLanguage', user.language)
    document.documentElement.lang = user.language
  }
})

const layoutComponent = computed(() => {
  if (route.path.startsWith('/dashboard') || route.path.startsWith('/admin')) {
    return DashboardLayout
  }
  return PublicLayout
})

onMounted(() => {
  if (auth.isAuthenticated) {
    auth.fetchUser()
  }
})
</script>

<template>
  <component :is="layoutComponent">
    <router-view />
  </component>
  <CookieConsent />
</template>
