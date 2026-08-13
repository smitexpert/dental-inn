import PrimeVue from 'primevue/config'
import ToastService from 'primevue/toastservice'
import ConfirmationService from 'primevue/confirmationservice'
import Tooltip from 'primevue/tooltip'

import { provideToastInstance } from '@/Composables/useToast.js'
import { provideConfirmInstance } from '@/Composables/useConfirm.js'

/**
 * @param {import('vue').App} app
 */
export default function installPrimeVue(app) {
  app.use(PrimeVue, { unstyled: true, ripple: true })
  app.use(ToastService)
  app.use(ConfirmationService)
  app.directive('tooltip', Tooltip)

  app.mixin({
    setup() {
      provideToastInstance(app.config.globalProperties.$toast)
      provideConfirmInstance(app.config.globalProperties.$confirm)
    },
  })
}
