<script>
// App.vue — Shell principal amb ToastNotification global
import ToastNotification from './components/ToastNotification.vue'
import { useAuthStore } from './stores/authStore'
import socketService from './services/socketService'

export default {
  name: 'App',
  components: { ToastNotification: ToastNotification },
  data() {
    return {
      temaActual: 'dark'
    }
  },
  methods: {
    // Mètode global per mostrar toasts des de qualsevol component fill
    mostrarToast: function (text, tipus) {
      if (this.$refs.toast) {
        this.$refs.toast.afegir(text, tipus)
      }
    },
    setTheme: function (theme) {
      this.temaActual = theme
      document.documentElement.dataset.theme = theme
      document.body.dataset.theme = theme
      localStorage.setItem('app-theme', theme)
    },
    initTheme: function () {
      const storedTheme = localStorage.getItem('app-theme')
      const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches
      const theme = storedTheme || (prefersDark ? 'dark' : 'light')
      this.setTheme(theme)
    }
  },
  provide: function () {
    var self = this
    return {
      mostrarToast: function (text, tipus) {
        self.mostrarToast(text, tipus)
      }
    }
  },
  mounted() {
    const authStore = useAuthStore()
    authStore.inicialitzarClient()
    
    // Escoltar de forma global si EL MEU rol canvia
    socketService.onRolActualitzat((data) => {
      // data = { usuari_id, nou_rol }
      if (authStore.estaAutenticat && authStore.usuari && authStore.usuari.id === data.usuari_id) {
        authStore.usuari.rol = data.nou_rol
        
        // Persistir canvi per a quan canviem de pàgina o refresquem
        localStorage.setItem('usuari', JSON.stringify(authStore.usuari))
        
        // Avisar a l'usuari amb un efecte 
        if (data.nou_rol === 'premium') {
          this.mostrarToast("🎉 Enhorabona! Ara ets usuari Premium", "success")
        } else {
          this.mostrarToast("ℹ️ El teu compte ha passat a General", "info")
        }
      }
    })

    this.initTheme()
  }
}
</script>

<template>
  <div id="app-root" class="min-h-screen bg-[var(--color-background)] text-[var(--color-text)]">
    <router-view />
    <ToastNotification ref="toast" />
  </div>
</template>
