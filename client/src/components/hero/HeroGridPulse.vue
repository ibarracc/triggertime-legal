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

    const sp = 60
    for (let x = 0; x < W + sp; x += sp) {
      for (let y = 0; y < H + sp; y += sp) {
        const d = Math.sqrt((x - W / 2) ** 2 + (y - H / 2) ** 2)
        const w = Math.sin(d * 0.008 - t * 0.02) * 0.5 + 0.5
        ctx.fillStyle = `rgba(124,179,66,${w * 0.45})`
        ctx.beginPath()
        ctx.arc(x, y, 2 + w * 2.5, 0, Math.PI * 2)
        ctx.fill()
      }
    }

    for (let y = 0; y < H; y += 80) {
      const off = Math.sin(y * 0.05 + t * 0.015) * 40
      ctx.strokeStyle = 'rgba(124,179,66,0.18)'
      ctx.lineWidth = 0.5
      ctx.beginPath()
      ctx.moveTo(0, y + off)
      ctx.lineTo(W, y + off)
      ctx.stroke()
    }

    const g = ctx.createRadialGradient(W * 0.5, H * 0.45, 0, W * 0.5, H * 0.45, 400)
    g.addColorStop(0, 'rgba(124,179,66,0.18)')
    g.addColorStop(1, 'rgba(124,179,66,0)')
    ctx.fillStyle = g
    ctx.fillRect(0, 0, W, H)

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
