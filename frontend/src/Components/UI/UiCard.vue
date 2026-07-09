<template>
  <div :class="[
    'bg-[#181c25]/90 backdrop-blur border border-white/[0.07] rounded-2xl shadow-sm',
    padding ? 'p-5' : '',
    hover ? 'hover:border-white/[0.12] hover:shadow-lg hover:shadow-black/20 transition-all duration-200' : '',
    className
  ]">
    <slot />
  </div>
</template>

<script setup>
defineProps({
  padding: { type: Boolean, default: true },
  hover: { type: Boolean, default: false },
  className: { type: String, default: '' }
})
</script>
