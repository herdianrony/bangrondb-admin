<template>
  <div class="text-center py-14 px-6">
    <div class="w-12 h-12 mx-auto rounded-2xl bg-slate-800/60 border border-slate-700/60 grid place-items-center mb-3"
      v-if="icon">
      <component :is="icon" :size="22" class="text-slate-500" />
    </div>
    <div class="font-[600] text-slate-200 text-[14px]">{{ title }}</div>
    <div v-if="description" class="text-slate-500 text-[12px] mt-1 max-w-sm mx-auto">
      {{ description }}
    </div>
    <div v-if="$slots.action" class="mt-4">
      <slot name="action" />
    </div>
  </div>
</template>

<script setup>
defineProps({
  icon: [Object, Function],
  title: { type: String, default: 'No data' },
  description: String,
})
</script>
