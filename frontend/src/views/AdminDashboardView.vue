<script>
import AppHeader from '../components/AppHeader.vue'
import api from '../services/apiService.js'
import { useAuthStore } from '../stores/authStore.js'
import socketService from '../services/socketService.js'

export default {
  name: 'AdminDashboardView',
  components: { AppHeader },
  data: function () {
    return {
      vols: [],
      carregant: true,
      error: null
    }
  },
  computed: {
    authStore() {
      return useAuthStore()
    }
  },
  methods: {
    carregarDades: function (silent = false) {
      var self = this
      if (!silent) self.carregant = true;

      api.get('/admin/monitoritzacio')
        .then(function (response) {
          self.vols = response.data.monitoritzacio || []
          self.carregant = false
        })
        .catch(function (error) {
          console.error("Error carregant dades admin", error)
          self.error = "No s'han pogut carregar les dades del dashboard. Comprova que tens permisos d'administrador."
          self.carregant = false
        })
    },
    forcarEstatVenda: function (volId, nouEstat) {
      var self = this
      // Optimisme: Canviem l'estat localment per a un feedback instantani
      const vol = self.vols.find(v => v.volId === volId)
      if (vol) vol.estat_venda = nouEstat

      api.post('/admin/vols-interns/' + volId + '/force-status', { estat_venda: nouEstat })
        .then(function () {
          self.carregarDades(true) // Silent refresh
        })
        .catch(function (error) {
          console.error("Error forçant estat", error)
          alert("Error en forçar l'estat: " + (error.response?.data?.missatge || error.message))
          self.carregarDades() // Tornar a l'estat real si falla
        })
    },
    formatEstatVenda: function (estat) {
      if (estat === 'tancat') return { text: 'TANCADA', class: 'bg-slate-700 text-slate-300' }
      if (estat === 'obert') return { text: 'OBERTA', class: 'bg-green-500/20 text-green-400 border border-green-500/30' }
      if (estat === 'finalitzat') return { text: 'FINALITZADA', class: 'bg-primary/20 text-primary border border-primary/30' }
      return { text: estat, class: 'bg-slate-800 text-slate-400' }
    }
  },
  mounted: function () {
    var self = this

    // Assegurar que estem autenticats i som admin
    if (!self.authStore.estaAutenticat) {
      self.$router.push('/')
      return
    }

    self.carregarDades()

    // Escoltar de forma global si les mètriques canvien
    socketService.onMonitoritzacioActualitzada(() => {
      // Passem 'true' per fer un silent refresh (sense spinner de pantalla completa)
      self.carregarDades(true)
    })

    // Fase 13: Escoltar cada check-in individual per augmentar la barra a l'instant
    socketService.onBarretaEmbarcamentActualitzada((data) => {
      const volId = data.volId;
      const volTarget = self.vols.find(v => v.volId === volId);
      if (volTarget) {
        volTarget.seientsEmbarcats++;
      }
    })
  },
  beforeUnmount: function () {
    socketService.netejarListeners()
  }
}
</script>

<template>
  <div class="min-h-screen">
    <AppHeader />

    <div class="max-w-7xl mx-auto p-6">
      <header class="mb-6 sm:mb-8 flex flex-col sm:flex-row justify-between items-start sm:items-end gap-4">
        <div>
          <div class="inline-flex w-10 h-10 rounded-lg overflow-hidden shadow-lg shadow-black/40 align-middle mr-2 mt-[-4px]">
            <img src="/logo.png" alt="Logo" class="w-full h-full object-cover mix-blend-screen" />
          </div>
          Dashboard <span class="text-slate-500 font-normal">Sales Simulator</span>
          <p class="text-slate-400">Monitorització de vols entrants i manual override d'estats de venda.</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
          <button @click="$router.push('/admin/scanner')"
            class="px-5 py-2 bg-primary hover:bg-blue-600 text-white rounded-lg flex items-center gap-2 font-bold tracking-wider shadow-[0_0_15px_rgba(59,130,246,0.3)] transition-all">
            <span class="material-icons text-[18px]">qr_code_scanner</span>
            Mode Escàner
          </button>

          <button @click="$router.push('/admin/usuaris')"
            class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-white rounded-lg flex items-center gap-2 transition-colors">
            <span class="material-icons text-sm">people</span>
            Gestió d'Usuaris
          </button>
        </div>
      </header>

      <div v-if="error"
        class="bg-red-500/10 border border-red-500/30 text-red-400 p-4 rounded-lg mb-6 flex items-start gap-3">
        <span class="material-icons">error_outline</span>
        <p>{{ error }}</p>
      </div>

      <div class="bg-slate-900 border border-slate-800 rounded-xl shadow-2xl overflow-x-auto w-full">
        <table class="w-full text-left border-collapse min-w-[800px]">
          <thead>
            <tr class="bg-slate-800/50 text-slate-400 text-xs uppercase tracking-wider">
              <th class="p-4 font-semibold border-b border-slate-800">Vol / Destí</th>
              <th class="p-4 font-semibold border-b border-slate-800">Simulador Vol Entrant</th>
              <th class="p-4 font-semibold border-b border-slate-800 text-center">Estat Venda</th>
              <th class="p-4 font-semibold border-b border-slate-800 text-center">Mètriques</th>
              <th class="p-4 font-semibold border-b border-slate-800 text-right">Accions (Override)</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-800/50 text-sm">
            <tr v-if="carregant && vols.length === 0">
              <td colspan="5" class="p-8 text-center text-slate-500">
                <span class="material-icons animate-spin text-3xl mb-2 text-primary">autorenew</span>
                <p>Carregant dades del simulador...</p>
              </td>
            </tr>
            <tr v-for="vol in vols" :key="vol.volId" class="hover:bg-slate-800/20 transition-colors">

              <!-- Vol Info -->
              <td class="p-4 align-top">
                <div class="flex items-center gap-3">
                  <div class="w-10 h-10 rounded-lg flex items-center justify-center bg-slate-800 overflow-hidden">
                    <img src="/logo.png" alt="Logo" class="w-full h-full object-cover mix-blend-screen" />
                  </div>
                  <div>
                    <div class="font-bold text-lg text-white mb-0.5">{{ vol.destiIata }}</div>
                    <div class="text-xs text-slate-500">{{ new Date(vol.dataHoraSortida).toLocaleString('ca-ES', {
                      hour:
                        '2-digit', minute:'2-digit'}) }}h • ID: {{ vol.volId }}</div>
                  </div>
                </div>
              </td>

              <!-- Simulador Entrant -->
              <td class="p-4 align-top">
                <div v-if="vol.vol_entrant_origen"
                  class="bg-slate-800/40 rounded-lg p-2.5 border border-slate-700/50 inline-block">
                  <div class="text-xs text-slate-400 mb-1 flex items-center gap-1">
                    <span class="material-icons text-[14px]">flight_land</span> Arribant de <b>{{ vol.vol_entrant_origen
                      }}</b>
                  </div>
                  <div class="text-[11px] text-slate-500 font-mono">
                    ETA: {{ new Date(vol.hora_arribada_esperada).toLocaleTimeString('ca-ES', {
                      hour: '2-digit',
                      minute: '2-digit', second:'2-digit'}) }}
                  </div>
                </div>
                <div v-else class="text-xs text-slate-500 italic py-2">No simulat</div>
              </td>

              <!-- Estat Venda -->
              <td class="p-4 align-top text-center">
                <span
                  class="px-2.5 py-1 rounded-full text-[10px] font-bold tracking-wider inline-flex items-center justify-center mt-2.5"
                  :class="formatEstatVenda(vol.estat_venda).class">
                  {{ formatEstatVenda(vol.estat_venda).text }}
                </span>
              </td>

              <!-- Mètriques -->
              <td class="p-4 align-top">
                <div class="flex justify-center gap-3 mt-1.5 mb-3">
                  <div class="text-center" title="Venuts">
                    <div class="text-lg font-black text-white">{{ vol.seientsComprats }}</div>
                    <div class="text-[9px] uppercase tracking-wider text-slate-500 font-bold">Venuts</div>
                  </div>
                  <div class="text-center" title="En procés (Holds)">
                    <div class="text-lg font-black text-amber-500">{{ vol.holdsActius }}</div>
                    <div class="text-[9px] uppercase tracking-wider text-slate-500 font-bold">Holds</div>
                  </div>
                  <div class="text-center" title="Usuaris a la Cua">
                    <div class="text-lg font-black text-slate-300">{{ vol.cuaEsperant }}</div>
                    <div class="text-[9px] uppercase tracking-wider text-slate-500 font-bold">Cua</div>
                  </div>
                </div>

                <!-- Barra Embarcament (Fase 13) -->
                <div v-if="vol.seientsComprats > 0" class="w-full mt-2">
                  <div class="flex justify-between text-[10px] uppercase tracking-widest text-slate-400 font-bold mb-1">
                    <span>Embarcament</span>
                    <span :class="vol.seientsEmbarcats === vol.seientsComprats ? 'text-green-400' : 'text-primary'">
                      {{ vol.seientsEmbarcats }} / {{ vol.seientsComprats }}
                    </span>
                  </div>
                  <div class="h-2 w-full bg-slate-800 rounded-full overflow-hidden border border-slate-700">
                    <div class="h-full bg-primary transition-all duration-500 ease-out"
                      :class="{ 'bg-green-500': vol.seientsEmbarcats === vol.seientsComprats }"
                      :style="{ width: Math.max(0, Math.min(100, (vol.seientsEmbarcats / vol.seientsComprats) * 100)) + '%' }">
                    </div>
                  </div>
                </div>
              </td>

              <!-- Accions Manuals (Forçar Estat) -->
              <td class="p-4 align-top text-right">
                <div class="flex flex-col items-end gap-2">
                  <button @click="$router.push('/admin/vol/' + vol.volId + '/seatmap')"
                    class="px-4 py-2 text-xs font-bold rounded-lg flex items-center justify-between w-40 transition-all bg-slate-800 hover:bg-slate-700 text-white border border-slate-700">
                    <span>Live Seatmap</span>
                    <span class="material-icons text-[16px]">map</span>
                  </button>

                  <button v-if="vol.estat_venda === 'tancat'" @click="forcarEstatVenda(vol.volId, 'obert')"
                    class="px-4 py-2 text-xs font-bold rounded-lg flex items-center justify-between w-40 transition-all shadow-lg bg-green-500 hover:bg-green-400 text-white shadow-green-500/20">
                    <span>Obrir Venda</span>
                    <span class="material-icons text-[16px]">play_circle</span>
                  </button>

                  <button v-else-if="vol.estat_venda === 'obert'" @click="forcarEstatVenda(vol.volId, 'finalitzat')"
                    class="px-4 py-2 text-xs font-bold rounded-lg flex items-center justify-between w-40 transition-all bg-primary hover:bg-blue-500 text-white">
                    <span>Finalitzar Venda</span>
                    <span class="material-icons text-[16px]">stop_circle</span>
                  </button>

                  <button v-else-if="vol.estat_venda === 'finalitzat'" disabled
                    class="px-4 py-2 text-xs font-bold rounded-lg flex items-center justify-between w-40 transition-all bg-slate-800 text-slate-500 border border-slate-700 cursor-not-allowed">
                    <span>Finalitzada</span>
                    <span class="material-icons text-[16px]">check_circle</span>
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="!carregant && vols.length === 0">
              <td colspan="5" class="p-8 text-center text-slate-500">
                L'API no ha retornat vols per a monitoritzar.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>
