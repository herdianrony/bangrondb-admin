<template>
  <AppModal :model-value="modelValue" @update:model-value="$emit('update:modelValue', $event)" @close="$emit('cancel')">
    <template #default>
      <div class="flex items-start gap-3 mb-4">
        <div v-if="danger" class="w-10 h-10 rounded-xl bg-red-500/10 border border-red-500/20 grid place-items-center flex-shrink-0">
          <AlertTriangle class="w-5 h-5 text-red-400" />
        </div>
        <div class="min-w-0 flex-1 pt-1">
          <h3 class="font-bold text-white text-sm">{{ title }}</h3>
          <p v-if="message" class="text-xs text-slate-400 mt-1 whitespace-pre-line">{{ message }}</p>
        </div>
      </div>
      <div class="flex justify-end gap-2">
        <button class="btn-ghost-sm" @click="$emit('cancel')">Batal</button>
        <button :class="danger ? 'btn-sm !bg-red-600 hover:!bg-red-500' : 'btn-sm'" @click="$emit('confirm')">
          {{ confirmText }}
        </button>
      </div>
    </template>
  </AppModal>
</template>

<script setup>
import { AlertTriangle } from 'lucide-vue-next'
import AppModal from '@/Components/AppModal.vue'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  title: { type: String, default: 'Konfirmasi' },
  message: { type: String, default: '' },
  confirmText: { type: String, default: 'OK' },
  danger: { type: Boolean, default: false },
})

const emit = defineEmits(['update:modelValue', 'confirm', 'cancel'])
</script>
