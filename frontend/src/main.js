import { createApp } from 'vue'
import { createPinia } from 'pinia'
import PrimeVue from 'primevue/config'
import Aura from '@primeuix/themes/aura'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import InputText from 'primevue/inputtext'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import Tooltip from 'primevue/tooltip'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'
import Message from 'primevue/message'
import App from './App.vue'
import router from './router'
import ConfirmationService from 'primevue/confirmationservice'
import ToastService from 'primevue/toastservice'
import ConfirmDialog from 'primevue/confirmdialog'
import Toast from 'primevue/toast'

// PrimeVue estilos
import 'primeicons/primeicons.css'
import 'primeflex/primeflex.css'

// Estilos globales
import './style.css'

const app = createApp(App)
const pinia = createPinia()

app.use(pinia)
app.use(router)
app.use(ConfirmationService)
app.use(ToastService)
app.use(PrimeVue, {
    theme: {
        preset: Aura,
        options: {
            darkModeSelector: '.p-dark'
        }
    }
})

// Directivas
app.directive('tooltip', Tooltip)

// Registrar componentes PrimeVue globalmente
app.component('DataTable', DataTable)
app.component('Column', Column)
app.component('InputText', InputText)
app.component('Button', Button)
app.component('Tag', Tag)
app.component('IconField', IconField)
app.component('InputIcon', InputIcon)
app.component('ConfirmDialog', ConfirmDialog)
app.component('Toast', Toast)
app.component('Message', Message)

app.mount('#app')
