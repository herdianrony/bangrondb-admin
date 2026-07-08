<template>
  <div class="toast-container">
    <TransitionGroup name="toast">
      <div
        v-for="t in toasts"
        :key="t.id"
        class="toast"
        :class="[
          `toast-${t.type}`,
          { 'toast-leaving': t.leaving }
        ]"
      >
        <component :is="iconMap[t.type]" class="w-4 h-4 flex-shrink-0 mt-0.5" />
        <span class="flex-1">{{ t.message }}</span>
        <button class="opacity-60 hover:opacity-100 transition-opacity ml-2 flex-shrink-0" @click="dismiss(t.id)">
          <X class="w-3.5 h-3.5" />
        </button>
      </div>
    </TransitionGroup>
  </div>
</template>

<script setup>
import { X, CheckCircle2, AlertCircle, Info, AlertTriangle } from 'lucide-vue-next'
import { useToast } from '@/composables/useToast'

const { toasts, dismiss } = useToast()

const iconMap = {
  success: CheckCircle2,
  error: AlertCircle,
  info: Info,
  warning: AlertTriangle,
}
</script>

<style scoped>
.toast-enter-active {
  transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}
.toast-leave-active {
  transition: all 0.25s ease-in;
}
.toast-enter-from {
  opacity: 0;
  transform: translateX(80px) scale(0.9);
}
.toast-leave-to,
.toast-leaving {
  opacity: 0;
  transform: translateX(80px) scale(0.9);
}
</style>