<script>
// Vista Cua Espera — Sala d'espera amb posició en temps real (A5)
import { useAuthStore } from '../stores/authStore.js'
import { useCuaStore } from '../stores/cuaStore.js'

export default {
  name: 'CuaEsperaView',
  computed: {
    authStore: function () { return useAuthStore() },
    cuaStore: function () { return useCuaStore() },
    volId: function () { return this.$route.params.id }
  },
  methods: {
    sortirCua: function () {
      var self = this
      self.cuaStore.sortirCua(self.volId, self.authStore.obtenirClientId).then(function () {
        self.$router.push('/vols')
      })
    }
  },
  watch: {
    'cuaStore.estat': function (nouEstat) {
      if (nouEstat === 'autoritzat') {
        this.$router.push('/vol/' + this.volId + '/seients')
      }
    }
  },
  mounted: function () {
    var self = this
    self.authStore.inicialitzarClient()
    // Iniciar polling cada 3 segons
    self.cuaStore.iniciarPolling(self.volId, self.authStore.obtenirClientId)
  },
  beforeUnmount: function () {
    this.cuaStore.aturarPolling()
  }
}
</script>

<template>
  <div class="min-h-screen flex flex-col overflow-hidden relative">
    <!-- Efectes de fons -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none">
      <div class="absolute -top-[10%] -left-[10%] w-[40%] h-[40%] bg-primary/10 rounded-full blur-[120px]"></div>
      <div class="absolute -bottom-[10%] -right-[10%] w-[40%] h-[40%] bg-primary/5 rounded-full blur-[120px]"></div>
    </div>

    <!-- Nav -->
    <nav class="relative z-10 flex items-center justify-between px-8 py-6">
      <div class="flex items-center gap-3">
        <div class="w-8 h-8 rounded-lg flex items-center justify-center overflow-hidden shadow-lg shadow-black/20">
          <img src="/logo.png" alt="Logo" class="w-full h-full object-cover mix-blend-screen" />
        </div>
        <span class="font-extrabold tracking-tight text-xl">last24<span class="text-primary">bcn</span></span>
      </div>
      <div class="flex items-center gap-6">
        <div class="flex items-center gap-2 px-3 py-1.5 rounded-full bg-primary/10 border border-primary/20">
          <span class="relative flex h-2 w-2">
            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-primary opacity-75"></span>
            <span class="relative inline-flex rounded-full h-2 w-2 bg-primary"></span>
          </span>
          <span class="text-xs font-semibold uppercase tracking-wider text-primary">Connexió en viu</span>
        </div>
      </div>
    </nav>

    <!-- Contingut principal -->
    <main class="relative z-10 flex-1 flex flex-col items-center justify-center px-4 -mt-12">
      <div class="text-center mb-12">
        <h1 class="text-3xl md:text-4xl font-extrabold mb-4 tracking-tight">Assegurant el teu lloc per a sortides BCN</h1>
        <p class="text-slate-400 max-w-lg mx-auto leading-relaxed">
          El sistema està processant reserves prioritàries. El teu torn arribarà aviat. Mantingues aquesta finestra activa.
        </p>
      </div>

      <!-- Cercle de progrés -->
      <div class="relative flex items-center justify-center mb-16">
        <svg class="w-64 h-64 md:w-80 md:h-80">
          <circle class="text-primary/10" cx="50%" cy="50%" fill="transparent" r="48%" stroke="currentColor" stroke-width="8"></circle>
          <circle class="progress-ring text-primary" cx="50%" cy="50%" fill="transparent" r="48%" stroke="currentColor" stroke-dasharray="1000" stroke-dashoffset="250" stroke-linecap="round" stroke-width="8"></circle>
        </svg>
        <div class="absolute flex flex-col items-center text-center">
          <span class="text-sm font-semibold text-primary uppercase tracking-[0.2em] mb-1">Estat de la Cua</span>
          <div class="flex items-baseline gap-1">
            <span class="text-6xl md:text-7xl font-extrabold text-white">#{{ cuaStore.posicio }}</span>
          </div>
          <div class="mt-4 px-4 py-1 rounded-full bg-primary/20 text-primary text-xs font-bold border border-primary/30">
            POSICIÓ
          </div>
        </div>
        <div class="absolute w-full h-full rounded-full border border-primary/20 pulse-slow" style="transform: scale(1.1);"></div>
        <div class="absolute w-full h-full rounded-full border border-primary/10 pulse-slow" style="transform: scale(1.25);"></div>
      </div>

      <!-- Mètriques -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 w-full max-w-4xl">
        <div class="glass-panel p-6 rounded-xl text-center">
          <span class="material-icons text-primary/60 mb-2">group</span>
          <h3 class="text-slate-400 text-xs font-bold uppercase tracking-widest mb-1">Persones Davant</h3>
          <p class="text-2xl font-bold">{{ cuaStore.posicio > 0 ? cuaStore.posicio - 1 : 0 }}</p>
        </div>
        <div class="glass-panel p-6 rounded-xl text-center">
          <span class="material-icons text-primary/60 mb-2">schedule</span>
          <h3 class="text-slate-400 text-xs font-bold uppercase tracking-widest mb-1">Espera Estimada</h3>
          <p class="text-2xl font-bold">~ {{ cuaStore.posicio > 0 ? cuaStore.posicio * 15 : 0 }}s</p>
        </div>
        <div class="glass-panel p-6 rounded-xl text-center">
          <span class="material-icons text-primary/60 mb-2">bolt</span>
          <h3 class="text-slate-400 text-xs font-bold uppercase tracking-widest mb-1">Velocitat</h3>
          <p class="text-2xl font-bold">Ràpid</p>
          <div class="mt-2 flex items-center justify-center gap-1">
            <div class="h-1 w-4 bg-primary rounded-full"></div>
            <div class="h-1 w-4 bg-primary rounded-full"></div>
            <div class="h-1 w-4 bg-primary rounded-full"></div>
            <div class="h-1 w-4 bg-primary/30 rounded-full"></div>
          </div>
        </div>
      </div>

      <!-- Avís -->
      <div class="mt-12 flex items-center gap-3 px-6 py-4 rounded-xl bg-amber-500/10 border border-amber-500/20 max-w-md w-full">
        <span class="material-icons text-amber-500">warning_amber</span>
        <p class="text-sm font-medium text-amber-200/80">
          No tanquis aquesta pàgina. Seràs redirigit automàticament al motor de reserves.
        </p>
      </div>

      <!-- Botó sortir -->
      <button class="mt-6 text-slate-500 hover:text-red-400 transition-colors text-sm flex items-center gap-1"
              @click="sortirCua">
        <span class="material-icons text-sm">close</span>
        Sortir de la cua
      </button>
    </main>
  </div>
</template>
