import './app.css'
import { createApp, h } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { registerUi } from '@/Components/UI'

createInertiaApp({
  resolve: name => {
    const pages = import.meta.glob('./Pages/**/*.vue', { eager: true })
    const page = pages[`./Pages/${name}.vue`]
    if (!page) throw new Error(`Page not found: ${name}`)
    page.default.layout = page.default.layout || AppLayout
    return page
  },
  setup({ el, App, props, plugin }) {
    // Remove skeleton loading class so CSS flex/height no longer constrains #app
    el.classList.remove('is-loading')
    const vueApp = createApp({ render: () => h(App, props) })
      .use(plugin)
    // Register Bangron Studio UI Kit globally – Design System v3 Clarity
    registerUi(vueApp)
    vueApp.mount(el)
  },
  progress: { color: '#6366f1' },
})
