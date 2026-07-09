<template>
  <ConfirmDialog
    v-model="state.confirm.visible"
    :title="state.confirm.title"
    :message="state.confirm.message"
    :confirm-text="state.confirm.confirmText"
    :danger="state.confirm.danger"
    @confirm="onConfirm"
    @cancel="onCancel"
  />
  <PromptDialog
    :visible="state.prompt.visible"
    v-model="state.prompt.value"
    :title="state.prompt.title"
    :label="state.prompt.label"
    :placeholder="state.prompt.placeholder"
    @confirm="onPrompt"
    @cancel="onPromptCancel"
  />
</template>

<script setup>
import { useConfirm } from '@/composables/useConfirm'
import ConfirmDialog from '@/Components/ConfirmDialog.vue'
import PromptDialog from '@/Components/PromptDialog.vue'

const { state } = useConfirm()

function settle(target, val) {
  const r = state[target].resolve
  state[target].resolve = null
  if (r) r(val)
}
function onConfirm() { state.confirm.visible = false; settle('confirm', true) }
function onCancel() { state.confirm.visible = false; settle('confirm', false) }
function onPrompt() { state.prompt.visible = false; settle('prompt', state.prompt.value) }
function onPromptCancel() { state.prompt.visible = false; settle('prompt', null) }
</script>
