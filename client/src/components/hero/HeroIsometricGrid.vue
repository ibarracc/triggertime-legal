<template>
  <canvas ref="canvas" class="hero-bg"></canvas>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'

const canvas = ref(null)
let animId = null

onMounted(() => {
  const c = canvas.value
  const ctx = c.getContext('2d')
  let t = 0

  function resize() {
    c.width = c.offsetWidth
    c.height = c.offsetHeight
  }

  window.addEventListener('resize', resize)
  resize()

  function draw() {
    const W = c.width, H = c.height
    ctx.fillStyle = 'oklch(0.14 0.01 145)'
    ctx.fillRect(0, 0, W, H)

    const cols = 32, rows = 16
    const cW = W / cols, cH = H / rows

    for (let r = 0; r < rows; r++) {
      for (let cl = 0; cl < cols; cl++) {
        const cx = cl * cW + cW / 2
        const cy = r * cH + cH / 2
        const d = Math.sqrt((cx - W * 0.4) ** 2 + (cy - H * 0.4) ** 2)
        const w = Math.sin(d * 0.006 - t * 0.018) * 0.5 + 0.5
        if (w > 0.5) {
          ctx.strokeStyle = `rgba(124,179,66,${(w - 0.5) * 0.45})`
          ctx.lineWidth = 0.5
          ctx.strokeRect(cx - cW * 0.4, cy - cH * 0.4, cW * 0.8, cH * 0.8)
        }
      }
    }

    for (let i = 0; i < 5; i++) {
      const p = Math.sin(t * 0.02 + i * 1.5) * 0.5 + 0.5
      if (p > 0.7) {
        const cx = ((Math.sin(i * 3.7) * 0.5 + 0.5) * cols | 0) * cW + cW / 2
        const cy = ((Math.sin(i * 2.3) * 0.5 + 0.5) * rows | 0) * cH + cH / 2
        const g = ctx.createRadialGradient(cx, cy, 0, cx, cy, cW * 2)
        g.addColorStop(0, `rgba(124,179,66,${p * 0.25})`)
        g.addColorStop(1, 'rgba(124,179,66,0)')
        ctx.fillStyle = g
        ctx.fillRect(0, 0, W, H)
      }
    }

    t++
    animId = requestAnimationFrame(draw)
  }

  draw()

  onUnmounted(() => {
    cancelAnimationFrame(animId)
    window.removeEventListener('resize', resize)
  })
})
</script>

<style scoped>
.hero-bg {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  display: block;
  z-index: 0;
}
</style>
