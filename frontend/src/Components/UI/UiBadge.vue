<template>
  <span :class="badgeClass">
    <slot />
  </span>
</template>

<script setup>
import { computed } from 'vue'
const props = defineProps({
  color: { type: String, default: 'slate' }, // slate, indigo, emerald, amber, red, violet
  size: { type: String, default: 'md' } // sm, md
})
const badgeClass = computed(()=>{
  const base = 'inline-flex items-center font-[550] border rounded-full'
  const sizes = {
    sm: 'text-[9px] px-1.5 py-0.5',
    md: 'text-[10px] px-2 py-0.5'
  }
  const colors = {
    slate: 'bg-slate-800 text-slate-300 border-slate-700',
    indigo: 'bg-indigo-500/10 text-indigo-300 border-indigo-500/20',
    emerald: 'bg-emerald-500/10 text-emerald-300 border-emerald-500/20',
    amber: 'bg-amber-500/10 text-amber-300 border-amber-500/20',
    red: 'bg-red-500/10 text-red-300 border-red-500/20',
    violet: 'bg-violet-500/10 text-violet-300 border-violet-500/20',
  }
  return [base, sizes[props.size], colors[props.color] || colors.slate].join(' ')
})
</script>
