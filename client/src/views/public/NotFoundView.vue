<template>
  <div class="not-found-container">
    <div class="content">
      <div class="error-code">404</div>
      <h1 class="title">{{ $t('notFound.title') || 'Oops! You missed the target.' }}</h1>
      <p class="message">
        {{ $t('notFound.message') || 'We looked everywhere, but it seems this page has gone rogue. Maybe it\'s hiding behind a barricade? Or maybe you just typed the wrong coordinates.' }}
      </p>
      
      <div class="animation-container">
        <!-- SVG graphic representing a missed target -->
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="120" height="120" class="bouncing-target" aria-hidden="true">
          <circle cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-width="1.5" class="target-ring"/>
          <circle cx="12" cy="12" r="6" fill="none" stroke="currentColor" stroke-width="1.5" class="target-ring"/>
          <circle cx="12" cy="12" r="2" fill="none" stroke="currentColor" stroke-width="1.5" class="target-ring"/>
          
          <!-- Bullet holes completely missing the center -->
          <circle cx="5" cy="5" r="1" class="bullet-hole" />
          <circle cx="19" cy="7" r="1" class="bullet-hole" />
          <circle cx="6" cy="18" r="1" class="bullet-hole" />
        </svg>
      </div>

      <router-link to="/" class="home-btn">
        {{ $t('notFound.backHome') || 'Tactical Retreat to Home Page' }}
      </router-link>
    </div>
  </div>
</template>

<script setup>
</script>

<style scoped>
.not-found-container {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: calc(100vh - 80px);
  padding: 2rem;
  text-align: center;
  /* Use app background color variables if they exist, fallback to common defaults */
  background-color: var(--bg-base, #0A0A0F);
  color: var(--text-primary, #F0F0F5);
}

:deep(.dark) .not-found-container {
  background-color: var(--bg-base, #0A0A0F);
  color: var(--text-primary, #F0F0F5);
}

.content {
  max-width: 600px;
}

.error-code {
  font-size: 8rem;
  font-weight: 900;
  line-height: 1;
  color: var(--primary, #C1FF72);
  text-shadow: 4px 4px 0px rgba(193, 255, 114, 0.15);
  margin-bottom: 1rem;
  animation: float 4s ease-in-out infinite;
}

.title {
  font-size: 2.5rem;
  font-weight: 800;
  margin-bottom: 1rem;
  letter-spacing: -0.025em;
}

.message {
  font-size: 1.25rem;
  color: var(--text-secondary, #8A8A9A);
  margin-bottom: 2.5rem;
  line-height: 1.6;
}

.animation-container {
  margin: 3rem 0;
  display: flex;
  justify-content: center;
}

.target-ring {
  color: var(--border-subtle, rgba(255, 255, 255, 0.2));
}

:deep(.dark) .target-ring {
  color: var(--border-subtle, rgba(255, 255, 255, 0.2));
}

.bullet-hole {
  fill: var(--primary, #C1FF72);
  transform-origin: center;
  animation: popIn 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275) backwards;
}

.bullet-hole:nth-child(4) { animation-delay: 0.5s; }
.bullet-hole:nth-child(5) { animation-delay: 1.5s; }
.bullet-hole:nth-child(6) { animation-delay: 2.5s; }

.bouncing-target {
  animation: pulse 6s ease-in-out infinite;
}

.home-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background-color: var(--primary, #C1FF72);
  color: var(--bg-base, #0A0A0F);
  padding: 0.875rem 2rem;
  border-radius: 9999px;
  text-decoration: none;
  font-weight: 600;
  font-size: 1.125rem;
  transition: all 0.2s ease;
  box-shadow: var(--shadow-glow, 0 0 20px rgba(193, 255, 114, 0.15));
}

.home-btn:hover {
  transform: translateY(-2px) scale(1.02);
  box-shadow: var(--shadow-glow-hover, 0 0 35px rgba(193, 255, 114, 0.3));
  background-color: var(--primary-hover, #D4FF8A);
}

.home-btn:active {
  transform: translateY(0) scale(0.98);
}

/* Animations */
@keyframes float {
  0% { transform: translateY(0px) rotate(0deg); }
  33% { transform: translateY(-10px) rotate(2deg); }
  66% { transform: translateY(-5px) rotate(-2deg); }
  100% { transform: translateY(0px) rotate(0deg); }
}

@keyframes pulse {
  0%, 100% { transform: scale(1); }
  50% { transform: scale(1.05); }
}

@keyframes popIn {
  0% { transform: scale(0); opacity: 0; }
  70% { transform: scale(1.5); opacity: 1; }
  100% { transform: scale(1); opacity: 1; }
}
</style>
