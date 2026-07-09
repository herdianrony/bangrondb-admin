<template>
  <header class="sticky top-0 z-30 lg:hidden bg-[#0f1117]/90 backdrop-blur-2xl border-b border-white/[0.07] px-4 py-3 flex items-center gap-3">
    <button class="p-2 -ml-1 rounded-xl hover:bg-white/5 text-slate-400" @click="$emit('open')">
      <Menu class="w-5 h-5" />
    </button>
    <div class="flex items-center gap-2.5">
      <div class="w-7 h-7 rounded-lg bg-gradient-to-br from-indigo-500 to-violet-600 grid place-items-center">
        <Box class="w-3.5 h-3.5 text-white" />
      </div>
      <span class="font-semibold text-sm text-white">{{ title }}</span>
    </div>
  </header>
</template>

<script setup>
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { Menu, Box } from 'lucide-vue-next'

defineEmits(['open'])

const page = usePage()
const currentPath = computed(() => page.url || window.location.pathname)

const title = computed(() => {
  const path = currentPath.value
  const m = path.match(/^\/databases\/([^/]+)(?:\/collections\/([^/]+))?/)
  if (m) return m[2] ? m[2] : m[1]
  if (path === '/') return 'Overview'
  if (path.startsWith('/auth/')) return 'Auth'
  return 'Bangron Studio'
})
</script>
