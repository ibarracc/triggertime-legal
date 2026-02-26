<template>
  <div class="public-layout">
    <AppHeader />
    <main class="public-main">
      <router-view />
    </main>
    <AppFooter />
  </div>
</template>

<script setup>
import AppHeader from '@/components/layout/AppHeader.vue'
import AppFooter from '@/components/layout/AppFooter.vue'
</script>

<style scoped>
.public-layout {
  display: flex;
  flex-direction: column;
  min-height: 100vh;
}

.public-main {
  flex: 1;
  padding-top: 80px; /* Offset for fixed header */
}
</style>
