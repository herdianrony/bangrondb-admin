<template>
  <AppModal :model-value="visible" @update:model-value="$emit('update:visible', $event)" @close="$emit('cancel')">
    <template #default>
      <div class="flex items-center justify-between mb-5">
        <h3 class="font-bold text-lg text-white">{{ title }}</h3>
        <button class="btn-ghost-sm" @click="$emit('cancel')">
          <X class="w-4 h-4" />
        </button>
      </div>
      <div class="mb-5">
        <label v-if="label" class="section-label">{{ label }}</label>
        <input
          :value="modelValue"
          class="input"
          :placeholder="placeholder"
          @input="$emit('update:modelValue', $event.target.value)"
          @keyup.enter="$emit('confirm')"
          ref="inputRef"
        />
      </div>
      <div class="flex justify-end gap-2 pt-2 border-t border-white/[0.06]">
        <button class="btn-ghost" @click="$emit('cancel')">Batal</button>
        <button class="btn" @click="$emit('confirm')">OK</button>
      </div>
    </template>
  </AppModal>
</template>

<script setup>
import { watch, ref, nextTick } from 'vue'
import { X } from 'lucide-vue-next'
import AppModal from '@/Components/AppModal.vue'

const props = defineProps({
  visible: { type: Boolean, default: false },
  modelValue: { type: String, default: '' },
  title: { type: String, default: 'Input' },
  label: { type: String, default: '' },
  placeholder: { type: String, default: '' },
})

const emit = defineEmits(['update:visible', 'update:modelValue', 'confirm', 'cancel'])
const inputRef = ref(null)

watch(
  () => props.visible,
  (v) => { if (v) nextTick(() => inputRef.value?.focus()) }
)
</script>
