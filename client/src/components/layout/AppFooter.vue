<template>
  <footer class="app-footer text-center">
    <div class="container">
      <div class="footer-content">
        <div class="logo mb-4">
          <span class="logo-text">Trigger<span class="highlight">Time</span></span>
        </div>
        <p class="text-secondary mb-8">{{ $t('footer.tagline') }}</p>
        
        <div class="footer-links flex justify-center gap-6">
          <router-link to="/privacy" class="nav-link">{{ $t('footer.privacy') }}</router-link>
          <router-link to="/privacy" class="nav-link">{{ $t('footer.terms') }}</router-link>
          <a href="mailto:help@triggertime.es" class="nav-link">{{ $t('landing.email_support') }}</a>
        </div>
      </div>
      
      <div class="footer-bottom mt-8">
        <p class="text-secondary text-sm">© {{ new Date().getFullYear() }} TriggerTime. {{ $t('footer.rights') }}</p>
      </div>
    </div>
  </footer>
</template>

<style scoped>
.app-footer {
  background: var(--bg-surface);
  border-top: 1px solid var(--border-subtle);
  padding: 64px 0 32px;
  margin-top: auto;
}

.logo-text {
  font-family: var(--font-heading);
  font-size: 1.5rem;
  font-weight: 800;
  letter-spacing: -0.5px;
}

.highlight {
  color: var(--primary);
}

.nav-link {
  color: var(--text-secondary);
  font-size: 0.9rem;
  transition: color 0.2s ease;
}

.nav-link:hover {
  color: var(--primary);
}

.text-sm {
  font-size: 0.8rem;
}

.footer-bottom {
  border-top: 1px solid var(--border-subtle);
  padding-top: 32px;
}
</style>
