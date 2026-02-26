<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import PublicLayout from '@/components/layout/PublicLayout.vue'
import DashboardLayout from '@/components/layout/DashboardLayout.vue'

const route = useRoute()
const auth = useAuthStore()

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
</template>
