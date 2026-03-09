<script>
// Vista Login — Pantalla d'accés (A1)
import { useAuthStore } from '../stores/authStore.js'

export default {
  name: 'LoginView',
  data: function () {
    return {
      modeActiu: 'login', // 'login' o 'registre'
      email: '',
      password: '',
      nom: '',
      passwordConfirmacio: '',
      errorMissatge: ''
    }
  },
  computed: {
    authStore: function () {
      return useAuthStore()
    }
  },
  methods: {
    canviarMode: function (mode) {
      this.modeActiu = mode
      this.errorMissatge = ''
    },
    ferLogin: function () {
      var self = this
      self.errorMissatge = ''
      self.authStore.login(self.email, self.password).then(function () {
        self.$router.push('/vols')
      }).catch(function () {
        self.errorMissatge = self.authStore.error || 'Error de login'
      })
    },
    ferRegistre: function () {
      var self = this
      self.errorMissatge = ''
      self.authStore.registre(self.nom, self.email, self.password, self.passwordConfirmacio).then(function () {
        self.$router.push('/vols')
      }).catch(function () {
        self.errorMissatge = self.authStore.error || 'Error de registre'
      })
    },
    entrarConvidat: function () {
      this.authStore.inicialitzarClient()
      this.$router.push('/vols')
    },
    ferLoginGoogle: function () {
      this.authStore.loginGoogle()
    }
  },
  mounted: function () {
    this.authStore.inicialitzarClient()
    if (this.authStore.estaAutenticat) {
      this.$router.push('/vols')
    }
  }
}
</script>

<template>
  <div class="min-h-screen flex items-center justify-center relative overflow-hidden">
    <!-- Fons amb overlay -->
    <div class="absolute inset-0 z-0">
      <div class="absolute inset-0 bg-gradient-to-b from-[var(--color-background-dark)]/80 via-[var(--color-background-dark)]/60 to-[var(--color-background-dark)]"></div>
      <div class="absolute inset-0 bg-gradient-to-br from-primary/10 to-transparent"></div>
    </div>

    <!-- Contingut principal -->
    <main class="relative z-10 w-full max-w-lg px-6 py-12">
      <!-- Branding -->
      <div class="text-center mb-8">
        <div class="inline-flex items-center gap-2 bg-primary/10 border border-primary/20 px-3 py-1 rounded-full mb-6">
          <span class="flex h-2 w-2 relative">
            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-primary opacity-75"></span>
            <span class="relative inline-flex rounded-full h-2 w-2 bg-primary"></span>
          </span>
          <span class="text-xs font-semibold tracking-wider uppercase text-primary">Connexió en temps real</span>
        </div>
        <h1 class="text-4xl md:text-5xl font-bold tracking-tight text-white mb-2">
          BCN <span class="text-primary font-light">Last Minute</span>
        </h1>
        <p class="text-slate-400 text-lg">Sortides en temps real. Reserva instantània.</p>
      </div>

      <!-- Targeta d'autenticació -->
      <div class="glass-panel rounded-xl shadow-2xl overflow-hidden" style="background: rgba(16, 25, 34, 0.7); backdrop-filter: blur(12px); border: 1px solid rgba(19, 127, 236, 0.2);">
        <!-- Botons de mode -->
        <div class="flex border-b border-primary/10">
          <button class="flex-1 py-4 text-sm font-semibold transition-colors"
                  :class="modeActiu === 'login' ? 'text-white border-b-2 border-primary' : 'text-slate-400 hover:text-white'"
                  @click="canviarMode('login')">
            Iniciar Sessió
          </button>
          <button class="flex-1 py-4 text-sm font-semibold transition-colors"
                  :class="modeActiu === 'registre' ? 'text-white border-b-2 border-primary' : 'text-slate-400 hover:text-white'"
                  @click="canviarMode('registre')">
            Registrar-se
          </button>
        </div>

        <div class="p-8">
          <!-- Avantatge soci -->
          <div v-if="modeActiu === 'login'" class="mb-6 p-4 bg-primary/10 rounded-lg border border-primary/20 flex items-start gap-3">
            <span class="material-icons text-primary text-xl">loyalty</span>
            <p class="text-sm text-slate-300">
              <strong class="text-white">Avantatge Soci:</strong> Accedeix a tarifes exclusives. Els socis estalvien un 20% en sortides BCN.
            </p>
          </div>

          <!-- Error -->
          <div v-if="errorMissatge" class="mb-4 p-3 bg-red-500/10 border border-red-500/20 rounded-lg text-red-400 text-sm">
            {{ errorMissatge }}
          </div>

          <!-- Formulari Login -->
          <form v-if="modeActiu === 'login'" @submit.prevent="ferLogin" class="space-y-5">
            <div>
              <label class="block text-xs font-bold uppercase tracking-widest text-slate-400 mb-2">Correu electrònic</label>
              <div class="relative">
                <span class="material-icons absolute left-4 top-1/2 -translate-y-1/2 text-slate-500 text-xl">mail_outline</span>
                <input v-model="email" type="email" placeholder="nom@correu.com"
                       class="w-full bg-[var(--color-background-dark)]/50 border border-primary/20 rounded-lg py-3 pl-12 pr-4 text-white placeholder-slate-600 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all" />
              </div>
            </div>
            <div>
              <label class="block text-xs font-bold uppercase tracking-widest text-slate-400 mb-2">Contrasenya</label>
              <div class="relative">
                <span class="material-icons absolute left-4 top-1/2 -translate-y-1/2 text-slate-500 text-xl">lock_open</span>
                <input v-model="password" type="password" placeholder="••••••••"
                       class="w-full bg-[var(--color-background-dark)]/50 border border-primary/20 rounded-lg py-3 pl-12 pr-4 text-white placeholder-slate-600 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all" />
              </div>
            </div>
            <button type="submit" :disabled="authStore.carregant"
                    class="w-full bg-primary hover:bg-primary/90 text-white font-bold py-4 rounded-lg shadow-lg shadow-primary/20 transition-all flex items-center justify-center gap-2">
              <span v-if="authStore.carregant" class="material-icons animate-spin">sync</span>
              <span>{{ authStore.carregant ? 'Connectant...' : 'Iniciar Sessió' }}</span>
              <span v-if="!authStore.carregant" class="material-icons text-lg">arrow_forward</span>
            </button>

            <!-- Botó Google -->
            <button type="button" @click="ferLoginGoogle"
                    class="w-full bg-white hover:bg-slate-100 text-slate-900 font-bold py-3 rounded-lg shadow-md transition-all flex items-center justify-center gap-3 border border-slate-200">
              <svg class="w-5 h-5" viewBox="0 0 24 24">
                <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z"/>
                <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 12-4.53z"/>
              </svg>
              Continuar amb Google
            </button>
          </form>

          <!-- Formulari Registre -->
          <form v-if="modeActiu === 'registre'" @submit.prevent="ferRegistre" class="space-y-5">
            <div>
              <label class="block text-xs font-bold uppercase tracking-widest text-slate-400 mb-2">Nom complet</label>
              <div class="relative">
                <span class="material-icons absolute left-4 top-1/2 -translate-y-1/2 text-slate-500 text-xl">person</span>
                <input v-model="nom" type="text" placeholder="El teu nom"
                       class="w-full bg-[var(--color-background-dark)]/50 border border-primary/20 rounded-lg py-3 pl-12 pr-4 text-white placeholder-slate-600 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all" />
              </div>
            </div>
            <div>
              <label class="block text-xs font-bold uppercase tracking-widest text-slate-400 mb-2">Correu electrònic</label>
              <div class="relative">
                <span class="material-icons absolute left-4 top-1/2 -translate-y-1/2 text-slate-500 text-xl">mail_outline</span>
                <input v-model="email" type="email" placeholder="nom@correu.com"
                       class="w-full bg-[var(--color-background-dark)]/50 border border-primary/20 rounded-lg py-3 pl-12 pr-4 text-white placeholder-slate-600 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all" />
              </div>
            </div>
            <div>
              <label class="block text-xs font-bold uppercase tracking-widest text-slate-400 mb-2">Contrasenya</label>
              <div class="relative">
                <span class="material-icons absolute left-4 top-1/2 -translate-y-1/2 text-slate-500 text-xl">lock_open</span>
                <input v-model="password" type="password" placeholder="Mínim 6 caràcters"
                       class="w-full bg-[var(--color-background-dark)]/50 border border-primary/20 rounded-lg py-3 pl-12 pr-4 text-white placeholder-slate-600 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all" />
              </div>
            </div>
            <div>
              <label class="block text-xs font-bold uppercase tracking-widest text-slate-400 mb-2">Confirmar contrasenya</label>
              <div class="relative">
                <span class="material-icons absolute left-4 top-1/2 -translate-y-1/2 text-slate-500 text-xl">lock</span>
                <input v-model="passwordConfirmacio" type="password" placeholder="Repeteix la contrasenya"
                       class="w-full bg-[var(--color-background-dark)]/50 border border-primary/20 rounded-lg py-3 pl-12 pr-4 text-white placeholder-slate-600 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all" />
              </div>
            </div>
            <button type="submit" :disabled="authStore.carregant"
                    class="w-full bg-primary hover:bg-primary/90 text-white font-bold py-4 rounded-lg shadow-lg shadow-primary/20 transition-all flex items-center justify-center gap-2">
              <span>{{ authStore.carregant ? 'Registrant...' : 'Crear Compte' }}</span>
              <span v-if="!authStore.carregant" class="material-icons text-lg">arrow_forward</span>
            </button>

            <!-- Botó Google (en Registre igualment) -->
            <button type="button" @click="ferLoginGoogle"
                    class="w-full bg-white hover:bg-slate-100 text-slate-900 font-bold py-3 rounded-lg shadow-md transition-all flex items-center justify-center gap-3 border border-slate-200 mt-4">
              <svg class="w-5 h-5" viewBox="0 0 24 24">
                <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z"/>
                <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 12-4.53z"/>
              </svg>
              Continuar amb Google
            </button>
          </form>

          <!-- Separador -->
          <div class="relative my-8 text-center">
            <div class="absolute inset-0 flex items-center">
              <div class="w-full border-t border-primary/10"></div>
            </div>
            <span class="relative px-4 text-xs font-bold uppercase tracking-widest text-slate-500" style="background: rgba(16, 25, 34, 0.9);">O continuar sense compte</span>
          </div>

          <!-- Accés convidat -->
          <button class="w-full border border-primary/30 hover:bg-primary/5 text-slate-200 font-semibold py-4 rounded-lg transition-all flex items-center justify-center gap-2"
                  @click="entrarConvidat">
            Entrar com a Convidat
            <span class="material-icons text-lg">person_outline</span>
          </button>
        </div>
      </div>

      <!-- Footer -->
      <footer class="mt-8 flex flex-col items-center gap-4">
        <div class="flex items-center gap-2 text-slate-600 text-[10px] uppercase tracking-tighter">
          <span class="material-icons text-[12px]">security</span>
          <span>Connexió segura xifrada 256-bit</span>
        </div>
      </footer>
    </main>
  </div>
</template>
