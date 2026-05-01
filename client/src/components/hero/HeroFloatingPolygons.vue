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

  function resize() {
    c.width = c.offsetWidth
    c.height = c.offsetHeight
  }

  window.addEventListener('resize', resize)
  resize()

  const shapes = Array.from({ length: 18 }, () => ({
    x: Math.random() * 1440,
    y: Math.random() * 600,
    vx: (Math.random() - 0.5) * 0.3,
    vy: (Math.random() - 0.5) * 0.2,
    size: 20 + Math.random() * 60,
    sides: [3, 4, 5, 6][Math.floor(Math.random() * 4)],
    rot: Math.random() * Math.PI * 2,
    rs: (Math.random() - 0.5) * 0.003,
    a: 0.1 + Math.random() * 0.15
  }))

  function poly(cx, cy, r, sides, rot) {
    ctx.beginPath()
    for (let i = 0; i <= sides; i++) {
      const ang = (i / sides) * Math.PI * 2 + rot
      const px = cx + r * Math.cos(ang)
      const py = cy + r * Math.sin(ang)
      i === 0 ? ctx.moveTo(px, py) : ctx.lineTo(px, py)
    }
    ctx.closePath()
  }

  function draw() {
    const W = c.width, H = c.height
    ctx.fillStyle = 'oklch(0.14 0.01 145)'
    ctx.fillRect(0, 0, W, H)

    for (const s of shapes) {
      s.x += s.vx
      s.y += s.vy
      s.rot += s.rs
      if (s.x < -80) s.x = W + 80
      if (s.x > W + 80) s.x = -80
      if (s.y < -80) s.y = H + 80
      if (s.y > H + 80) s.y = -80

      ctx.strokeStyle = `rgba(124,179,66,${s.a})`
      ctx.lineWidth = 1.5
      poly(s.x, s.y, s.size, s.sides, s.rot)
      ctx.stroke()
    }

    for (let i = 0; i < shapes.length; i++) {
      for (let j = i + 1; j < shapes.length; j++) {
        const dx = shapes[i].x - shapes[j].x
        const dy = shapes[i].y - shapes[j].y
        const d = Math.sqrt(dx * dx + dy * dy)
        if (d < 200) {
          ctx.strokeStyle = `rgba(124,179,66,${0.12 * (1 - d / 200)})`
          ctx.lineWidth = 0.5
          ctx.beginPath()
          ctx.moveTo(shapes[i].x, shapes[i].y)
          ctx.lineTo(shapes[j].x, shapes[j].y)
          ctx.stroke()
        }
      }
    }

    const g = ctx.createLinearGradient(0, H - 80, 0, H)
    g.addColorStop(0, 'rgba(124,179,66,0)')
    g.addColorStop(1, 'rgba(124,179,66,0.12)')
    ctx.fillStyle = g
    ctx.fillRect(0, H - 80, W, 80)

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
