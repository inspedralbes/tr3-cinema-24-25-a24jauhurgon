<script>
// Vista AdminSeatmap — (A17) Mapa de seients en directe per a Administradors
import api from '../services/apiService.js'
import socketService from '../services/socketService.js'

export default {
  name: 'AdminSeatmapView',
  data() {
    return {
      volId: this.$route.params.id,
      vol: null,
      seientsComprats: [],
      seientsBloquejats: [],
      carregant: true,
      error: null
    }
  },
  methods: {
    carregarDades(silenci=false) {
      if (!silenci) this.carregant = true
      this.error = null
      
      // Carregar detalls del vol per info bàsica
      api.get(`/vols/${this.volId}`)
        .then(res => {
          this.vol = res.data.vol
          return api.get(`/admin/vols-interns/${this.volId}/seients`)
        })
        .then(res => {
          this.seientsComprats = res.data.seientsComprats
          this.seientsBloquejats = res.data.seientsBloquejats
        })
        .catch(err => {
          console.error("Error carregant mapa admin", err)
          this.error = "No s'han pogut carregar les dades del vol intern."
        })
        .finally(() => {
          this.carregant = false
        })
    },
    // Retorna l'estatus visual del seient i el nom de l'usuari si n'hi ha
    estatSeient(fila, col) {
      const comprat = this.seientsComprats.find(s => s.fila === fila && s.columna === col)
      if (comprat) {
        if (comprat.hora_embarcament) {
          return { tipus: 'embarcat', usuari: comprat.usuari ? comprat.usuari.name : 'Desconegut' }
        }
        return { tipus: 'comprat', usuari: comprat.usuari ? comprat.usuari.name : 'Desconegut' }
      }

      const bloquejat = this.seientsBloquejats.find(s => s.fila === fila && s.columna === col)
      if (bloquejat) return { tipus: 'bloquejat', usuari: bloquejat.usuari ? bloquejat.usuari.name : 'Desconegut' }

      return { tipus: 'lliure', usuari: null }
    },
    configurarSockets() {
      socketService.connectar()
      socketService.unirVol(this.volId)

      socketService.onSeatmapActualitzat(() => {
        this.carregarDades(true)
      })
    },
    forcarEstatVenda(estat) {
      if (!confirm(`Segur que vols canviar l'estat a ${estat}?`)) return
      
      api.post(`/admin/vols-interns/${this.volId}/force-status`, { estat_venda: estat })
        .then(res => {
          this.vol.estat_venda = res.data.estat_venda
        })
        .catch(err => alert("Error al forçar estat: " + err.response?.data?.missatge))
    }
  },
  mounted() {
    this.carregarDades()
    this.configurarSockets()
  },
  unmounted() {
    socketService.sortirVol(this.volId)
    socketService.netejarListeners()
  }
}
</script>

<template>
  <div class="space-y-6 animate-fade-in-up">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-4">
        <button @click="$router.push('/admin')" class="w-10 h-10 rounded-full bg-slate-800/50 hover:bg-slate-700 flex items-center justify-center text-slate-300 transition-colors">
          <span class="material-icons">arrow_back</span>
        </button>
        <div>
          <h2 class="text-xl font-bold text-white mb-1">Live Seatmap Monitor</h2>
          <p class="text-sm text-slate-400" v-if="vol && vol.modelAvio">Bcn ➔ {{ vol.destiIata }} | Avió: {{ vol.modelAvio.nomModel }}</p>
        </div>
      </div>
      
      <!-- Botons Control Venda Ràpid -->
      <div v-if="vol" class="flex items-center gap-3">
        <div class="px-3 py-1.5 rounded-lg border flex items-center gap-2 text-sm font-bold"
             :class="vol.estat_venda === 'obert' ? 'bg-green-500/10 border-green-500/30 text-green-400' : (vol.estat_venda === 'tancat' ? 'bg-slate-800 text-slate-400 border-slate-700' : 'bg-primary/20 border-primary/40 text-primary')">
          <span class="w-2 h-2 rounded-full" :class="vol.estat_venda === 'obert' ? 'bg-green-500 animate-pulse' : (vol.estat_venda === 'tancat' ? 'bg-slate-500' : 'bg-primary')"></span>
          {{ vol.estat_venda.toUpperCase() }}
        </div>
        
        <button v-if="vol.estat_venda === 'obert'" @click="forcarEstatVenda('finalitzat')" 
                class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg font-bold text-sm shadow-lg shadow-red-500/20 transition-colors flex items-center gap-2">
          <span class="material-icons text-sm">stop_circle</span> Tancar Vendes d'Emergència
        </button>
      </div>
    </div>

    <div v-if="error" class="bg-red-500/10 border border-red-500/50 p-4 rounded-lg flex items-center gap-3">
      <span class="material-icons text-red-500">error_outline</span>
      <span class="text-red-400 text-sm font-semibold">{{ error }}</span>
    </div>

    <!-- Seatmap Body -->
    <div v-if="vol && !carregant" class="flex flex-col lg:flex-row gap-8">
      
      <!-- Plànol -->
      <div class="lg:w-2/3 glass-panel p-8 rounded-xl flex flex-col items-center overflow-x-auto relative">
        <div class="w-24 h-32 border-4 border-slate-700/50 rounded-t-full mb-8 relative flex items-center justify-center bg-slate-800/20">
            <span class="text-xs font-bold text-slate-500 tracking-widest">CABINA</span>
        </div>

        <div class="flex flex-col gap-6 w-max pb-12">
          <!-- Bucles Files i Columnes -->
          <div v-for="fila in vol.modelAvio.files" :key="fila" class="flex items-center gap-8 relative group">
            <span class="w-6 text-center text-xs font-bold text-slate-600 absolute -left-10">{{ fila }}</span>
            
            <div class="flex gap-3">
              <!-- Passadís central -->
              <template v-for="col in vol.modelAvio.columnes" :key="col">
                <div v-if="col === Math.floor(vol.modelAvio.columnes / 2) + 1" class="w-6"></div>
                
                <div class="relative group/seat w-10 h-10">
                  <template v-if="!(fila <= 3 && (col === 2 || col === 5))">
                    <div class="w-10 h-10 rounded text-xs font-bold flex items-center justify-center transition-all duration-300 border shadow-sm cursor-help relative overflow-hidden"
                         :class="{
                           'bg-cyan-500 text-white border-cyan-400 shadow-cyan-500/50 shadow-lg': estatSeient(fila, col).tipus === 'embarcat',
                           'bg-green-500 text-white border-green-400 shadow-green-500/30': estatSeient(fila, col).tipus === 'comprat',
                           'bg-orange-500 text-white border-orange-400 shadow-orange-500/30 animate-pulse-slow': estatSeient(fila, col).tipus === 'bloquejat',
                           'bg-amber-600/30 text-amber-500 border-amber-500/50': estatSeient(fila, col).tipus === 'lliure' && fila <= 3,
                           'bg-slate-800 text-slate-500 border-slate-700': estatSeient(fila, col).tipus === 'lliure' && fila > 3
                         }">
                      <!-- Top border simulation for seat -->
                      <div class="absolute top-0 left-0 right-0 h-1" 
                           :class="{
                             'bg-cyan-400': estatSeient(fila, col).tipus === 'embarcat',
                             'bg-green-400': estatSeient(fila, col).tipus === 'comprat',
                             'bg-orange-400': estatSeient(fila, col).tipus === 'bloquejat',
                             'bg-amber-500': estatSeient(fila, col).tipus === 'lliure' && fila <= 3,
                             'bg-slate-700': estatSeient(fila, col).tipus === 'lliure' && fila > 3
                           }"></div>
                           
                      <span v-if="estatSeient(fila, col).tipus === 'embarcat'" class="material-icons text-[18px]">how_to_reg</span>
                      <span v-else-if="estatSeient(fila, col).tipus === 'comprat'" class="material-icons text-[16px]">check</span>
                      <span v-else-if="estatSeient(fila, col).tipus === 'bloquejat'" class="material-icons text-[16px]">shopping_cart</span>
                      <span v-else-if="fila <= 3" class="material-icons text-[14px]">star</span>
                      <span v-else>{{ String.fromCharCode(64 + col) }}</span>
                    </div>

                    <!-- Tooltip -->
                    <div v-if="estatSeient(fila, col).tipus !== 'lliure'" 
                         class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 w-max px-3 py-1.5 bg-slate-900 border border-slate-700 rounded-lg text-xs font-bold text-white shadow-xl opacity-0 group-hover/seat:opacity-100 transition-opacity pointer-events-none z-20 flex flex-col items-center">
                      <span class="text-[10px] text-slate-400 uppercase tracking-widest">{{ estatSeient(fila, col).tipus }}</span>
                      <span>{{ estatSeient(fila, col).usuari }}</span>
                      <div class="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-slate-700"></div>
                    </div>
                  </template>
                </div>

              </template>
            </div>
            
            <span class="w-6 text-center text-xs font-bold text-slate-600 absolute -right-10">{{ fila }}</span>
          </div>
        </div>
      </div>

      <!-- Llegenda i Stats -->
      <div class="lg:w-1/3 space-y-6">
        <div class="glass-panel p-6 rounded-xl border border-slate-700/50">
          <h3 class="font-bold text-white mb-4 uppercase tracking-wider text-sm flex items-center gap-2">
            <span class="material-icons text-primary text-sm">info</span>
            Llegenda del Mapa
          </h3>
          <div class="space-y-4">
            <div class="flex items-center gap-3">
              <div class="w-8 h-8 rounded bg-slate-800 border border-slate-700 flex flex-col items-center justify-center relative overflow-hidden">
                <div class="absolute top-0 w-full h-1 bg-slate-700"></div>
              </div>
              <div>
                <p class="text-sm font-bold text-slate-300">Lliure</p>
                <p class="text-xs text-slate-500">Disponible per a compra</p>
              </div>
            </div>
            <div class="flex items-center gap-3">
              <div class="w-8 h-8 rounded bg-orange-500 border border-orange-400 flex flex-col items-center justify-center relative overflow-hidden shadow-orange-500/20 shadow-lg">
                <div class="absolute top-0 w-full h-1 bg-orange-400"></div>
                <span class="material-icons text-white text-[14px]">shopping_cart</span>
              </div>
              <div>
                <p class="text-sm font-bold text-orange-400">Bloquejat / En Cistella</p>
                <p class="text-xs text-slate-500">L'usuari (es mostra) ho està pagant.</p>
              </div>
            </div>
            <div class="flex items-center gap-3">
              <div class="w-8 h-8 rounded bg-green-500 border border-green-400 flex flex-col items-center justify-center relative overflow-hidden shadow-green-500/20 shadow-lg">
                <div class="absolute top-0 w-full h-1 bg-green-400"></div>
                <span class="material-icons text-white text-[16px]">check</span>
              </div>
              <div>
                <p class="text-sm font-bold text-green-400">Comprat</p>
                <p class="text-xs text-slate-500">Bitllet assignat i pagat en ferm.</p>
              </div>
            </div>
            <!-- Nou estat: Embarcat -->
            <div class="flex items-center gap-3">
              <div class="w-8 h-8 rounded bg-cyan-500 border border-cyan-400 flex flex-col items-center justify-center relative overflow-hidden shadow-cyan-500/40 shadow-lg">
                <div class="absolute top-0 w-full h-1 bg-cyan-400"></div>
                <span class="material-icons text-white text-[18px]">how_to_reg</span>
              </div>
              <div>
                <p class="text-sm font-bold text-cyan-400">Embarcat</p>
                <p class="text-xs text-slate-500">QR escanejat a la porta d'embarcament.</p>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Ocupació Resum -->
        <div class="glass-panel p-6 rounded-xl border border-slate-700/50" v-if="vol.modelAvio">
          <div class="flex justify-between items-end mb-2">
            <h3 class="text-sm font-bold text-slate-400 uppercase tracking-wider">Ocupació Total</h3>
            <span class="text-2xl font-bold text-white">{{ seientsComprats.length + seientsBloquejats.length }}<span class="text-sm text-slate-500">/{{ vol.modelAvio.seientsTotals }}</span></span>
          </div>
          <div class="w-full bg-slate-800 rounded-full h-2 mb-4 overflow-hidden flex">
            <!-- Bloquejats -->
            <div class="bg-orange-500 h-2 transition-all duration-500" :style="`width: ${(seientsBloquejats.length / vol.modelAvio.seientsTotals) * 100}%`"></div>
            <!-- Comprats -->
            <div class="bg-green-500 h-2 transition-all duration-500" :style="`width: ${(seientsComprats.length / vol.modelAvio.seientsTotals) * 100}%`"></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.glass-panel {
  background: rgba(16, 25, 34, 0.6);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
}
.animate-pulse-slow {
  animation: pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
</style>
