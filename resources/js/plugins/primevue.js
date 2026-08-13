import PrimeVue from 'primevue/config'

/**
 * @param {import('vue').App} app
 */
export default function installPrimeVue(app) {
  app.use(PrimeVue, { unstyled: true, ripple: true })
}
