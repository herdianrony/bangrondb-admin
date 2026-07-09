<template>
  <div class="space-y-1.5 w-full">
    <label v-if="label" class="block text-[11px] font-[550] text-slate-400 uppercase tracking-wider">
      {{ label }} <span v-if="required" class="text-red-400">*</span>
    </label>
    <select
      :value="modelValue"
      :disabled="disabled"
      :required="required"
      :class="[
        'w-full bg-[#0f131c] border border-white/[0.1] rounded-xl px-3 py-2 text-[13px] text-slate-100 outline-none transition',
        'focus:border-indigo-500/60 focus:ring-2 focus:ring-indigo-500/15',
        'disabled:opacity-60'
      ]"
      @change="$emit('update:modelValue', $event.target.value)"
    >
      <option v-if="placeholder" value="" disabled>{{ placeholder }}</option>
      <slot>
        <option v-for="opt in options" :key="optValue(opt)" :value="optValue(opt)">
          {{ optLabel(opt) }}
        </option>
      </slot>
    </select>
    <p v-if="hint" class="text-[11px] text-slate-500">{{ hint }}</p>
  </div>
</template>

<script setup>
const props = defineProps({
  modelValue: [String, Number],
  label: String,
  options: { type: Array, default: ()=>[] },
  optionValue: { type: String, default: 'value' },
  optionLabel: { type: String, default: 'label' },
  placeholder: String,
  hint: String,
  disabled: Boolean,
  required: Boolean,
})
defineEmits(['update:modelValue'])
const optValue = (o)=> typeof o==='object' ? (o[props.optionValue] ?? o.value ?? o.name ?? o._id) : o
const optLabel = (o)=> typeof o==='object' ? (o[props.optionLabel] ?? o.label ?? o.name ?? optValue(o)) : o
</script>
