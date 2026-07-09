import { reactive } from 'vue'

const state = reactive({
  confirm: { visible: false, title: '', message: '', confirmText: 'OK', danger: false, resolve: null },
  prompt: { visible: false, title: '', label: '', placeholder: '', value: '', resolve: null },
})

export function confirm(opts = {}) {
  return new Promise((resolve) => {
    state.confirm.title = opts.title || 'Konfirmasi'
    state.confirm.message = opts.message || ''
    state.confirm.confirmText = opts.confirmText || 'OK'
    state.confirm.danger = !!opts.danger
    state.confirm.resolve = resolve
    state.confirm.visible = true
  })
}

export function prompt(opts = {}) {
  return new Promise((resolve) => {
    state.prompt.title = opts.title || 'Input'
    state.prompt.label = opts.label || ''
    state.prompt.placeholder = opts.placeholder || ''
    state.prompt.value = opts.default != null ? opts.default : ''
    state.prompt.resolve = resolve
    state.prompt.visible = true
  })
}

export function useConfirm() {
  return { state, confirm, prompt }
}
