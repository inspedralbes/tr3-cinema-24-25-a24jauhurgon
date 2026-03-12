<script>
// Vista Seatmap — Selecció de seients en temps real (A7)
import AppHeader from '../components/AppHeader.vue'
import CountdownTimer from '../components/CountdownTimer.vue'
import { useAuthStore } from '../stores/authStore.js'
import { useCompraStore } from '../stores/compraStore.js'
import { useVolsStore } from '../stores/volsStore.js'
import { useCuaStore } from '../stores/cuaStore.js'

export default {
  name: 'SeatmapView',
  components: { AppHeader: AppHeader, CountdownTimer: CountdownTimer },
  data: function () {
    return {
      refreshInterval: null
    }
  },
  inject: ['mostrarToast'],
  computed: {
    authStore: function () { return useAuthStore() },
    compraStore: function () { return useCompraStore() },
    volsStore: function () { return useVolsStore() },
    cuaStore: function () { return useCuaStore() },
    volId: function () { return this.$route.params.id },
    lletresColumna: function () {
      var lletres = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I']
      var resultat = []
      for (var i = 0; i < this.compraStore.columnes; i++) {
        resultat.push(lletres[i] || '' + (i + 1))
      }
      return resultat
    },
    totalActual: function () {
      return this.compraStore.totalCalculat
    }
  },
  methods: {
    obtenirLletraColumna: function (index) {
      var lletres = ['A', 'B', 'C', 'D', 'E', 'F']
      return lletres[index] || '' + (index + 1)
    },
    obtenirEstatSeient: function (fila, columna) {
      // Configuració First Class: 2-2 (sense columnes B ni E)
      if (fila <= 3 && (columna === 2 || columna === 5)) {
        return 'inexistent'
      }

      // Comprovar si és seleccionat per nosaltres
      for (var i = 0; i < this.compraStore.seientsSeleccionats.length; i++) {
        var s = this.compraStore.seientsSeleccionats[i]
        if (s.fila === fila && s.columna === columna) return 'seleccionat'
      }
      // Buscar en el seatmap
      if (this.compraStore.seatmap.length > 0) {
        var filaData = this.compraStore.seatmap[fila - 1]
        if (filaData) {
          for (var j = 0; j < filaData.length; j++) {
            if (filaData[j].fila === fila && filaData[j].columna === columna) {
              return filaData[j].estat
            }
          }
        }
      }
      return 'lliure'
    },
    classeSeient: function (estat, fila) {
      if (estat === 'inexistent') return 'invisible' // Espai buit per a First Class
      if (estat === 'seleccionat') return 'bg-green-500 ring-4 ring-green-500/20 shadow-lg shadow-green-500/10 cursor-pointer'
      if (estat === 'comprat') return 'bg-slate-700 cursor-not-allowed border border-white/5'
      if (estat === 'bloquejat') return 'animate-pulse-hold cursor-help shadow-lg shadow-yellow-500/10'
      
      // Bloqueig visual de First Class per a clients Normals
      if (fila <= 3 && !this.authStore.esPremium) {
        return 'bg-slate-800/80 cursor-not-allowed border border-amber-500/20 shadow-sm opacity-50 relative overflow-hidden group'
      }

      // Estil First Class disponible
      if (fila <= 3 && this.authStore.esPremium) {
        return 'bg-amber-600 hover:bg-amber-500 hover:scale-105 transition-transform cursor-pointer border border-amber-400/30 text-white shadow-lg shadow-amber-500/20 relative overflow-hidden group'
      }

      // Standard disponible
      return 'bg-primary hover:scale-105 transition-transform cursor-pointer border border-primary/20 shadow-md'
    },
    clicarSeient: function (fila, columna) {
      var self = this
      var estat = self.obtenirEstatSeient(fila, columna)

      if (estat === 'inexistent' || estat === 'comprat' || estat === 'bloquejat') return

      // Restricció per a clients Normals
      if (fila <= 3 && !self.authStore.esPremium) {
        return // Restricció silenciosa, només bloqueig de clic
      }

      if (estat === 'seleccionat') {
        // Alliberar (L'store ja fa Optimistic UI: treu de seleccionats i canvia color a l'instant)
        self.compraStore.alliberarSeient(self.volId, self.authStore.obtenirClientId, fila, columna).catch(function() {
          self.mostrarToast('No s\'ha pogut alliberar el seient.', 'error')
        })
      } else {
        // Comprovar max bitllets
        var vol = self.volsStore.volActual
        var max = vol ? vol.maximBitlletsPerCompra : 4
        if (self.compraStore.seientsSeleccionats.length >= max) {
          self.mostrarToast('Màxim ' + max + ' seients per compra.', 'avís')
          return
        }

        // Determinar tarifa base segons fila
        var esFirstClass = fila <= 3
        var tipusTarifa = esFirstClass ? 'First Class' : 'General'
        var preuBase = esFirstClass ? 120.00 : 49.99

        // Bloquejar (L'store ja fa Optimistic UI: afegeix a seleccionats i mostra el botó INSTANTÀNIAMENT)
        self.compraStore.bloquejarSeient(self.volId, self.authStore.obtenirClientId, fila, columna, tipusTarifa, preuBase).catch(function () {
          // Si falla (ex: seient ocupat per un altre), l'store reverteix i nosaltres avisem
          self.mostrarToast('Aquest seient s\'acaba d\'ocupar.', 'error')
        })
      }
    },
    refrescarSeatmap: function () {
      this.compraStore.carregarSeatmap(this.volId)
    },
    continuarCompra: function () {
      if (this.compraStore.seientsSeleccionats.length > 0) {
        this.$router.push('/vol/' + this.volId + '/resum')
      }
    },
    eliminarSeient: function (fila, columna) {
      this.compraStore.alliberarSeient(this.volId, this.authStore.obtenirClientId, fila, columna).catch(function () {})
    },
    buidarSeleccio: function () {
      var seients = [...this.compraStore.seientsSeleccionats]
      for (var i = 0; i < seients.length; i++) {
        this.compraStore.alliberarSeient(this.volId, this.authStore.obtenirClientId, seients[i].fila, seients[i].columna).catch(function () {})
      }
    },
    sessioExpirada: function () {
      var self = this
      self.cuaStore.sortirCua(self.volId, self.authStore.obtenirClientId).catch(function () {}).finally(function () {
        // Redirigir amb el volId per a poder "Tornar-ho a intentar" (redirigeix al mateix vol)
        self.$router.push({ path: '/sessio-expirada', query: { volId: self.volId } })
      })
    }
  },
  mounted: function () {
    var self = this
    self.authStore.inicialitzarClient()
    self.volsStore.carregarDetall(self.volId)
    self.compraStore.carregarSeatmap(self.volId)

    // Auto-refresh seatmap cada 15 segons (backup; socket gestiona temps real)
    self.refreshInterval = setInterval(function () {
      self.compraStore.carregarSeatmap(self.volId)
    }, 15000)
  },
  beforeUnmount: function () {
    if (this.refreshInterval) {
      clearInterval(this.refreshInterval)
    }
    // Netejar connexió socket quan sortim de la vista
    this.compraStore.aturarSocket()
  }
}
</script>

<template>
  <div class="min-h-screen flex flex-col">
    <!-- Header amb timer -->
    <header class="border-b border-primary/10 bg-white/5 backdrop-blur-md sticky top-0 z-50">
      <div class="max-w-7xl mx-auto px-4 h-16 flex items-center justify-between">
        <div class="flex items-center gap-4">
          <div class="bg-primary p-1.5 rounded-lg">
            <span class="material-icons text-white">flight_takeoff</span>
          </div>
          <div v-if="volsStore.volActual">
            <h1 class="text-lg font-bold leading-none">Vol {{ volsStore.volActual.externalId || 'BCN-' + volId }}</h1>
            <p class="text-xs text-slate-400 mt-1 uppercase tracking-widest font-semibold">Barcelona (BCN) → {{ volsStore.volActual.destiIata }}</p>
          </div>
        </div>

        <!-- Timer -->
        <CountdownTimer v-if="cuaStore.ticketExpiraAt"
                        :dataObjectiu="cuaStore.ticketExpiraAt"
                        @expirat="sessioExpirada" />

        <div class="flex items-center gap-6">
          <div v-if="compraStore.seientsSeleccionats.length > 0" class="text-right hidden md:block">
            <p class="text-xs text-slate-400 uppercase font-bold">Total</p>
            <p class="text-xl font-bold text-primary">€{{ totalActual.toFixed(2) }}</p>
          </div>
          <button v-if="compraStore.seientsSeleccionats.length > 0"
                  class="bg-primary hover:bg-primary/90 text-white px-6 py-2.5 rounded-lg font-bold transition-all shadow-lg shadow-primary/20"
                  @click="continuarCompra">
            Confirmar Seient
          </button>
        </div>
      </div>
    </header>

    <main class="flex-1 flex overflow-hidden">
      <!-- Sidebar esquerra: llegenda -->
      <aside class="w-80 border-r border-primary/10 flex flex-col hidden xl:flex">
        <div class="p-6 border-b border-primary/10">
          <h3 class="text-sm font-bold uppercase tracking-wider text-slate-400 mb-6">Llegenda d'Estats</h3>
          <div class="space-y-4">
            <div class="flex items-center gap-3">
              <div class="w-6 h-6 rounded bg-primary"></div>
              <span class="text-sm font-medium">Disponible</span>
            </div>
            <div class="flex items-center gap-3">
              <div class="w-6 h-6 rounded bg-yellow-500 animate-pulse-hold"></div>
              <span class="text-sm font-medium">Reservat per altres</span>
            </div>
            <div class="flex items-center gap-3">
              <div class="w-6 h-6 rounded bg-slate-700 flex items-center justify-center">
                <span class="material-icons text-[14px] text-slate-500">close</span>
              </div>
              <span class="text-sm font-medium">Ocupat</span>
            </div>
            <div class="flex items-center gap-3">
              <div class="w-6 h-6 rounded bg-green-500 flex items-center justify-center">
                <span class="material-icons text-[14px] text-white">check</span>
              </div>
              <span class="text-sm font-bold text-green-500">La teva selecció</span>
            </div>
          </div>
        </div>
        <div class="flex-1 p-6 overflow-y-auto">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-bold uppercase tracking-wider text-slate-400">Activitat en viu</h3>
            <div class="flex items-center gap-1.5">
              <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
              <span class="text-[10px] font-bold text-green-500 uppercase">Live</span>
            </div>
          </div>
          <p class="text-xs text-slate-500">Les actualitzacions en temps real es mostraran aquí.</p>
          <div v-if="compraStore.usuarisConnectats > 0" class="mt-3 flex items-center gap-2 text-xs">
            <span class="material-icons text-sm text-primary">groups</span>
            <span class="text-slate-400">{{ compraStore.usuarisConnectats }} usuari(s) mirant</span>
          </div>
        </div>
      </aside>

      <!-- Mapa de seients central -->
      <section class="flex-1 overflow-y-auto bg-slate-950/20 flex flex-col items-center py-6 md:py-12 px-4">
        <!-- Llegenda compacta mòbil -->
        <div class="flex flex-wrap gap-4 mb-6 xl:hidden">
          <div class="flex items-center gap-1.5"><div class="w-4 h-4 rounded bg-primary"></div><span class="text-[10px] font-medium text-slate-400">Disponible</span></div>
          <div class="flex items-center gap-1.5"><div class="w-4 h-4 rounded bg-yellow-500"></div><span class="text-[10px] font-medium text-slate-400">Reservat</span></div>
          <div class="flex items-center gap-1.5"><div class="w-4 h-4 rounded bg-slate-700"></div><span class="text-[10px] font-medium text-slate-400">Ocupat</span></div>
          <div class="flex items-center gap-1.5"><div class="w-4 h-4 rounded bg-green-500"></div><span class="text-[10px] font-bold text-green-500">Seleccionat</span></div>
        </div>

        <!-- Morro de l'avió -->
        <div class="max-w-[420px] w-full h-28 md:h-32 border-x-4 border-t-4 border-slate-700 rounded-t-[140px] mb-6 md:mb-8 bg-slate-900/50 flex flex-col items-center justify-center">
          <span class="text-[10px] font-bold tracking-widest text-slate-500 uppercase">Cabina de Comandament</span>
        </div>

        <!-- Mapa de seients -->
        <div class="max-w-[420px] w-full bg-slate-900/30 border-x-4 border-slate-700 p-3 md:p-6">
          <!-- Capçalera de columnes -->
          <div class="seat-grid mb-4 text-center text-[10px] font-bold text-slate-500 uppercase">
            <div>A</div>
            <div>B</div>
            <div>C</div>
            <div></div>
            <div>D</div>
            <div>E</div>
            <div>F</div>
          </div>

          <!-- Files de seients (Re-renderitzat forçat si canvia el rol Premium) -->
          <div class="space-y-2 md:space-y-4" :key="'seatmap-premium-' + authStore.esPremium">
            <div v-for="(fila, filaIdx) in compraStore.seatmap" :key="filaIdx" class="seat-grid group">
              <template v-for="(seient, colIdx) in fila" :key="colIdx">
                <!-- Inserir passadís després de la 3a columna -->
                <div v-if="colIdx === 3" class="flex items-center justify-center text-[10px] font-bold text-slate-600">
                  {{ filaIdx + 1 }}
                </div>

                <!-- Seient -->
                <div class="h-8 md:h-10 rounded flex items-center justify-center transition-all duration-300"
                     :class="classeSeient(obtenirEstatSeient(seient.fila, seient.columna), seient.fila)"
                     @click="clicarSeient(seient.fila, seient.columna)">
                  
                  <!-- Icones del Seient -->
                  <span v-if="obtenirEstatSeient(seient.fila, seient.columna) === 'comprat'" class="material-icons text-[14px] md:text-[16px] text-slate-500">close</span>
                  <span v-else-if="obtenirEstatSeient(seient.fila, seient.columna) === 'seleccionat'" class="material-icons text-white text-[16px] md:text-[18px]">check</span>
                  <span v-else-if="seient.fila <= 3 && !authStore.esPremium && obtenirEstatSeient(seient.fila, seient.columna) === 'lliure'" class="material-icons text-[12px] md:text-[14px] text-amber-500/50">lock</span>
                  <span v-else-if="seient.fila <= 3 && authStore.esPremium && obtenirEstatSeient(seient.fila, seient.columna) === 'lliure'" class="material-icons text-[12px] md:text-[14px] text-amber-100 opacity-50 group-hover:opacity-100">star</span>
                  
                </div>
              </template>
              <!-- Passadís per files amb 6 o menys columnes -->
              <div v-if="fila.length <= 3" class="flex items-center justify-center text-[10px] font-bold text-slate-600">
                {{ filaIdx + 1 }}
              </div>
            </div>
          </div>
        </div>

        <!-- Cos de l'avió -->
        <div class="max-w-[420px] w-full h-32 border-x-4 border-slate-700 bg-gradient-to-b from-slate-900/30 to-transparent"></div>
      </section>

      <!-- Sidebar dreta: selecció i preu -->
      <aside class="w-80 border-l border-primary/10 p-6 flex flex-col gap-6 hidden xl:flex">
        <div class="bg-white/5 rounded-xl p-5 border border-white/5">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-bold uppercase tracking-wider text-slate-400">La teva Selecció</h3>
            <button v-if="compraStore.seientsSeleccionats.length > 0" 
                    @click="buidarSeleccio" 
                    class="text-[10px] text-red-400 hover:text-red-300 uppercase font-bold flex items-center gap-1 transition-colors">
              <span class="material-icons text-[12px]">delete_sweep</span> Buidar
            </button>
          </div>
          <div v-if="compraStore.seientsSeleccionats.length === 0" class="text-center py-4">
            <p class="text-sm text-slate-500">Cap seient seleccionat</p>
          </div>
          <div v-for="(seient, idx) in compraStore.seientsSeleccionats" :key="idx" class="flex items-center justify-between mb-3">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded bg-green-500 flex items-center justify-center">
                <span class="text-white font-bold text-sm">{{ seient.fila }}{{ obtenirLletraColumna(seient.columna - 1) }}</span>
              </div>
              <div>
                <p class="text-sm font-bold">Seient Estàndard</p>
                <p class="text-xs text-slate-400">Fila {{ seient.fila }}</p>
              </div>
            </div>
            <div class="flex items-center gap-3">
              <span class="text-sm font-bold">€{{ parseFloat(seient.preu).toFixed(2) }}</span>
              <button @click="eliminarSeient(seient.fila, seient.columna)" 
                      class="text-slate-500 hover:text-red-400 p-1 transition-colors"
                      title="Alliberar aquest seient">
                <span class="material-icons text-[16px]">close</span>
              </button>
            </div>
          </div>
        </div>

        <div class="mt-auto">
          <div class="space-y-2 mb-6">
            <div class="flex justify-between text-sm">
              <span class="text-slate-400">{{ compraStore.seientsSeleccionats.length }}x Seient(s)</span>
              <span>€{{ totalActual.toFixed(2) }}</span>
            </div>
            <div class="flex justify-between text-lg font-bold border-t border-white/10 pt-4 mt-2">
              <span>Total</span>
              <span class="text-primary">€{{ totalActual.toFixed(2) }}</span>
            </div>
          </div>
          <button class="w-full bg-primary py-4 rounded-xl font-bold text-lg hover:bg-primary/90 transition-all active:scale-[0.98] shadow-lg shadow-primary/20 flex items-center justify-center gap-2"
                  :disabled="compraStore.seientsSeleccionats.length === 0"
                  :class="compraStore.seientsSeleccionats.length === 0 ? 'opacity-50 cursor-not-allowed' : ''"
                  @click="continuarCompra">
            <span>Continuar Reserva</span>
            <span class="material-icons">arrow_forward</span>
          </button>
        </div>
      </aside>
    </main>

    <!-- Barra inferior mòbil -->
    <div v-if="compraStore.seientsSeleccionats.length > 0" class="xl:hidden fixed bottom-0 left-0 right-0 bg-slate-900/95 backdrop-blur-md border-t border-primary/20 p-4 z-50">
      <div class="flex items-center justify-between max-w-lg mx-auto">
        <div class="flex items-center gap-3">
          <div>
            <p class="text-xs text-slate-400">{{ compraStore.seientsSeleccionats.length }} seient(s)</p>
            <p class="text-lg font-bold text-primary">€{{ totalActual.toFixed(2) }}</p>
          </div>
          <button @click="buidarSeleccio" class="w-8 h-8 rounded-full bg-red-500/10 text-red-500 flex items-center justify-center hover:bg-red-500/20 active:scale-95 transition-all">
            <span class="material-icons text-[16px]">delete_sweep</span>
          </button>
        </div>
        <button class="bg-primary px-6 py-3 rounded-xl font-bold text-white hover:bg-primary/90 transition-all shadow-lg shadow-primary/20 flex items-center gap-2"
                @click="continuarCompra">
          <span>Confirmar</span>
          <span class="material-icons text-lg">arrow_forward</span>
        </button>
      </div>
    </div>

    <!-- Footer -->
    <footer class="bg-primary/10 border-t border-primary/20 py-2 px-4 text-[10px] text-center uppercase tracking-[0.2em] font-bold text-primary/80" :class="compraStore.seientsSeleccionats.length > 0 ? 'mb-20 xl:mb-0' : ''">
      Entorn Interactiu d'Alta Concurrència • Sincronització de Seients en Temps Real
    </footer>
  </div>
</template>
