<script>
// Vista Resum Compra — Revisió amb selecció de tarifa per passatger (A8)
import CountdownTimer from '../components/CountdownTimer.vue'
import { useAuthStore } from '../stores/authStore.js'
import { useCompraStore } from '../stores/compraStore.js'
import { useVolsStore } from '../stores/volsStore.js'
import { useCuaStore } from '../stores/cuaStore.js'

export default {
  name: 'ResumCompraView',
  components: { CountdownTimer: CountdownTimer },
  data: function () {
    return {
      email: '',
      acceptaCondicions: false,
      processant: false,
      errorMissatge: '',
      passatgers: [] // {nom, tarifaId, preu} per cada seient
    }
  },
  computed: {
    authStore: function () { return useAuthStore() },
    compraStore: function () { return useCompraStore() },
    volsStore: function () { return useVolsStore() },
    cuaStore: function () { return useCuaStore() },
    volId: function () { return this.$route.params.id },
    tarifes: function () { return this.volsStore.tarifes },
    totalCalculat: function () {
      var sum = 0
      for (var i = 0; i < this.passatgers.length; i++) {
        sum = sum + parseFloat(this.passatgers[i].preu || 0)
      }
      return sum
    },
    obtenirLletraColumna: function () {
      return function (index) {
        var lletres = ['A', 'B', 'C', 'D', 'E', 'F']
        return lletres[index] || '' + (index + 1)
      }
    }
  },
  methods: {
    canviarTarifa: function (index, tarifaId) {
      // Els Premium no poden canviar tarifa, ja ve fixada
      if (this.authStore.esPremium) return

      var tarifa = null
      for (var i = 0; i < this.tarifes.length; i++) {
        if (this.tarifes[i].id === parseInt(tarifaId)) {
          tarifa = this.tarifes[i]
          break
        }
      }
      if (tarifa) {
        this.passatgers[index].tarifaId = tarifa.id
        this.passatgers[index].preu = tarifa.preu
        this.passatgers[index].nomTarifa = tarifa.nom
      }
    },
    confirmar: function () {
      var self = this
      if (!self.email) {
        self.errorMissatge = 'Cal introduir el correu electrònic.'
        return
      }
      if (!self.acceptaCondicions) {
        self.errorMissatge = 'Cal acceptar les condicions.'
        return
      }

      // Validar que tots els passatgers tenen nom
      for (var j = 0; j < self.passatgers.length; j++) {
        if (!self.passatgers[j].nom || self.passatgers[j].nom.trim() === '') {
          self.errorMissatge = 'Cal introduir el nom de tots els passatgers.'
          return
        }
      }

      self.processant = true
      self.errorMissatge = ''

      // Preparar bitllets amb tarifa i nom seleccionats
      var bitllets = []
      for (var i = 0; i < self.compraStore.seientsSeleccionats.length; i++) {
        var s = self.compraStore.seientsSeleccionats[i]
        var p = self.passatgers[i]
        
        // Validació extra abans d'enviar
        if (!p || !p.tarifaId) {
            self.errorMissatge = 'Error en les tarifes del passatger ' + (i+1)
            self.processant = false
            return
        }

        bitllets.push({
          fila: s.fila,
          columna: s.columna,
          tarifaId: p.tarifaId,
          nomPassatger: p.nom || 'Passatger ' + (i+1),
          esPremium: self.authStore.esPremium 
        })
      }

      self.compraStore.confirmarCompra(self.volId, self.authStore.obtenirClientId, self.email, bitllets).then(function (dades) {
        self.cuaStore.netejar() 
        self.$router.push('/compra/' + dades.compra.id + '/completada')
      }).catch(function (error) {
        self.processant = false
        if (error.response && error.response.status === 422) {
            self.errorMissatge = 'Dades de compra invàlides. Revisa els noms i el correu.'
        } else {
            self.errorMissatge = self.compraStore.error || 'Error processant la compra.'
        }
      })
    },
    tornarSeients: function () {
      this.$router.push('/vol/' + this.volId + '/seients')
    },
    sessioExpirada: function () {
      var self = this
      self.cuaStore.sortirCua(self.volId, self.authStore.obtenirClientId).catch(function () {}).finally(function () {
        // Redirigir amb el volId per a poder "Tornar-ho a intentar"
        self.$router.push({ path: '/sessio-expirada', query: { volId: self.volId } })
      })
    },
    inicialitzarPassatgers: function () {
      var self = this
      
      // Buscar tarifes vàlides
      var tarifaGeneral = null
      var tarifaSoci = null
      
      for (var j = 0; j < self.tarifes.length; j++) {
        var t = self.tarifes[j]
        if (t.nom === 'general') tarifaGeneral = t
        if (t.nom === 'soci') tarifaSoci = t
      }
      
      // Fallback absolut si no trobem la tarifa general
      var idFallback = tarifaGeneral ? tarifaGeneral.id : (self.tarifes.length > 0 ? self.tarifes[0].id : 1)

      self.passatgers = []
      
      for (var i = 0; i < self.compraStore.seientsSeleccionats.length; i++) {
        var seient = self.compraStore.seientsSeleccionats[i]
        var esFirstClass = seient.fila <= 3
        
        var dadesPassatger = { nom: '' }

        if (self.authStore.esPremium) {
          // Lògica Premium: usem 'soci' com a fallback per a tarifaId vàlid
          if (esFirstClass) {
            dadesPassatger.tarifaId = idFallback // Evitem 999 fake
            dadesPassatger.preu = 120.00
            dadesPassatger.nomTarifa = 'First Class'
          } else {
            // Descompte Premium per seient normal (podem usar id de soci)
            dadesPassatger.tarifaId = tarifaSoci ? tarifaSoci.id : idFallback
            dadesPassatger.preu = 39.99 
            dadesPassatger.nomTarifa = 'Premium Discount'
          }
        } else {
          // Lògica Normal
          dadesPassatger.tarifaId = idFallback
          dadesPassatger.preu = tarifaGeneral ? tarifaGeneral.preu : 49.99
          dadesPassatger.nomTarifa = 'general'
        }

        self.passatgers.push(dadesPassatger)
      }
    }
  },
  mounted: function () {
    var self = this
    self.authStore.inicialitzarClient()
    if (self.compraStore.seientsSeleccionats.length === 0) {
      self.$router.push('/vol/' + self.volId + '/seients')
      return
    }
    // Carregar tarifes i inicialitzar passatgers
    self.volsStore.carregarTarifes().then(function () {
      self.inicialitzarPassatgers()
    }).catch(function () {
      self.inicialitzarPassatgers()
    })
  },
  watch: {
    'authStore.usuari': {
      handler: function (nouUsuari) {
        if (nouUsuari && !this.email) {
          this.email = nouUsuari.email || ''
        }
      },
      immediate: true
    }
  }
}
</script>

<template>
  <div class="min-h-screen flex flex-col">
    <!-- Header -->
    <header class="border-b border-primary/10 bg-white/5 backdrop-blur-md sticky top-0 z-50">
      <div class="max-w-4xl mx-auto px-4 h-16 flex items-center justify-between">
        <div class="flex items-center gap-4">
          <button class="text-slate-400 hover:text-primary transition-colors" @click="tornarSeients">
            <span class="material-icons">arrow_back</span>
          </button>
          <div>
            <h1 class="text-lg font-bold leading-none">Confirmar Reserva</h1>
            <p class="text-xs text-slate-400 mt-1 uppercase tracking-widest font-semibold" v-if="volsStore.volActual">BCN → {{ volsStore.volActual.destiIata }}</p>
          </div>
        </div>
        <CountdownTimer v-if="cuaStore.ticketExpiraAt"
                        :dataObjectiu="cuaStore.ticketExpiraAt"
                        @expirat="sessioExpirada" />
      </div>
    </header>

    <main class="flex-1 max-w-4xl mx-auto w-full p-6 grid grid-cols-1 lg:grid-cols-5 gap-8">
      <!-- Formulari -->
      <div class="lg:col-span-3 space-y-6">
        <!-- Passatgers i seients -->
        <div class="bg-white/5 rounded-xl p-6 border border-white/5">
          <h2 class="text-lg font-bold mb-4 flex items-center gap-2">
            <span class="material-icons text-primary">airline_seat_recline_normal</span>
            Passatgers i Seients
          </h2>
          <div class="space-y-4">
            <div v-for="(seient, idx) in compraStore.seientsSeleccionats" :key="idx"
                 class="p-4 bg-slate-800/50 rounded-lg border border-slate-700/50">
              <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-3">
                  <div class="w-10 h-10 rounded bg-green-500 flex items-center justify-center font-bold text-sm">
                    {{ seient.fila }}{{ obtenirLletraColumna(seient.columna - 1) }}
                  </div>
                  <span class="font-semibold">Seient {{ seient.fila }}{{ obtenirLletraColumna(seient.columna - 1) }}</span>
                </div>
                <span v-if="passatgers[idx]" class="font-bold text-primary">€{{ parseFloat(passatgers[idx].preu).toFixed(2) }}</span>
              </div>
              <!-- Nom del passatger -->
              <div class="mb-3">
                <label class="block text-[10px] font-bold uppercase tracking-widest text-slate-500 mb-1">Nom del passatger</label>
                <input v-if="passatgers[idx]" v-model="passatgers[idx].nom" type="text" :placeholder="'Passatger ' + (idx + 1)"
                       class="w-full bg-slate-900/50 border border-slate-700 rounded-lg py-2 px-3 text-sm text-white placeholder-slate-600 focus:outline-none focus:ring-1 focus:ring-primary" />
              </div>
              <!-- Selecció de tarifa -->
              <div v-if="!authStore.esPremium">
                <label class="block text-[10px] font-bold uppercase tracking-widest text-slate-500 mb-1">Tarifa</label>
                <div class="grid grid-cols-2 gap-2">
                  <button v-for="tarifa in tarifes.filter(t => t.nom === 'general' || t.nom === 'nen')" :key="tarifa.id"
                          class="py-2 px-3 rounded-lg text-xs font-bold transition-colors border"
                          :class="passatgers[idx] && passatgers[idx].tarifaId === tarifa.id ? 'bg-primary text-white border-primary' : 'bg-slate-900/50 text-slate-400 border-slate-700 hover:border-primary/40'"
                          @click="canviarTarifa(idx, tarifa.id)">
                    <div>{{ tarifa.nom === 'general' ? 'Adult' : 'Infant' }}</div>
                    <div class="text-[10px] mt-0.5 opacity-80">€{{ parseFloat(tarifa.preu).toFixed(2) }}</div>
                  </button>
                </div>
              </div>
              
              <!-- Badge tarifa Premium -->
              <div v-else-if="passatgers[idx]">
                 <div class="inline-flex items-center gap-1 mt-2 px-2 py-1 rounded bg-amber-500/10 border border-amber-500/20 text-amber-500 text-[10px] font-bold uppercase tracking-widest">
                   <span class="material-icons text-[12px]">{{ passatgers[idx].nomTarifa === 'First Class' ? 'star' : 'local_offer' }}</span>
                   Tarifa {{ passatgers[idx].nomTarifa }} Aplicada
                 </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Email -->
        <div class="bg-white/5 rounded-xl p-6 border border-white/5">
          <h2 class="text-lg font-bold mb-4 flex items-center gap-2">
            <span class="material-icons text-primary">mail_outline</span>
            Dades de Contacte
          </h2>
          <div>
            <label class="block text-xs font-bold uppercase tracking-widest text-slate-400 mb-2">
              {{ authStore.estaAutenticat ? 'Email del teu compte' : 'Correu electrònic per a la confirmació' }}
            </label>
            <div class="relative">
              <span v-if="authStore.estaAutenticat" class="material-icons absolute left-3 top-1/2 -translate-y-1/2 text-primary text-lg">lock</span>
              <input v-model="email" type="email"
                     :placeholder="authStore.estaAutenticat ? '' : 'nom@correu.com'"
                     :readonly="authStore.estaAutenticat"
                     :class="authStore.estaAutenticat
                       ? 'w-full bg-primary/5 border border-primary/30 rounded-lg py-3 pl-10 pr-4 text-primary font-semibold cursor-not-allowed focus:outline-none'
                       : 'w-full bg-slate-900/50 border border-primary/20 rounded-lg py-3 px-4 text-white placeholder-slate-600 focus:outline-none focus:ring-2 focus:ring-primary'" />
            </div>
          </div>
          <div class="mt-4 flex items-start gap-3">
            <input v-model="acceptaCondicions" type="checkbox" id="condicions"
                   class="mt-1.5 w-4 h-4 accent-primary rounded" />
            <label for="condicions" class="text-sm text-slate-400">
              Accepto les condicions de compra i política de cancel·lació. Entenc que els bitllets last-minute no són reemborsables.
            </label>
          </div>
        </div>

        <!-- Error -->
        <div v-if="errorMissatge" class="p-4 bg-red-500/10 border border-red-500/20 rounded-lg text-red-400 text-sm">
          {{ errorMissatge }}
        </div>
      </div>

      <!-- Resum lateral -->
      <div class="lg:col-span-2">
        <div class="bg-white/5 rounded-xl p-6 border border-white/5 sticky top-24">
          <h2 class="text-lg font-bold mb-6">Resum de Compra</h2>
          <div class="space-y-3 mb-6">
            <div v-for="(p, idx) in passatgers" :key="'r'+idx" class="flex justify-between text-sm">
              <span class="text-slate-400">{{ p.nom || 'Passatger ' + (idx + 1) }} ({{ p.nomTarifa }})</span>
              <span>€{{ parseFloat(p.preu).toFixed(2) }}</span>
            </div>
            <div class="flex justify-between text-sm">
              <span class="text-slate-400">Taxes i recàrrecs</span>
              <span>€0.00</span>
            </div>
          </div>
          <div class="border-t border-white/10 pt-4 mb-6">
            <div class="flex justify-between text-xl font-bold">
              <span>Total</span>
              <span class="text-primary">€{{ totalCalculat.toFixed(2) }}</span>
            </div>
          </div>
          <button class="w-full bg-primary py-4 rounded-xl font-bold text-lg hover:bg-primary/90 transition-all active:scale-[0.98] shadow-lg shadow-primary/20 flex items-center justify-center gap-2"
                  :disabled="processant || !acceptaCondicions"
                  :class="(processant || !acceptaCondicions) ? 'opacity-50 cursor-not-allowed' : ''"
                  @click="confirmar">
            <span v-if="processant" class="material-icons animate-spin text-xl">sync</span>
            {{ processant ? 'Processant...' : 'Confirmar i Pagar' }}
          </button>
          <div class="flex items-center justify-center gap-2 mt-4 text-[10px] text-slate-500 uppercase">
            <span class="material-icons text-[12px]">security</span>
            Pagament segur TLS 256-bit
          </div>
        </div>
      </div>
    </main>
  </div>
</template>
