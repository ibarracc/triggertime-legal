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

    const sp = 24, n = Math.ceil((W + H) / sp)

    for (let i = 0; i < n; i++) {
      const bx = i * sp - H
      const w = Math.sin(i * 0.15 + t * 0.012) * 0.5 + 0.5
      ctx.strokeStyle = `rgba(124,179,66,${0.08 + w * 0.12})`
      ctx.lineWidth = 0.5 + w * 0.5
      ctx.beginPath()
      ctx.moveTo(bx, 0)
      ctx.lineTo(bx + H, H)
      ctx.stroke()
    }

    for (let i = 0; i < n; i += 4) {
      const bx = W - i * sp + H
      const w = Math.sin(i * 0.1 + t * 0.008) * 0.5 + 0.5
      ctx.strokeStyle = `rgba(124,179,66,${w * 0.1})`
      ctx.lineWidth = 0.5
      ctx.beginPath()
      ctx.moveTo(bx, 0)
      ctx.lineTo(bx - H, H)
      ctx.stroke()
    }

    const spots = [[W * 0.2, H * 0.3], [W * 0.7, H * 0.6], [W * 0.5, H * 0.15]]
    for (let idx = 0; idx < spots.length; idx++) {
      const s = spots[idx]
      const p = Math.sin(t * 0.015 + idx * 2) * 0.5 + 0.5
      const g = ctx.createRadialGradient(s[0], s[1], 0, s[0], s[1], 180 + p * 60)
      g.addColorStop(0, `rgba(124,179,66,${0.1 + p * 0.08})`)
      g.addColorStop(1, 'rgba(124,179,66,0)')
      ctx.fillStyle = g
      ctx.fillRect(0, 0, W, H)
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
