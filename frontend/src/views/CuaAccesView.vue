<script>
// Vista Cua Accés — Explicació i entrada a la cua (A3/A4)
import { useAuthStore } from '../stores/authStore.js'
import { useCuaStore } from '../stores/cuaStore.js'
import { useVolsStore } from '../stores/volsStore.js'

export default {
  name: 'CuaAccesView',
  data: function () {
    return {
      carregant: false
    }
  },
  computed: {
    authStore: function () { return useAuthStore() },
    cuaStore: function () { return useCuaStore() },
    volsStore: function () { return useVolsStore() },
    volId: function () { return this.$route.params.id },
    volNoDisponible: function () {
      return this.volsStore.volActual && this.volsStore.volActual.disponiblePerCompra === false
    },
    motiuNoDisponible: function () {
      if (this.volsStore.volActual && this.volsStore.volActual.motiuNoDisponible) {
        return this.volsStore.volActual.motiuNoDisponible
      }
      return 'Vol no disponible per compra'
    }
  },
  methods: {
    entrarCua: function () {
      var self = this
      if (self.carregant) return
      
      self.carregant = true
      var clientId = self.authStore.obtenirClientId

      // Optimisme: Feedback visual immediat
      self.cuaStore.entrarCua(self.volId, clientId).then(function (dades) {
        // Redirecció rapida segons l'estat
        if (dades.cua.estat === 'autoritzat') {
          self.$router.push('/vol/' + self.volId + '/seients')
        } else {
          self.$router.push('/vol/' + self.volId + '/esperant')
        }
      }).catch(function (error) {
        self.carregant = false
        console.error("Error entrant a la cua", error)
      })
    },
    tornarEnrere: function () {
      this.$router.push('/vols')
    }
  },
  mounted: function () {
    this.authStore.inicialitzarClient()
    this.volsStore.carregarDetall(this.volId)
  }
}
</script>

<template>
  <div class="min-h-screen flex flex-col">
    <!-- Header -->
    <header class="w-full border-b border-primary/10 px-8 py-4 flex justify-between items-center bg-[var(--color-background-dark)]/50 backdrop-blur-md sticky top-0 z-50">
      <div class="flex items-center gap-3">
        <div class="w-8 h-8 rounded-lg flex items-center justify-center overflow-hidden shadow-lg shadow-black/20">
          <img src="/logo.png" alt="Logo" class="w-full h-full object-cover mix-blend-screen" />
        </div>
        <span class="font-extrabold tracking-tight text-xl">last24<span class="text-primary">bcn</span></span>
      </div>
      <div class="flex items-center gap-4 text-sm font-medium text-slate-500">
        <span class="flex items-center gap-1.5">
          <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
          Sistema: Online
        </span>
      </div>
    </header>

    <main class="flex-grow flex items-center justify-center p-6 hero-gradient">
      <div class="max-w-4xl w-full">
        <!-- Hero -->
        <div class="text-center mb-12">
          <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-primary/10 border border-primary/20 text-primary text-xs font-bold uppercase tracking-wider mb-6">
            <span class="material-icons text-sm">bolt</span> Període d'Alta Demanda
          </div>
          <h1 class="text-4xl md:text-5xl font-extrabold mb-4 tracking-tight">
            Reserves en temps real <span class="text-primary">(BCN)</span>
          </h1>
          <p class="text-slate-400 text-lg max-w-2xl mx-auto">
            Per mantenir l'estabilitat del sistema i l'accés just per a tots els viatgers, gestionem les entrades mitjançant una cua prioritzada.
          </p>
        </div>

        <!-- Info del vol -->
        <div v-if="volsStore.volActual" class="text-center mb-8">
          <div class="inline-flex items-center gap-3 bg-white/5 border border-white/10 px-6 py-3 rounded-xl">
            <span class="material-icons text-primary">flight</span>
            <span class="font-bold text-lg">BCN → {{ volsStore.volActual.destiIata }}</span>
            <span class="text-slate-400 text-sm">{{ volsStore.volActual.externalId }}</span>
          </div>
        </div>

        <!-- Regles Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
          <div class="glass-panel p-6 rounded-xl hover:border-primary/30 transition-colors">
            <div class="w-12 h-12 bg-primary/20 rounded-lg flex items-center justify-center mb-4 text-primary">
              <span class="material-icons">groups</span>
            </div>
            <h3 class="font-bold mb-2">Capacitat Limitada</h3>
            <p class="text-sm text-slate-400 leading-relaxed">
              Permetem un màxim de 10 compradors actius simultàniament per garantir la confirmació instantània de seients.
            </p>
          </div>
          <div class="glass-panel p-6 rounded-xl hover:border-primary/30 transition-colors">
            <div class="w-12 h-12 bg-primary/20 rounded-lg flex items-center justify-center mb-4 text-primary">
              <span class="material-icons">browser_not_supported</span>
            </div>
            <h3 class="font-bold mb-2">Persistència de Cua</h3>
            <p class="text-sm text-slate-400 leading-relaxed">
              Mantingues aquesta finestra oberta. Tancar la pestanya pot fer perdre la teva posició prioritària.
            </p>
          </div>
          <div class="glass-panel p-6 rounded-xl hover:border-primary/30 transition-colors">
            <div class="w-12 h-12 bg-primary/20 rounded-lg flex items-center justify-center mb-4 text-primary">
              <span class="material-icons">schedule</span>
            </div>
            <h3 class="font-bold mb-2">Finestra de 2 Minuts</h3>
            <p class="text-sm text-slate-400 leading-relaxed">
              Un cop et toqui, tindràs 2 minuts per completar la reserva abans que el ticket expiri.
            </p>
          </div>
        </div>

        <!-- Botó d'acció -->
        <div class="flex flex-col items-center gap-6">
          <div class="w-full max-w-md">
            <button class="w-full bg-primary hover:bg-primary/90 text-white font-bold py-5 rounded-xl transition-all flex items-center justify-center gap-3 shadow-lg shadow-primary/20 group"
                    :disabled="carregant || volNoDisponible"
                    :class="(carregant || volNoDisponible) ? 'opacity-50 cursor-not-allowed' : ''"
                    @click="entrarCua">
              <span v-if="carregant" class="material-icons animate-spin">sync</span>
              <span>{{ carregant ? 'Entrant a la cua...' : (volNoDisponible ? motiuNoDisponible : 'Entrar a la Cua de Reserva') }}</span>
              <span v-if="!carregant && !volNoDisponible" class="material-icons group-hover:translate-x-1 transition-transform">arrow_forward</span>
            </button>
            <div class="flex justify-between mt-3 px-2">
              <span class="text-xs text-slate-500 flex items-center gap-1">
                <span class="material-icons text-[14px]">hourglass_empty</span>
                Espera est.: &lt; 2 min
              </span>
              <span class="text-xs text-slate-500 flex items-center gap-1">
                <span class="material-icons text-[14px]">verified_user</span>
                Sessió Segura
              </span>
            </div>
          </div>
          <button class="text-slate-500 hover:text-primary transition-colors text-sm font-medium flex items-center gap-1"
                  @click="tornarEnrere">
            <span class="material-icons text-[18px]">arrow_back</span>
            Tornar al llistat de vols
          </button>
        </div>
      </div>
    </main>
  </div>
</template>
