export { default as UiCard } from './UiCard.vue'
export { default as UiButton } from './UiButton.vue'
export { default as UiInput } from './UiInput.vue'
export { default as UiSelect } from './UiSelect.vue'
export { default as UiTextarea } from './UiTextarea.vue'
export { default as UiBadge } from './UiBadge.vue'
export { default as UiPageHeader } from './UiPageHeader.vue'
export { default as UiEmptyState } from './UiEmptyState.vue'
export { default as UiTable } from './UiTable.vue'

// auto-register helper (optional)
import UiCard from './UiCard.vue'
import UiButton from './UiButton.vue'
import UiInput from './UiInput.vue'
import UiSelect from './UiSelect.vue'
import UiTextarea from './UiTextarea.vue'
import UiBadge from './UiBadge.vue'
import UiPageHeader from './UiPageHeader.vue'
import UiEmptyState from './UiEmptyState.vue'
import UiTable from './UiTable.vue'

export function registerUi(app){
  app.component('UiCard', UiCard)
  app.component('UiButton', UiButton)
  app.component('UiInput', UiInput)
  app.component('UiSelect', UiSelect)
  app.component('UiTextarea', UiTextarea)
  app.component('UiBadge', UiBadge)
  app.component('UiPageHeader', UiPageHeader)
  app.component('UiEmptyState', UiEmptyState)
  app.component('UiTable', UiTable)
}
