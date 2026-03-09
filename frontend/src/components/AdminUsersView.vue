<script>
// Vista AdminUsuaris — (A16) Gestió de rols d'usuari
import AppHeader from './AppHeader.vue'
import { useAuthStore } from '../stores/authStore.js'
import api from '../services/apiService.js'
import socketService from '../services/socketService.js'

export default {
  name: 'AdminUsersView',
  components: { AppHeader },
  data() {
    return {
      usuaris: [],
      usuarisOnline: [], // IDs dels usuaris actualment connectats
      carregant: true,
      error: null
    }
  },
  computed: {
    authStore() { return useAuthStore() }
  },
  methods: {
    carregarUsuaris() {
      this.carregant = true
      this.error = null
      api.get('/admin/usuaris')
        .then(res => {
          this.usuaris = res.data.usuaris
        })
        .catch(err => {
          console.error("Error carregant usuaris", err)
          this.error = "No s'ha pogut obtenir la llista d'usuaris."
        })
        .finally(() => {
          this.carregant = false
        })
    },
    toggleSoci(usuari) {
      if (usuari.rol === 'admin') {
        alert("No pots modificar el rol d'un administrador.")
        return
      }

      // NO usem Optimistic UI aquí, esperarem que el Socket ens avisi
      // Això evita salts estranys si fem molts clics
      const rolAnterior = usuari.rol
      
      // Mostrar carregant animació al botó directament (opcional) o simplement deshabilitar
      usuari.is_updating = true

      api.post(`/admin/usuaris/${usuari.id}/toggle-soci`)
        .then(res => {
          // El rol s'actualitzarà via socket per a TOTHOM (inclòs aquest client)
        })
        .catch(err => {
          console.error("Error canviant rol", err)
          alert("Error al canviar el rol de l'usuari.")
        })
        .finally(() => {
          usuari.is_updating = false
        })
    },
    formatData(dataIso) {
      if (!dataIso) return '-'
      const d = new Date(dataIso)
      return d.toLocaleDateString() + ' ' + d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
    },
    configurarSockets() {
      socketService.connectar()
      
      socketService.onUsuarisOnline((llistaIds) => {
        this.usuarisOnline = llistaIds
      })

      // 3. Escoltar quan un nou usuari es registra
      socketService.onNouUsuariRegistrat((usuariData) => {
        // Assegurar-nos que no duplicarem per error si ja hi era (cosa rara però possible)
        const creatIgat = this.usuaris.find(u => u.id === usuariData.id)
        if (!creatIgat) {
          // Inserir al principi de la llista (ja que està invertida cronològicament)
          this.usuaris.unshift(usuariData)
          if (this.mostrarToast) {
            this.mostrarToast(`Nou usuari registrat: ${usuariData.name}`, 'info')
          }
        }
      })

      // 4. Escoltar canvis de rol emesos pel backend
      socketService.onRolActualitzat((data) => {
        // data = { usuari_id, nou_rol }
        const index = this.usuaris.findIndex(u => u.id === data.usuari_id)
        if (index !== -1) {
          this.usuaris[index].rol = data.nou_rol
        }
      })

      // Demanar la llista actual un cop els oients estan registrats
      socketService.demanarUsuarisOnline()
    }
  },
  inject: ['mostrarToast'],
  mounted() {
    this.carregarUsuaris()
    this.configurarSockets()
  },
  unmounted() {
    socketService.netejarListeners()
  }
}
</script>

<template>
  <div class="min-h-screen">
    <AppHeader />

    <div class="max-w-7xl mx-auto p-6 space-y-6 animate-fade-in-up">
      <div class="flex items-center gap-4 border-b border-slate-800 pb-6">
        <button @click="$router.push('/admin')" class="w-10 h-10 rounded-full bg-slate-800/50 hover:bg-slate-700 flex items-center justify-center text-slate-300 transition-colors">
          <span class="material-icons">arrow_back</span>
        </button>
        <div class="flex-1 flex items-center justify-between">
          <div>
            <h2 class="text-2xl font-bold text-white mb-1">Gestió d'Usuaris i Rols</h2>
            <p class="text-sm text-slate-400">Administra l'estatus Premium (Soci) dels clients registrats. Actulització en temps real.</p>
          </div>
        </div>
      </div>

    <div v-if="error" class="bg-red-500/10 border border-red-500/50 p-4 rounded-lg flex items-center gap-3">
      <span class="material-icons text-red-500">error_outline</span>
      <span class="text-red-400 text-sm font-semibold">{{ error }}</span>
    </div>

    <!-- Taula d'Usuaris -->
    <div class="glass-panel rounded-xl border border-slate-700/50 overflow-hidden shadow-2xl relative">
      <div v-if="carregant" class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm z-10 flex items-center justify-center">
        <span class="material-icons animate-spin text-primary text-4xl">sync</span>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="bg-slate-800/80 border-b border-slate-700/80">
              <th class="p-4 text-xs font-bold uppercase tracking-wider text-slate-400">ID</th>
              <th class="p-4 text-xs font-bold uppercase tracking-wider text-slate-400">Usuari</th>
              <th class="p-4 text-xs font-bold uppercase tracking-wider text-slate-400">Rol Sistema</th>
              <th class="p-4 text-xs font-bold uppercase tracking-wider text-slate-400">Registre</th>
              <th class="p-4 text-xs font-bold uppercase tracking-wider text-slate-400 text-right">Estatus Premium</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-700/50">
            <tr v-for="usuari in usuaris" :key="usuari.id" class="hover:bg-slate-800/30 transition-colors">
              <td class="p-4 text-sm text-slate-500">#{{ usuari.id }}</td>
              <td class="p-4">
                <div class="flex items-center gap-3">
                  <!-- Indicador Online/Offline -->
                  <div class="relative flex items-center justify-center w-8 h-8 rounded-full bg-slate-800 border border-slate-700">
                    <span class="material-icons text-slate-400 text-lg">person</span>
                    <span class="absolute -bottom-0.5 -right-0.5 w-3 h-3 border-2 border-[var(--color-background-dark)] rounded-full"
                          :class="usuarisOnline.includes(usuari.id) ? 'bg-green-500' : 'bg-slate-500'"></span>
                  </div>
                  <div class="flex flex-col">
                    <div class="flex items-center gap-2">
                       <span class="text-white font-medium">{{ usuari.name }}</span>
                       <!-- Insígnia Google -->
                       <span v-if="usuari.google_id" class="flex items-center justify-center w-4 h-4 rounded-full bg-white ml-1" title="Registrat amb Google">
                         <img src="https://upload.wikimedia.org/wikipedia/commons/c/c1/Google_%22G%22_logo.svg" alt="Google Logo" class="w-2.5 h-2.5" />
                       </span>
                    </div>
                    <span class="text-xs text-slate-400">{{ usuari.email }}</span>
                  </div>
                </div>
              </td>
              <td class="p-4">
                <div class="flex items-center gap-2">
                  <!-- Badge per als 3 rols -->
                  <span v-if="usuari.rol === 'admin'" class="px-2 py-0.5 text-[10px] uppercase tracking-wider font-bold rounded bg-red-500/20 text-red-400 border border-red-500/30">
                    Admin
                  </span>
                  <span v-else-if="usuari.rol === 'premium'" class="px-2 py-0.5 text-[10px] uppercase tracking-wider font-bold rounded bg-amber-400/20 text-amber-400 border border-amber-400/30 flex items-center gap-1">
                    <span class="material-icons text-[10px]">workspace_premium</span> Premium
                  </span>
                  <span v-else class="px-2 py-0.5 text-[10px] uppercase tracking-wider font-bold rounded bg-slate-700 text-slate-300">
                    General
                  </span>
                </div>
              </td>
              <td class="p-4 text-xs text-slate-400">{{ formatData(usuari.created_at) }}</td>
              
              <!-- Toggle Rol (General <-> Premium) -->
              <td class="p-4 flex justify-end">
                <button v-if="usuari.rol !== 'admin'" @click="toggleSoci(usuari)" 
                        class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                        :class="usuari.rol === 'premium' ? 'bg-amber-400' : 'bg-slate-600'"
                        :title="usuari.rol === 'premium' ? 'Clic per canviar a General' : 'Clic per canviar a Premium'">
                  <span aria-hidden="true" 
                        class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                        :class="usuari.rol === 'premium' ? 'translate-x-5' : 'translate-x-0'"></span>
                </button>
                <span v-else class="text-xs text-slate-500 italic pr-2">Admin</span>
              </td>
            </tr>
            <tr v-if="!carregant && usuaris.length === 0">
              <td colspan="5" class="p-8 text-center text-slate-500">
                Cap usuari registrat.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
    </div>
  </div>
</template>
