<template>
  <div class="space-y-1.5 w-full">
    <label v-if="label" class="block text-[11px] font-[550] text-slate-400 uppercase tracking-wider">
      {{ label }}<span v-if="required" class="text-red-400">*</span>
    </label>
    <textarea
      :value="modelValue"
      :placeholder="placeholder"
      :rows="rows"
      :disabled="disabled"
      :required="required"
      class="w-full bg-[#0f131c] border border-white/[0.1] rounded-xl px-3 py-2 text-[13px] text-slate-100 placeholder-slate-500 outline-none focus:border-indigo-500/60 focus:ring-2 focus:ring-indigo-500/15 resize-y min-h-[80px]"
      @input="$emit('update:modelValue', $event.target.value)"
    />
    <p v-if="hint" class="text-[11px] text-slate-500">{{ hint }}</p>
  </div>
</template>

<script setup>
defineProps({
  modelValue: String,
  label: String,
  placeholder: String,
  rows: { type: [Number, String], default: 3 },
  hint: String,
  disabled: Boolean,
  required: Boolean,
})
defineEmits(['update:modelValue'])
</script>
