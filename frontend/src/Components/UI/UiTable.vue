<template>
  <div class="overflow-auto rounded-2xl border border-white/[0.07] bg-[#141820]">
    <table class="w-full text-[13px]">
      <thead class="bg-white/[0.02] text-[11px] uppercase tracking-wider text-slate-400">
        <tr>
          <th v-for="col in columns" :key="col.key"
              class="text-left px-4 py-2.5 font-[600] whitespace-nowrap">
            {{ col.label }}
          </th>
          <th v-if="$slots.actions" class="w-10"></th>
        </tr>
      </thead>
      <tbody class="divide-y divide-white/[0.04]">
        <tr v-if="loading" v-for="i in 3" :key="'s'+i" class="animate-pulse">
          <td :colspan="columns.length + ($slots.actions?1:0)" class="px-4 py-4">
            <div class="h-3 bg-slate-800 rounded w-3/4"></div>
          </td>
        </tr>
        <tr v-else-if="!items || items.length===0">
          <td :colspan="columns.length + ($slots.actions?1:0)" class="px-4 py-10">
            <slot name="empty">
              <div class="text-center text-slate-500 text-sm">No data</div>
            </slot>
          </td>
        </tr>
        <tr v-else v-for="(row, idx) in items" :key="rowKey(row, idx)"
            class="hover:bg-white/[0.02] transition-colors">
          <td v-for="col in columns" :key="col.key"
              class="px-4 py-2.5 align-top"
              :class="col.mono ? 'font-mono text-[12px] text-indigo-200' : 'text-slate-200'">
            <slot :name="`cell-${col.key}`" :row="row" :value="get(row,col.key)">
              {{ get(row, col.key) ?? '—' }}
            </slot>
          </td>
          <td v-if="$slots.actions" class="px-3 py-2 text-right whitespace-nowrap">
            <slot name="actions" :row="row" />
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup>
const props = defineProps({
  columns: { type: Array, required: true }, // [{key:'username', label:'Username', mono:true}, ...]
  items: Array,
  loading: Boolean,
  rowKey: { type: [String, Function], default: '_id' }
})
const get = (obj, path) => path.split('.').reduce((o,k)=>o?.[k], obj)
const rowKey = (row, idx) => typeof props.rowKey === 'function' ? props.rowKey(row) : (row[props.rowKey] ?? idx)
</script>
