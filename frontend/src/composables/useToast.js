import { reactive } from 'vue'

const toasts = reactive([])
let nextId = 0

export function useToast() {
  function add(message, type = 'info', duration = 3500) {
    const id = nextId++
    toasts.push({ id, message, type, duration, leaving: false })
    if (duration > 0) {
      setTimeout(() => dismiss(id), duration)
    }
    return id
  }

  function dismiss(id) {
    const t = toasts.find(t => t.id === id)
    if (!t) return
    t.leaving = true
    setTimeout(() => {
      const idx = toasts.findIndex(t => t.id === id)
      if (idx !== -1) toasts.splice(idx, 1)
    }, 300)
  }

  function success(msg, dur) { return add(msg, 'success', dur) }
  function error(msg, dur) { return add(msg, 'error', dur || 5000) }
  function info(msg, dur) { return add(msg, 'info', dur) }
  function warning(msg, dur) { return add(msg, 'warning', dur) }

  return { toasts, add, dismiss, success, error, info, warning }
}