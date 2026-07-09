<template>
  <Teleport to="body">
    <Transition name="modal">
      <div v-if="modelValue" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="closable && close()"></div>
        <div class="relative card w-full animate-scale-in" :class="maxWidthClass">
          <slot :close="close" />
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { watch, onBeforeUnmount } from 'vue'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  closable: { type: Boolean, default: true },
  maxWidth: { type: String, default: 'max-w-sm' },
})

const emit = defineEmits(['update:modelValue', 'close'])

const maxWidthClass = props.maxWidth

function close() {
  if (!props.closable) return
  emit('update:modelValue', false)
  emit('close')
}

function onKey(e) {
  if (e.key === 'Escape') close()
}

watch(
  () => props.modelValue,
  (open) => {
    if (open) window.addEventListener('keydown', onKey)
    else window.removeEventListener('keydown', onKey)
  }
)

onBeforeUnmount(() => window.removeEventListener('keydown', onKey))
</script>
