<script>
// Vista Historial de Vols — Vols passats amb mapa de seients i privacitat (Fase 4)
import { useVolsStore } from '../stores/volsStore.js'
import AppHeader from '../components/AppHeader.vue'

export default {
  name: 'HistorialVolsView',
  components: { AppHeader: AppHeader },
  data: function () {
    return {
      volsStore: useVolsStore(),
      volExpandit: null
    }
  },
  computed: {
    vols: function () {
      return this.volsStore.historial
    }
  },
  methods: {
    toggleVol: function (volId) {
      if (this.volExpandit === volId) {
        this.volExpandit = null
      } else {
        this.volExpandit = volId
      }
    },
    formatData: function (dataStr) {
      var d = new Date(dataStr)
      var dia = String(d.getDate()).padStart(2, '0')
      var mes = String(d.getMonth() + 1).padStart(2, '0')
      var any = d.getFullYear()
      var h = String(d.getHours()).padStart(2, '0')
      var m = String(d.getMinutes()).padStart(2, '0')
      return dia + '/' + mes + '/' + any + ' ' + h + ':' + m
    },
    tornarVols: function () {
      this.$router.push('/vols')
    },
    // Helper per saber si un seient està ocupat
    esOcupat: function (vol, fila, columna) {
      if (!vol.seientsOcupats) return false
      for (var i = 0; i < vol.seientsOcupats.length; i++) {
        if (vol.seientsOcupats[i].f === fila && vol.seientsOcupats[i].c === columna) {
          return true
        }
      }
      return false
    }
  },
  mounted: function () {
    this.volsStore.carregarHistorial()
  }
}
</script>

<template>
  <div class="min-h-screen flex flex-col bg-[var(--color-background-dark)]">
    <!-- Header -->
    <AppHeader />

    <main class="flex-grow p-6 md:p-10 max-w-6xl mx-auto w-full">
      <!-- Títol -->
      <div class="mb-10 flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div>
          <h1 class="text-3xl md:text-5xl font-extrabold tracking-tighter mb-3 bg-gradient-to-r from-white to-slate-500 bg-clip-text text-transparent">
            Historial de Vols
          </h1>
          <p class="text-slate-400 text-lg">Consulta el trànsit passat i l'ocupació final dels avions.</p>
        </div>
        <div class="flex items-center gap-4 bg-white/5 px-4 py-2 rounded-2xl border border-white/10">
          <div class="text-right">
            <div class="text-[10px] uppercase font-bold text-slate-500 tracking-widest">Total Arxivat</div>
            <div class="text-xl font-black text-primary">{{ vols.length }} Vols</div>
          </div>
          <span class="material-icons text-primary/40 text-3xl">inventory_2</span>
        </div>
      </div>

      <!-- Loading -->
      <div v-if="volsStore.carregant" class="flex flex-col items-center justify-center py-32 gap-4">
        <div class="w-12 h-12 border-4 border-primary/20 border-t-primary rounded-full animate-spin"></div>
        <p class="text-sm font-bold text-primary animate-pulse uppercase tracking-widest">Sincronitzant historial...</p>
      </div>

      <!-- Sense vols -->
      <div v-else-if="vols.length === 0" class="text-center py-32 glass-panel rounded-3xl">
        <div class="w-20 h-20 bg-slate-800/50 rounded-full flex items-center justify-center mx-auto mb-6 border border-white/5">
          <span class="material-icons text-slate-500 text-4xl">folder_off</span>
        </div>
        <p class="text-slate-400 text-xl font-medium">L'historial està buit.</p>
        <p class="text-slate-600 mt-2">Només s'arxiven els vols en els que s'han realitzat compres.</p>
      </div>

      <!-- Llistat de vols -->
      <div v-else class="grid grid-cols-1 gap-6">
        <div v-for="vol in vols" :key="vol.id"
             class="glass-panel rounded-2xl overflow-hidden transition-all duration-500 hover:shadow-2xl hover:shadow-primary/5"
             :class="volExpandit === vol.id ? 'border-primary/30 ring-1 ring-primary/20 scale-[1.01]' : 'border-white/5'">

          <!-- Card header -->
          <div class="p-6 cursor-pointer hover:bg-white/[0.02] transition-colors relative group"
               @click="toggleVol(vol.id)">
            
            <div class="flex flex-col md:flex-row md:items-center gap-6">
              <!-- Indicador Estat/Ruta -->
              <div class="flex items-center gap-4 flex-grow">
                <div class="w-14 h-14 bg-slate-800 rounded-2xl flex items-center justify-center border border-white/10 group-hover:border-primary/30 transition-colors">
                  <span class="material-icons text-slate-400 text-3xl">flight_land</span>
                </div>
                <div>
                  <div class="flex items-center gap-3 mb-1">
                    <span class="text-2xl font-black tracking-tight">{{ vol.origenIata }}</span>
                    <span class="material-icons text-primary text-sm">east</span>
                    <span class="text-2xl font-black tracking-tight">{{ vol.destiIata }}</span>
                    <span class="px-2 py-0.5 bg-slate-800 text-slate-400 rounded text-[10px] font-mono border border-white/10 uppercase">{{ vol.externalId }}</span>
                  </div>
                  <div class="flex items-center gap-4 text-sm text-slate-500">
                    <span class="flex items-center gap-1.5 font-medium">
                      <span class="material-icons text-xs">calendar_today</span>
                      {{ formatData(vol.dataHoraSortida) }}
                    </span>
                    <span v-if="vol.modelAvio" class="flex items-center gap-1.5 font-medium">
                      <span class="material-icons text-xs">airlines</span>
                      {{ vol.modelAvio.nom }}
                    </span>
                  </div>
                </div>
              </div>

              <!-- Stats ràpides -->
              <div class="flex items-center gap-8 px-6 border-l border-white/5 hidden lg:flex">
                <div class="text-center">
                  <div class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Bitllets</div>
                  <div class="text-2xl font-black text-primary">{{ vol.bitlletsComprats }}</div>
                </div>
                <div>
                  <div class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-2">Ocupació</div>
                  <div class="flex items-center gap-3">
                    <div class="w-24 h-2.5 bg-slate-800 rounded-full overflow-hidden border border-white/5 p-0.5">
                      <div class="h-full rounded-full bg-gradient-to-r from-primary to-blue-400 shadow-[0_0_10px_rgba(19,127,236,0.3)] transition-all duration-1000"
                           :style="'width: ' + ((vol.bitlletsComprats / vol.modelAvio.seientsTotals) * 100) + '%'"></div>
                    </div>
                    <span class="text-sm font-black text-white">{{ Math.round((vol.bitlletsComprats / vol.modelAvio.seientsTotals) * 100) }}%</span>
                  </div>
                </div>
              </div>

              <div class="flex items-center justify-between md:justify-end gap-4 mt-4 md:mt-0">
                 <!-- Botó expandir -->
                 <div class="w-10 h-10 rounded-full bg-white/5 flex items-center justify-center border border-white/10 group-hover:bg-primary group-hover:text-white transition-all">
                   <span class="material-icons transition-transform duration-300"
                         :class="volExpandit === vol.id ? 'rotate-180' : ''">expand_more</span>
                 </div>
              </div>
            </div>
          </div>

          <!-- Detalls expandits: READ-ONLY SEATMAP -->
          <div v-if="volExpandit === vol.id" class="border-t border-white/5 bg-slate-900/40 p-8 animate-fade-in">
            <div class="max-w-4xl mx-auto">
              <!-- Info Seatmap -->
              <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
                <div>
                  <h3 class="text-xl font-bold flex items-center gap-2">
                    <span class="material-icons text-primary/80">grid_view</span>
                    Mapa de Seients Final
                  </h3>
                  <p class="text-sm text-slate-400 mt-1">Dades demogràfiques filtrades per privacitat.</p>
                </div>
                <div class="flex gap-4">
                  <div class="flex items-center gap-2">
                    <div class="w-3 h-3 rounded bg-primary border border-primary/50"></div>
                    <span class="text-xs font-bold text-slate-400">Venut</span>
                  </div>
                  <div class="flex items-center gap-2">
                    <div class="w-3 h-3 rounded bg-slate-800 border border-white/10"></div>
                    <span class="text-xs font-bold text-slate-400">Lliure</span>
                  </div>
                </div>
              </div>

              <!-- GID SEATMAP -->
              <div class="bg-slate-950/50 rounded-3xl p-6 md:p-10 border border-white/5 overflow-x-auto shadow-inner">
                <div class="mx-auto min-w-fit flex flex-col items-center">
                  <!-- Capçalera Lletres (A, B, C...) -->
                  <div class="flex gap-1.5 mb-4 ml-10">
                    <div v-for="c in vol.modelAvio.columnes" :key="c" 
                         class="w-6 md:w-8 text-center text-[10px] font-black text-slate-600 uppercase">
                      {{ String.fromCharCode(64 + c) }}
                    </div>
                  </div>

                  <!-- Files -->
                  <div v-for="f in vol.modelAvio.files" :key="f" class="flex items-center gap-1.5 mb-1.5">
                    <!-- Número de fila -->
                    <div class="w-8 text-right pr-2 text-[10px] font-black text-slate-700">{{ f }}</div>
                    
                    <!-- Seients de la fila -->
                    <div v-for="c in vol.modelAvio.columnes" :key="c"                             
                         class="w-6 h-6 md:w-8 md:h-8 rounded flex items-center justify-center transition-all duration-500"
                         :class="[
                            esOcupat(vol, f, c) ? 'bg-primary border border-primary/30 shadow-[0_0_8px_rgba(19,127,236,0.3)]' : 'bg-slate-800/40 border border-white/5',
                            c === vol.modelAvio.columnes / 2 ? 'mr-3 md:mr-6' : ''
                         ]">
                      <span v-if="esOcupat(vol, f, c)" class="material-icons text-white text-[10px] md:text-sm">check</span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Footer Detalls -->
              <div class="mt-10 grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white/5 p-4 rounded-2xl border border-white/5">
                  <div class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Configuració</div>
                  <div class="text-lg font-bold">{{ vol.modelAvio.nom }}</div>
                  <div class="text-xs text-slate-400">{{ vol.modelAvio.files }} files x {{ vol.modelAvio.columnes }} col.</div>
                </div>
                <div class="bg-white/5 p-4 rounded-2xl border border-white/5">
                  <div class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Capacitat</div>
                  <div class="text-lg font-bold">{{ vol.modelAvio.seientsTotals }} seients totals</div>
                  <div class="text-xs text-slate-400">{{ vol.modelAvio.seientsTotals - vol.bitlletsComprats }} seients lliures</div>
                </div>
                <div class="bg-primary/5 p-4 rounded-2xl border border-primary/10">
                  <div class="text-[10px] font-bold text-primary/70 uppercase tracking-widest mb-1">Bitllets Venuts</div>
                  <div class="text-2xl font-black text-primary">{{ vol.bitlletsComprats }}</div>
                  <div class="text-xs text-slate-400">Arxivat permanentment.</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>

    <!-- Footer Simple -->
    <footer class="p-8 text-center text-slate-600 text-xs mt-10">
      &copy; 2026 last24bcn - Sistema de Gestió de Vendes en Temps Real. Les dades del passatgers no són públiques per motius de privacitat RGPD.
    </footer>
  </div>
</template>

<style scoped>
.glass-panel {
  background: rgba(255, 255, 255, 0.03);
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
}

.animate-fade-in {
  animation: fadeIn 0.4s ease-out forwards;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}
</style>
