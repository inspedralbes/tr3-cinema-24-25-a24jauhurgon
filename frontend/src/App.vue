<script>
// App.vue — Shell principal amb ToastNotification global
import ToastNotification from './components/ToastNotification.vue'
import { useAuthStore } from './stores/authStore'
import socketService from './services/socketService'

export default {
  name: 'App',
  components: { ToastNotification: ToastNotification },
  methods: {
    // Mètode global per mostrar toasts des de qualsevol component fill
    mostrarToast: function (text, tipus) {
      if (this.$refs.toast) {
        this.$refs.toast.afegir(text, tipus)
      }
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
  }
}
</script>

<template>
  <div id="app-root" class="min-h-screen bg-[var(--color-background-dark)] text-slate-100">
    <router-view />
    <ToastNotification ref="toast" />
  </div>
</template>
