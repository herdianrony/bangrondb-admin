<template>
  <button
    :type="type"
    :disabled="disabled || loading"
    :class="classes"
    @click="$emit('click', $event)"
  >
    <Loader2 v-if="loading" :size="iconSize" class="animate-spin" />
    <component v-if="icon && !loading" :is="icon" :size="iconSize" />
    <slot />
  </button>
</template>

<script setup>
import { computed } from 'vue'
import { Loader2 } from 'lucide-vue-next'

const props = defineProps({
  variant: { type: String, default: 'primary' }, // primary | ghost | danger | secondary | subtle
  size: { type: String, default: 'md' }, // sm | md | lg
  disabled: Boolean,
  loading: Boolean,
  type: { type: String, default: 'button' },
  icon: { type: [Object, Function], default: null },
  block: Boolean,
})

defineEmits(['click'])

const classes = computed(() => {
  const base = 'inline-flex items-center justify-center gap-2 font-[550] transition-all select-none focus:outline-none focus:ring-2 focus:ring-indigo-500/30 disabled:opacity-50 disabled:cursor-not-allowed active:scale-[0.97]'
  const sizes = {
    sm: 'px-3 py-1.5 text-[12px] rounded-xl',
    md: 'px-3.5 py-2 text-[13px] rounded-xl',
    lg: 'px-4 py-2.5 text-[14px] rounded-2xl'
  }
  const variants = {
    primary: 'bg-gradient-to-r from-indigo-500 to-violet-600 text-white shadow shadow-indigo-900/20 hover:opacity-95',
    secondary: 'bg-slate-800 text-slate-100 border border-slate-700 hover:bg-slate-750',
    ghost: 'border border-white/[0.1] text-slate-300 hover:bg-white/[0.04]',
    danger: 'bg-red-600/90 text-white hover:bg-red-600 border border-red-500/30',
    subtle: 'text-slate-400 hover:text-slate-100 hover:bg-white/[0.04]',
  }
  return [
    base,
    sizes[props.size] || sizes.md,
    variants[props.variant] || variants.primary,
    props.block ? 'w-full' : ''
  ].join(' ')
})

const iconSize = computed(()=> props.size==='sm' ? 14 : props.size==='lg' ? 18 : 15)
</script>
