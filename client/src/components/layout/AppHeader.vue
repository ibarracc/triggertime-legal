<template>
  <header class="app-header" :class="{ 'scrolled': isScrolled }">
    <div class="container flex justify-between items-center h-full relative">
      <router-link to="/" class="logo flex items-center gap-2">
        <img src="/triggertime.png" alt="TriggerTime Logo" class="logo-img">
        <span class="logo-text">Trigger<span class="highlight">Time</span></span>
      </router-link>
      
      <!-- Mobile Menu Toggle -->
      <button class="mobile-menu-toggle" @click="mobileMenuOpen = !mobileMenuOpen">
        <svg v-if="!mobileMenuOpen" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <line x1="3" y1="12" x2="21" y2="12"></line>
          <line x1="3" y1="6" x2="21" y2="6"></line>
          <line x1="3" y1="18" x2="21" y2="18"></line>
        </svg>
        <svg v-else width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <line x1="18" y1="6" x2="6" y2="18"></line>
          <line x1="6" y1="6" x2="18" y2="18"></line>
        </svg>
      </button>

      <nav class="desktop-nav" :class="{ 'is-open': mobileMenuOpen }">
        <div class="nav-links-wrapper">
          <router-link to="/#features" class="nav-link" @click="mobileMenuOpen = false">{{ $t('nav.features') }}</router-link>
          <router-link to="/#premium" class="nav-link" @click="mobileMenuOpen = false">{{ $t('nav.premium') }}</router-link>
          <router-link to="/#pricing" class="nav-link" @click="mobileMenuOpen = false">{{ $t('nav.pricing') }}</router-link>
          <router-link to="/#whitelabel" class="nav-link" @click="mobileMenuOpen = false">{{ $t('nav.whitelabel') }}</router-link>
        </div>
      
        <div class="auth-buttons flex gap-4 items-center">
        <!-- Language Selector -->
        <div class="language-selector relative">
          <button class="lang-btn" @click.stop="showLangMenu = !showLangMenu" type="button">
            <span class="flag">{{ currentLocale.flag }}</span>
            <span class="lang-code uppercase">{{ currentLocale.code.toUpperCase() }}</span>
            <span class="chevron" :class="{ 'open': showLangMenu }">▼</span>
          </button>
          
          <transition name="fade">
            <div v-if="showLangMenu" class="lang-dropdown">
              <div 
                v-for="l in availableLocales" 
                :key="l.code" 
                class="lang-item" 
                :class="{ 'active': locale === l.code }"
                @click="setLocale(l.code); showLangMenu = false"
              >
                <span class="flag">{{ l.flag }}</span>
                <span class="name">{{ l.name }}</span>
              </div>
            </div>
          </transition>
        </div>

        <template v-if="auth.isAuthenticated">
          <router-link to="/dashboard" class="btn btn-primary btn-sm rounded-full">{{ $t('nav.dashboard') }}</router-link>
          <AppButton variant="secondary" size="sm" @click="handleLogout" class="rounded-full">{{ $t('common.logout') }}</AppButton>
        </template>
        <template v-else>
          <router-link to="/login" class="nav-link login-link">{{ $t('common.login') }}</router-link>
          <router-link to="/register" class="btn btn-primary btn-sm rounded-btn">{{ $t('common.register') }}</router-link>
        </template>
      </div>
      </nav>
    </div>
  </header>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useRouter } from 'vue-router'
import { useLocale } from '@/composables/useLocale'
import AppButton from '@/components/ui/AppButton.vue'

const auth = useAuthStore()
const router = useRouter()
const { locale, currentLocale, availableLocales, setLocale } = useLocale()

const isScrolled = ref(false)
const showLangMenu = ref(false)
const mobileMenuOpen = ref(false)

const handleLogout = () => {
  auth.logout()
  router.push('/login')
}

const handleScroll = () => {
  isScrolled.value = window.scrollY > 20
}

const closeLangMenu = () => {
  showLangMenu.value = false
}

onMounted(() => {
  window.addEventListener('scroll', handleScroll)
  window.addEventListener('click', closeLangMenu)
})
onUnmounted(() => {
  window.removeEventListener('scroll', handleScroll)
  window.removeEventListener('click', closeLangMenu)
})
</script>

<style scoped>
/* Previous styles... */
.app-header {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  height: 80px;
  z-index: 100;
  transition: all 0.3s ease;
  background: var(--bg-surface);
  border-bottom: 1px solid var(--border-subtle);
}

.app-header.scrolled {
  background: var(--bg-surface);
  backdrop-filter: blur(12px);
  height: 70px;
}

.h-full { height: 100%; }
.items-center { align-items: center; }

/* Language Selector Styles */
.lang-btn {
  display: flex;
  align-items: center;
  gap: 8px;
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid var(--border-subtle);
  padding: 6px 12px;
  border-radius: 9999px;
  color: var(--text-secondary);
  font-family: var(--font-heading);
  font-weight: 600;
  font-size: 0.85rem;
  cursor: pointer;
  transition: all 0.2s ease;
}

.lang-btn:hover {
  background: rgba(255, 255, 255, 0.1);
  color: var(--text-primary);
  border-color: var(--primary);
}

.lang-code {
  width: 24px;
}

.chevron {
  font-size: 0.6rem;
  transition: transform 0.2s ease;
  opacity: 0.5;
}

.chevron.open {
  transform: rotate(180deg);
}

.lang-dropdown {
  position: absolute;
  top: 100%;
  margin-top: 8px;
  right: 0;
  background: var(--bg-elevated);
  border: 1px solid var(--border-subtle);
  border-radius: 12px;
  padding: 8px;
  width: 160px;
  box-shadow: 0 10px 25px rgba(0,0,0,0.5);
  display: flex;
  flex-direction: column;
  gap: 2px;
  z-index: 1000;
}

.lang-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 10px 12px;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s ease;
  color: var(--text-secondary);
  font-size: 0.9rem;
}

.lang-item:hover {
  background: rgba(255, 255, 255, 0.05);
  color: var(--text-primary);
}

.lang-item.active {
  background: rgba(193, 255, 114, 0.1);
  color: var(--primary);
}

.fade-enter-active, .fade-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}

.fade-enter-from, .fade-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}

.logo-text {
  font-family: var(--font-heading);
  font-size: 1.5rem;
  font-weight: 800;
  letter-spacing: -0.5px;
}

.logo-img {
  width: 50px;
  height: 50px;
  border-radius: 12px;
  object-fit: cover;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

.highlight {
  color: var(--primary);
}

.desktop-nav {
  display: flex;
  align-items: center;
  gap: 32px;
}

.nav-link {
  font-family: var(--font-heading);
  font-weight: 500;
  font-size: 0.95rem;
  color: var(--text-secondary);
  transition: color 0.2s ease;
}

.nav-link:hover {
  color: var(--text-primary);
}

.login-link {
  display: flex;
  align-items: center;
}

.rounded-btn {
  border-radius: 20px !important;
}

.rounded-full {
  border-radius: 9999px !important;
}

.mobile-menu-toggle {
  display: none;
  background: transparent;
  border: none;
  color: var(--text-primary);
  cursor: pointer;
  padding: 8px;
}

.nav-links-wrapper {
  display: flex;
  align-items: center;
  gap: 32px;
}

@media (max-width: 768px) {
  .logo-img {
    width: 40px;
    height: 40px;
  }

  .mobile-menu-toggle {
    display: block;
  }

  .desktop-nav {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: var(--bg-surface);
    border-bottom: 1px solid var(--border-subtle);
    padding: 24px;
    flex-direction: column;
    gap: 24px;
    display: none;
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
  }

  .desktop-nav.is-open {
    display: flex;
  }

  .nav-links-wrapper {
    flex-direction: column;
    gap: 16px;
    width: 100%;
    align-items: flex-start;
  }

  .auth-buttons {
    flex-direction: column;
    width: 100%;
    align-items: stretch;
  }

  .auth-buttons > * {
    width: 100%;
    text-align: center;
    justify-content: center;
  }

  .lang-dropdown {
    width: 100%;
    position: static;
    margin-top: 8px;
    box-shadow: none;
    border: 1px solid var(--border-subtle);
  }
}
</style>
