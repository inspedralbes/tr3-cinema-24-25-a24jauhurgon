<script>
// Component VolCard — Targeta de vol per al llistat A2
export default {
  name: 'VolCard',
  props: {
    vol: { type: Object, required: true },
    destacat: { type: Boolean, default: false }
  },
  emits: ['seleccionar'],
  data: function () {
    return {
      tempsRestant: '',
      minutsArribada: null,
      esUrgent: false,
      intervalId: null
    }
  },
  computed: {
    classesBorde: function () {
      if (!this.vol.disponiblePerCompra) return 'border-slate-800/50 opacity-60'
      if (this.esUrgent) return 'border-primary/50 bg-primary/5 ring-1 ring-primary/20'
      return 'border-slate-800 hover:border-slate-700'
    },
    colorTemps: function () {
      if (this.esUrgent) return 'text-red-500'
      return 'text-slate-200'
    },
    estatText: function () {
      var estat = this.vol.estat
      if (estat === 'embarquement') return 'BOARDING'
      if (estat === 'retardat') return 'DELAYED'
      return 'SCHEDULED'
    },
    estatColor: function () {
      if (this.vol.estat === 'embarquement') return 'text-green-400'
      if (this.vol.estat === 'retardat') return 'text-amber-500'
      return 'text-slate-400'
    },
    estatIcon: function () {
      if (this.vol.estat === 'embarquement') return 'bolt'
      if (this.vol.estat === 'retardat') return 'warning'
      return 'schedule'
    }
  },
  methods: {
    seleccionar: function () {
      if (!this.vol.disponiblePerCompra) return
      this.$emit('seleccionar', this.vol)
    },
    actualitzarTemps: function () {
      var ara = new Date()
      var sortida = new Date(this.vol.dataHoraSortida)
      var diffMs = sortida.getTime() - ara.getTime()

      if (diffMs <= 0) {
        this.tempsRestant = 'Sortit'
        this.esUrgent = false
        return
      }

      var totalSegons = Math.floor(diffMs / 1000)
      var hores = Math.floor(totalSegons / 3600)
      var minuts = Math.floor((totalSegons % 3600) / 60)
      var segons = totalSegons % 60

      if (hores > 0) {
        this.tempsRestant = hores + 'h ' + minuts + 'm ' + segons + 's'
      } else {
        this.tempsRestant = minuts + 'm ' + (segons < 10 ? '0' : '') + segons + 's'
      }

      // Urgent si queden menys de 2h (ara 90min és el cutoff, però "urgent" visual)
      this.esUrgent = diffMs < 120 * 60000

      // Calcular temps arribada vol entrant
      if (this.vol.hora_arribada_esperada) {
        var arribada = new Date(this.vol.hora_arribada_esperada)
        var diffArribadaMs = arribada.getTime() - ara.getTime()
        if (diffArribadaMs <= 0) {
          this.minutsArribada = 'Aterrat'
        } else {
          this.minutsArribada = Math.ceil(diffArribadaMs / 60000)
        }
      } else {
        this.minutsArribada = null
      }
    }
  },
  mounted: function () {
    var self = this
    self.actualitzarTemps()
    self.intervalId = setInterval(function () {
      self.actualitzarTemps()
    }, 1000)
  },
  beforeUnmount: function () {
    if (this.intervalId) {
      clearInterval(this.intervalId)
    }
  }
}
</script>

<template>
  <div class="flight-card-gradient rounded-xl p-5 flex flex-col transition-all group relative overflow-hidden border"
       :class="classesBorde">
    <!-- Badge urgent -->
    <div v-if="esUrgent && vol.disponiblePerCompra" class="absolute top-0 right-0 p-2">
      <span class="bg-primary/20 text-primary text-[10px] font-bold px-2 py-1 rounded-full border border-primary/30">LAST CALL</span>
    </div>
    <!-- Badge no disponible -->
    <div v-if="!vol.disponiblePerCompra" class="absolute top-0 right-0 p-2">
      <span class="bg-red-500/20 text-red-400 text-[10px] font-bold px-2 py-1 rounded-full border border-red-500/30">NO DISPONIBLE</span>
    </div>

    <!-- Info aerolinea -->
    <div class="flex justify-between items-start mb-4">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 bg-primary/20 rounded-lg flex items-center justify-center">
          <span class="material-icons text-primary">flight</span>
        </div>
        <div>
          <h2 class="font-bold text-lg leading-tight">{{ vol.destiIata }}</h2>
          <p class="text-xs text-slate-500 font-medium">{{ vol.externalId || 'BCN-' + vol.id }} • {{ vol.modelAvio || 'Avió' }}</p>
        </div>
      </div>
    </div>

    <!-- Countdown + Estat -->
    <div class="flex items-center justify-between mb-6">
      <div class="text-center">
        <p class="text-2xl font-black" :class="colorTemps">{{ tempsRestant }}</p>
        <p class="text-[10px] uppercase font-bold text-slate-500 text-left">Sortida</p>
      </div>
      <div class="h-10 w-[1px] bg-slate-800"></div>
      <div class="text-right">
        <div class="flex items-center justify-end gap-1" :class="estatColor">
          <span class="material-icons text-sm">{{ estatIcon }}</span>
          <span class="text-sm font-bold uppercase">{{ estatText }}</span>
        </div>
        <p class="text-[10px] uppercase font-bold text-slate-500">Des de BCN</p>
      </div>
    </div>

    <!-- Tracker Aviò Entrant -->
    <div v-if="vol.vol_entrant_origen && vol.estat_venda !== 'finalitzat'" class="mb-4 bg-slate-800/80 rounded-lg p-2.5 text-center border" :class="vol.estat_venda === 'obert' ? 'border-primary/50 bg-primary/10 shadow-[0_0_10px_rgba(19,127,236,0.1)]' : 'border-slate-700'">
      <div v-if="vol.estat_venda === 'obert'" class="text-sm font-medium text-white">
        <div class="flex items-center justify-center gap-1.5 mb-1 text-primary">
          <span class="material-icons text-sm animate-bounce">flight_land</span>
          <span class="font-bold text-[10px] tracking-widest uppercase">Tracker En Viu</span>
        </div>
        L'avió arriba de <span class="font-bold">{{ vol.vol_entrant_origen }}</span> en 
        <span class="font-bold whitespace-nowrap" v-if="minutsArribada !== 'Aterrat'">{{ minutsArribada }} minuts</span>
        <span class="font-bold whitespace-nowrap" v-else>aquest precís instant</span>.
        <div class="text-xs text-slate-300 mt-1 italic animate-pulse">Temps perfecte per arribar a l'aeroport. Compra ara!</div>
      </div>
      <div v-else-if="vol.estat_venda === 'tancat'" class="text-sm font-medium text-slate-400">
        <span class="material-icons text-slate-500 align-middle text-sm mr-1">schedule</span>
        Avió a <span class="font-bold text-slate-300">{{ vol.vol_entrant_origen }}</span>. La venda obrirà durant el vol.
      </div>
    </div>

    <!-- Botó -->
    <div class="mt-auto flex gap-3">
      <button v-if="vol.disponiblePerCompra" class="flex-grow py-2.5 rounded-lg font-bold text-sm transition-all"
              :class="esUrgent ? 'bg-primary text-white hover:shadow-[0_0_20px_rgba(19,127,236,0.3)]' : 'bg-slate-800 hover:bg-slate-700 text-white'"
              @click="seleccionar">
        Reservar Seient
      </button>
      <div v-else class="flex-grow py-2.5 rounded-lg bg-slate-800/40 text-slate-500 text-center text-sm font-medium cursor-not-allowed">
        {{ vol.motiuNoDisponible || 'No disponible' }}
      </div>
      <button class="w-11 h-11 border border-slate-700 rounded-lg flex items-center justify-center hover:bg-slate-800 transition-colors">
        <span class="material-icons text-slate-400">notifications_none</span>
      </button>
    </div>
  </div>
</template>
