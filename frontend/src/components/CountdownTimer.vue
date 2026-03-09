<script>
// Component CountdownTimer — Temporitzador de compte enrere
export default {
  name: 'CountdownTimer',
  props: {
    dataObjectiu: { type: String, default: null },
    segonsInicial: { type: Number, default: 120 }
  },
  emits: ['expirat'],
  data: function () {
    return {
      segonsRestants: 0,
      intervalId: null
    }
  },
  computed: {
    tempsFormatat: function () {
      var minuts = Math.floor(this.segonsRestants / 60)
      var segons = this.segonsRestants % 60
      var minutsStr = minuts < 10 ? '0' + minuts : '' + minuts
      var segonsStr = segons < 10 ? '0' + segons : '' + segons
      return minutsStr + ':' + segonsStr
    },
    esUrgent: function () {
      return this.segonsRestants <= 30
    }
  },
  mounted: function () {
    this.iniciar()
  },
  beforeUnmount: function () {
    this.aturar()
  },
  methods: {
    iniciar: function () {
      var self = this
      if (self.dataObjectiu) {
        self.calcularDesdeData()
      } else {
        self.segonsRestants = self.segonsInicial
      }

      self.intervalId = setInterval(function () {
        if (self.dataObjectiu) {
          self.calcularDesdeData()
        } else {
          self.segonsRestants = self.segonsRestants - 1
        }

        if (self.segonsRestants <= 0) {
          self.segonsRestants = 0
          self.aturar()
          self.$emit('expirat')
        }
      }, 1000)
    },
    calcularDesdeData: function () {
      var ara = new Date()
      var objectiu = new Date(this.dataObjectiu)
      var diff = Math.floor((objectiu.getTime() - ara.getTime()) / 1000)
      this.segonsRestants = diff > 0 ? diff : 0
    },
    aturar: function () {
      if (this.intervalId) {
        clearInterval(this.intervalId)
        this.intervalId = null
      }
    }
  }
}
</script>

<template>
  <div class="flex items-center gap-3 px-4 py-2 rounded-xl"
       :class="esUrgent ? 'bg-red-500/10 border border-red-500/20' : 'bg-primary/10 border border-primary/20'">
    <span class="material-icons text-sm" :class="esUrgent ? 'text-red-500' : 'text-primary'">timer</span>
    <div class="flex flex-col items-center">
      <span class="text-xl font-mono font-extrabold leading-none"
            :class="esUrgent ? 'text-red-500' : 'text-primary'">{{ tempsFormatat }}</span>
      <span class="text-[10px] uppercase font-bold"
            :class="esUrgent ? 'text-red-400/80' : 'text-primary/80'">Temps restant</span>
    </div>
  </div>
</template>
