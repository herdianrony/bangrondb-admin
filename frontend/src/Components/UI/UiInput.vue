<template>
  <div class="space-y-1.5 w-full">
    <label v-if="label" class="block text-[11px] font-[550] text-slate-400 uppercase tracking-wider">
      {{ label }}
      <span v-if="required" class="text-red-400">*</span>
    </label>
    <div class="relative">
      <component 
        v-if="leftIcon" 
        :is="leftIcon" 
        :size="15"
        class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500 pointer-events-none"
      />
      <input
        :type="type"
        :value="modelValue"
        :placeholder="placeholder"
        :disabled="disabled"
        :required="required"
        :readonly="readonly"
        :class="[
          'w-full bg-[#0f131c] border border-white/[0.1] rounded-xl text-slate-100 placeholder-slate-500 outline-none transition',
          'focus:border-indigo-500/60 focus:ring-2 focus:ring-indigo-500/15',
          'disabled:opacity-60 disabled:cursor-not-allowed',
          leftIcon ? 'pl-9' : 'px-3',
          rightIcon ? 'pr-9' : '',
          'py-2 text-[13px]',
          error ? '!border-red-500/60 !ring-red-500/15' : '',
          $attrs.class || ''
        ]"
        @input="$emit('update:modelValue', $event.target.value)"
        v-bind="$attrs"
      />
      <component
        v-if="rightIcon"
        :is="rightIcon"
        :size="15"
        class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-500"
      />
    </div>
    <p v-if="hint && !error" class="text-[11px] text-slate-500">{{ hint }}</p>
    <p v-if="error" class="text-[11px] text-red-400">{{ error }}</p>
  </div>
</template>

<script setup>
defineProps({
  modelValue: [String, Number],
  label: String,
  placeholder: String,
  type: { type: String, default: 'text' },
  hint: String,
  error: String,
  disabled: Boolean,
  readonly: Boolean,
  required: Boolean,
  leftIcon: [Object, Function],
  rightIcon: [Object, Function],
})
defineEmits(['update:modelValue'])
</script>
