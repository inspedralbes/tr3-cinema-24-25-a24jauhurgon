<script>
// Component AppHeader — Capçalera principal de l'aplicació (Refactoritzada Fase 5)
import { useAuthStore } from '../stores/authStore.js'

export default {
  name: 'AppHeader',
  props: {
    titol: { type: String, default: '' },
    subtitol: { type: String, default: '' },
    mostrarTemps: { type: Boolean, default: false },
    tempsRestant: { type: String, default: '00:00' },
    mostrarTotal: { type: Boolean, default: false },
    total: { type: Number, default: 0 },
    texteBoto: { type: String, default: '' },
    mostrarEnrere: { type: Boolean, default: false },
    enllaçEnrere: { type: String, default: '/vols' }
  },
  emits: ['accio', 'enrere'],
  computed: {
    authStore: function () {
      return useAuthStore()
    },
    estaAutenticat: function () {
      return this.authStore.estaAutenticat
    },
    usuari: function () {
      return this.authStore.usuari
    },
    rutaActual: function () {
      return this.$route.path
    }
  },
  methods: {
    ferAccio: function () {
      this.$emit('accio')
    },
    anarEnrere: function () {
      if (this.mostrarEnrere) {
        this.$emit('enrere')
      } else {
        this.$router.push(this.enllaçEnrere)
      }
    },
    ferLogout: function () {
      var self = this
      self.authStore.logout().then(function () {
        self.$router.push('/')
      })
    }
  }
}
</script>

<template>
  <header class="w-full border-b border-primary/10 px-6 py-4 flex justify-between items-center bg-[var(--color-background-dark)]/80 backdrop-blur-xl sticky top-0 z-50">
    <!-- Esquerra: Logo i Navegació principal -->
    <div class="flex items-center gap-8">
      <div class="flex items-center gap-3 cursor-pointer" @click="anarEnrere">
        <div class="w-8 h-8 bg-primary rounded-lg flex items-center justify-center shadow-lg shadow-primary/20">
          <span class="material-icons text-white text-xl">flight_takeoff</span>
        </div>
        <div>
          <span v-if="!titol" class="font-extrabold tracking-tight text-xl">last24<span class="text-primary">bcn</span></span>
          <h1 v-else class="text-lg font-bold leading-none">{{ titol }}</h1>
          <p v-if="subtitol" class="text-[10px] uppercase font-bold text-slate-500 tracking-widest mt-0.5">{{ subtitol }}</p>
        </div>
      </div>

      <!-- Nav principal (només si no hi ha titol personalitzat, indicant que som a les pantalles principals) -->
      <nav v-if="!titol" class="hidden md:flex items-center gap-6 font-medium">
        <router-link to="/vols" 
                     class="text-xs uppercase tracking-widest transition-colors flex items-center gap-2"
                     :class="rutaActual === '/vols' ? 'text-primary font-bold' : 'text-slate-400 hover:text-white'">
          <span class="material-icons text-sm" v-if="rutaActual === '/vols'">bolt</span>
          Vols Actius
        </router-link>
        <router-link to="/historial" 
                     class="text-xs uppercase tracking-widest transition-colors flex items-center gap-2"
                     :class="rutaActual === '/historial' ? 'text-primary font-bold' : 'text-slate-400 hover:text-white'">
          <span class="material-icons text-sm" v-if="rutaActual === '/historial'">history</span>
          Historial
        </router-link>
      </nav>
    </div>

    <!-- Centre: Temporitzador (opcional, per Seatmap) -->
    <div v-if="mostrarTemps" class="flex items-center gap-3 bg-red-500/10 border border-red-500/20 px-4 py-2 rounded-xl">
      <span class="material-icons text-red-500 text-sm">timer</span>
      <div class="flex flex-col items-center">
        <span class="text-xl font-mono font-extrabold text-red-500 leading-none">{{ tempsRestant }}</span>
        <span class="text-[10px] uppercase font-bold text-red-400/80">Sessió</span>
      </div>
    </div>

      <!-- Dreta: Accions, Perfil i Logout -->
    <div class="flex items-center gap-4">
      
      <!-- Enllaç Admin -->
      <router-link v-if="authStore.esAdmin" to="/admin" 
                   class="mr-2 px-3 py-1.5 bg-primary/20 text-primary border border-primary/30 rounded-lg text-[10px] font-bold tracking-widest uppercase hover:bg-primary hover:text-white transition-colors flex items-center gap-1">
        <span class="material-icons text-[14px]">admin_panel_settings</span>Admin
      </router-link>

      <!-- Estatus de l'usuari actual -->
      <div v-if="authStore.estaAutenticat" 
           class="flex items-center justify-center px-4 py-2 rounded-lg border font-bold text-xs uppercase transition-colors"
           :class="authStore.esPremium ? 'bg-amber-400/20 border-amber-400/30 text-amber-400' : 'bg-slate-800/50 border-slate-700/50 text-slate-400'">
        <template v-if="authStore.esPremium">
          <span class="material-icons text-sm mr-1">workspace_premium</span> Premium
        </template>
        <template v-else>
          Client
        </template>
      </div>

      <!-- Total (opcional, per Seatmap) -->
      <div v-if="mostrarTotal" class="text-right hidden md:block mr-2">
        <p class="text-[10px] text-slate-500 uppercase font-bold tracking-widest">Total</p>
        <p class="text-xl font-black text-primary">€{{ total.toFixed(2) }}</p>
      </div>

      <!-- Botó d'acció principal (opcional) -->
      <button v-if="texteBoto" 
              class="bg-primary hover:bg-primary/90 text-white px-6 py-2 rounded-lg font-bold text-xs uppercase tracking-widest transition-all shadow-lg shadow-primary/20" 
              @click="ferAccio">
        {{ texteBoto }}
      </button>

      <!-- Perfil d'usuari directament al Header -->
      <template v-if="estaAutenticat">
        <div class="flex items-center gap-3 px-3 py-1.5 bg-white/5 rounded-full border border-white/10 ml-2">
          <img v-if="usuari && usuari.avatar" :src="usuari.avatar" class="w-6 h-6 rounded-full border border-primary/30" />
          <span v-else class="material-icons text-slate-400 text-lg">account_circle</span>
          <span class="text-xs font-bold text-slate-200 hidden sm:inline">{{ usuari ? usuari.name : 'Usuari' }}</span>
        </div>
        <button @click="ferLogout" class="text-xs font-bold text-slate-500 hover:text-red-400 transition-colors uppercase tracking-tighter">Sortir</button>
      </template>
      
      <router-link v-else to="/" class="bg-primary hover:bg-primary/90 text-white text-xs font-bold px-4 py-2 rounded-lg transition-all shadow-lg shadow-primary/20">
        Iniciar Sessió
      </router-link>
    </div>
  </header>
</template>
