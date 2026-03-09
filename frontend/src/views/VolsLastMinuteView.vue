<script>
// Vista Vols Last Minute — Llistat de vols (A2)
import AppHeader from '../components/AppHeader.vue'
import VolCard from '../components/VolCard.vue'
import { useVolsStore } from '../stores/volsStore.js'
import { useAuthStore } from '../stores/authStore.js'
import socketService from '../services/socketService.js'

export default {
  name: 'VolsLastMinuteView',
  components: { AppHeader: AppHeader, VolCard: VolCard },
  data: function () {
    return {
      cercaDesti: '',
      finestraActiva: 1440,
      intervalRefresh: null
    }
  },
  computed: {
    volsStore: function () {
      return useVolsStore()
    },
    authStore: function () {
      return useAuthStore()
    },
    volsFiltrats: function () {
      var vols = this.volsStore.vols
      var cerca = this.cercaDesti.toUpperCase()
      if (!cerca) return vols
      var resultat = []
      for (var i = 0; i < vols.length; i++) {
        if (vols[i].destiIata.indexOf(cerca) !== -1) {
          resultat.push(vols[i])
        }
      }
      return resultat
    }
  },
  methods: {
    canviarFinestra: function (minuts) {
      this.finestraActiva = minuts
      this.volsStore.carregarVols(minuts)
    },
    seleccionarVol: function (vol) {
      this.$router.push('/vol/' + vol.id + '/cua')
    }
  },
  mounted: function () {
    var self = this
    self.authStore.inicialitzarClient()

    // Capturar login social de la URL si existeix
    var urlParams = new URLSearchParams(window.location.search)
    var token = urlParams.get('token')
    var usuari = urlParams.get('usuari')
    if (token && usuari) {
      self.authStore.guardarSessioSocial(token, usuari)
      // Netejem la URL per privacitat i estètica
      window.history.replaceState({}, document.title, "/vols")
    }

    self.volsStore.carregarVols(self.finestraActiva)
    self.volsStore.carregarTarifes()

    // Auto-refresh cada 30 segons com a fallback, però preferim WebSockets
    self.intervalRefresh = setInterval(function () {
      self.volsStore.carregarVols(self.finestraActiva)
    }, 30000)

    // Escoltar canvis d'estat de venda (Obert/Tancat) instantanis (ex: des de l'Admin Panel)
    socketService.onVolEstatActualitzat((data) => {
      // data: { volId, nou_estat }
      const volInfo = self.volsStore.vols.find(v => v.id === data.volId)
      if (volInfo) {
        volInfo.estat_venda = data.nou_estat
        // Això fa que <VolCard /> es re-renderitzi automàticament gràcies a Vue/Pinia
      }
    })
  },
  beforeUnmount: function () {
    if (this.intervalRefresh) {
      clearInterval(this.intervalRefresh)
    }
    socketService.netejarListeners();
  }
}
</script>

<template>
  <div class="min-h-screen">
    <!-- Header -->
    <AppHeader />

    <div class="max-w-[1600px] mx-auto flex gap-8 p-6">
      <!-- Sidebar Filtres -->
      <aside class="w-72 flex-shrink-0 hidden lg:block space-y-8">
        <div class="space-y-6">
          <div>
            <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-500 mb-4">Finestra Temporal</h3>
            <div class="grid grid-cols-3 gap-2">
              <button class="px-3 py-2 text-xs font-bold rounded transition-colors"
                      :class="finestraActiva === 360 ? 'bg-primary text-white' : 'bg-slate-800 text-slate-400 hover:bg-slate-700'"
                      @click="canviarFinestra(360)">6H</button>
              <button class="px-3 py-2 text-xs font-bold rounded transition-colors"
                      :class="finestraActiva === 720 ? 'bg-primary text-white' : 'bg-slate-800 text-slate-400 hover:bg-slate-700'"
                      @click="canviarFinestra(720)">12H</button>
              <button class="px-3 py-2 text-xs font-bold rounded transition-colors"
                      :class="finestraActiva === 1440 ? 'bg-primary text-white' : 'bg-slate-800 text-slate-400 hover:bg-slate-700'"
                      @click="canviarFinestra(1440)">24H</button>
            </div>
          </div>
          <div>
            <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-500 mb-4">Cercar Destinació</h3>
            <div class="relative">
              <span class="material-icons absolute left-3 top-2.5 text-slate-500 text-sm">search</span>
              <input v-model="cercaDesti" type="text" placeholder="p.ex. London, LHR"
                     class="w-full bg-slate-800/50 border border-slate-700 text-sm rounded-lg pl-10 py-2 text-white placeholder-slate-500 focus:ring-primary focus:border-primary" />
            </div>
          </div>
        </div>
      </aside>

      <!-- Contingut principal -->
      <main class="flex-grow">
        <header class="flex items-end justify-between mb-8">
          <div>
            <h1 class="text-3xl font-bold tracking-tight">Sortides <span class="text-slate-500 font-normal">BCN Terminal 1 & 2</span></h1>
            <p class="text-slate-500 mt-1">{{ volsFiltrats.length }} vols trobats sortint en les properes {{ finestraActiva / 60 }} hores</p>
          </div>
        </header>

        <!-- Carregant -->
        <div v-if="volsStore.carregant && volsStore.vols.length === 0" class="flex items-center justify-center py-20">
          <div class="flex flex-col items-center gap-4">
            <span class="material-icons text-primary text-4xl animate-spin">sync</span>
            <p class="text-slate-400">Carregant vols...</p>
          </div>
        </div>

        <!-- Grid de vols -->
        <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
          <VolCard v-for="vol in volsFiltrats" :key="vol.id" :vol="vol" @seleccionar="seleccionarVol" />
        </div>

        <!-- Sense vols -->
        <div v-if="!volsStore.carregant && volsFiltrats.length === 0" class="text-center py-20">
          <span class="material-icons text-slate-600 text-6xl mb-4">flight_takeoff</span>
          <p class="text-slate-400 text-lg">No s'han trobat vols en aquesta finestra temporal.</p>
          <button class="mt-4 text-primary hover:underline" @click="canviarFinestra(1440)">Ampliar a 24 hores</button>
        </div>

        <!-- Indicador alta demanda -->
        <div v-if="volsFiltrats.length > 0" class="mt-12 p-6 rounded-2xl border border-primary/20 bg-primary/5 flex items-center justify-between">
          <div class="flex items-center gap-4">
            <div class="flex -space-x-3">
              <div class="w-10 h-10 rounded-full border-2 border-[var(--color-background-dark)] bg-primary flex items-center justify-center text-xs font-bold text-white">
                <span class="material-icons text-sm">person</span>
              </div>
              <div class="w-10 h-10 rounded-full border-2 border-[var(--color-background-dark)] bg-slate-700 flex items-center justify-center text-xs font-bold text-slate-400">+42</div>
            </div>
            <div>
              <p class="text-sm font-bold">Mode Alta Demanda</p>
              <p class="text-xs text-slate-500">Sistema de cua activat per garantir reserves justes.</p>
            </div>
          </div>
        </div>
      </main>
    </div>
  </div>
</template>
