<template>
  <div class="mb-6">
    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
      <div class="flex items-start gap-3 min-w-0">
        <div v-if="icon" 
          class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500/15 to-violet-600/10 border border-indigo-500/20 grid place-items-center flex-shrink-0 mt-0.5">
          <component :is="icon" :size="18" class="text-indigo-300" />
        </div>
        <div class="min-w-0">
          <h1 class="text-[20px] sm:text-[22px] font-[700] tracking-tight text-white flex items-center gap-2 flex-wrap">
            {{ title }}
            <slot name="badge" />
          </h2>
          <p v-if="description" class="text-slate-400 text-[13px] mt-1 max-w-2xl">
            {{ description }}
          </p>
          <!-- breadcrumbs slot -->
          <div v-if="$slots.breadcrumbs" class="mt-2">
            <slot name="breadcrumbs" />
          </div>
        </div>
      </div>
      <div v-if="$slots.actions" class="flex items-center gap-2 flex-shrink-0">
        <slot name="actions" />
      </div>
    </div>
    <!-- meta / info bar -->
    <div v-if="$slots.meta" class="mt-3 text-[11px] text-slate-500">
      <slot name="meta" />
    </div>
  </div>
</template>

<script setup>
defineProps({
  title: { type: String, required: true },
  description: String,
  icon: [Object, Function, String],
})
</script>
