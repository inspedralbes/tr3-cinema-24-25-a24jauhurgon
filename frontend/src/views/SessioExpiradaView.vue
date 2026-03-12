<script>
// Vista Sessió Expirada — Error de sessió (A10)
export default {
  name: 'SessioExpiradaView',
  computed: {
    volId() {
      return this.$route.query.volId || null;
    }
  },
  methods: {
    tornarTentar() {
      if (this.volId) {
        this.$router.push('/vol/' + this.volId + '/seatmap');
      } else {
        this.tornarVols();
      }
    },
    tornarVols() {
      this.$router.push('/vols')
    },
    tornarLogin() {
      this.$router.push('/')
    }
  }
}
</script>

<template>
  <div class="min-h-screen flex items-center justify-center relative overflow-hidden">
    <!-- Fons -->
    <div class="absolute inset-0 bg-gradient-to-br from-red-950 via-slate-950 to-red-950/50"></div>

    <main class="relative z-10 max-w-lg w-full px-6 text-center">
      <!-- Icona d'error -->
      <div class="mb-8">
        <div
          class="w-24 h-24 mx-auto rounded-full bg-red-500/20 border-4 border-red-500/40 flex items-center justify-center mb-6">
          <span class="material-icons text-red-500 text-5xl">timer_off</span>
        </div>
        <h1 class="text-3xl md:text-4xl font-extrabold mb-3 tracking-tight">Sessió Expirada</h1>
        <p class="text-slate-400 text-lg max-w-md mx-auto">
          El temps assignat per completar la reserva ha finalitzat. Els seients seleccionats s'han alliberat
          automàticament.
        </p>
      </div>

      <!-- Explicació -->
      <div class="bg-white/5 rounded-xl p-6 border border-white/5 space-y-4 mb-8 text-left">
        <h2 class="font-bold flex items-center gap-2">
          <span class="material-icons text-primary">info</span>
          Què ha passat?
        </h2>
        <ul class="space-y-3">
          <li class="flex items-start gap-3 text-sm text-slate-400">
            <span class="material-icons text-red-400 text-lg mt-0.5">schedule</span>
            El ticket de compra tenia una durada màxima i ha expirat.
          </li>
          <li class="flex items-start gap-3 text-sm text-slate-400">
            <span class="material-icons text-amber-500 text-lg mt-0.5">event_seat</span>
            Els seients que havies reservat temporalment ja estan disponibles per a altres passatgers.
          </li>
          <li class="flex items-start gap-3 text-sm text-slate-400">
            <span class="material-icons text-green-400 text-lg mt-0.5">refresh</span>
            Pots tornar a provar de comprar els bitllets si encara queden seients lliures.
          </li>
        </ul>
      </div>

      <!-- Accions -->
      <div class="flex flex-col gap-3">
        <button v-if="volId"
          class="w-full bg-primary py-4 rounded-xl font-bold text-lg hover:bg-primary/90 transition-all shadow-lg shadow-primary/20 flex items-center justify-center gap-2"
          @click="tornarTentar">
          <span class="material-icons">replay</span>
          Tornar a la compra
        </button>
        <button v-else
          class="w-full bg-primary py-4 rounded-xl font-bold text-lg hover:bg-primary/90 transition-all shadow-lg shadow-primary/20 flex items-center justify-center gap-2"
          @click="tornarVols">
          <span class="material-icons">flight_takeoff</span>
          Tornar al llistat de vols
        </button>
        <button
          class="w-full border border-white/10 hover:bg-white/5 py-3 rounded-xl font-semibold text-slate-300 transition-all"
          @click="tornarLogin">
          Tornar a l'inici
        </button>
      </div>
    </main>
  </div>
</template>
