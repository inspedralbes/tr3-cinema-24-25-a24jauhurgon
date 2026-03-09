<script>
// Vista Compra Completada — Confirmació final (A9)
export default {
  name: 'CompraCompletadaView',
  data: function () {
    return {
      compraId: null
    }
  },
  methods: {
    tornarVols: function () {
      this.$router.push('/vols')
    },
    descarregarPdf: function () {
      window.open('http://localhost:8000/api/compra/' + this.compraId + '/pdf', '_blank')
    }
  },
  mounted: function () {
    this.compraId = this.$route.params.id
  }
}
</script>

<template>
  <div class="min-h-screen flex items-center justify-center relative overflow-hidden">
    <!-- Fons -->
    <div class="absolute inset-0 bg-gradient-to-br from-green-900/20 via-[var(--color-background-dark)] to-primary/10"></div>

    <main class="relative z-10 max-w-lg w-full px-6 text-center">
      <!-- Icona d'èxit -->
      <div class="mb-8">
        <div class="w-24 h-24 mx-auto rounded-full bg-green-500/20 border-4 border-green-500 flex items-center justify-center mb-6">
          <span class="material-icons text-green-500 text-5xl">check</span>
        </div>
        <h1 class="text-3xl md:text-4xl font-extrabold mb-3 tracking-tight">Reserva Confirmada!</h1>
        <p class="text-slate-400 text-lg">La teva reserva ha estat processada amb èxit.</p>
      </div>

      <!-- Detalls -->
      <div class="bg-white/5 rounded-xl p-8 border border-white/5 space-y-6 mb-8">
        <div class="flex items-center justify-between pb-4 border-b border-white/10">
          <span class="text-sm text-slate-400">Codi de Reserva</span>
          <span class="font-mono font-bold text-primary text-lg tracking-wider">#{{ compraId }}</span>
        </div>
        <div class="flex items-center gap-4 p-4 bg-primary/5 border border-primary/20 rounded-lg">
          <span class="material-icons text-primary text-2xl">mail_outline</span>
          <div class="text-left">
            <p class="text-sm font-bold">Confirmació enviada</p>
            <p class="text-xs text-slate-400">Revisa la teva safata d'entrada per trobar els bitllets electrònics.</p>
          </div>
        </div>
        <div class="flex items-center gap-4 p-4 bg-amber-500/5 border border-amber-500/20 rounded-lg">
          <span class="material-icons text-amber-500 text-2xl">schedule</span>
          <div class="text-left">
            <p class="text-sm font-bold">Arriba amb temps</p>
            <p class="text-xs text-slate-400">Recomanem arribar 2 hores abans de la sortida per a vols internacionals.</p>
          </div>
        </div>
      </div>

      <!-- Accions -->
      <div class="flex flex-col gap-3">
        <button class="w-full bg-primary py-4 rounded-xl font-bold text-lg hover:bg-primary/90 transition-all shadow-lg shadow-primary/20 flex items-center justify-center gap-2"
                @click="tornarVols">
          <span class="material-icons">flight</span>
          Reservar un altre vol
        </button>
        <button class="w-full border border-white/10 hover:bg-white/5 py-3 rounded-xl font-semibold text-slate-300 transition-all flex items-center justify-center gap-2"
                @click="descarregarPdf">
          <span class="material-icons">picture_as_pdf</span>
          Descarregar Bitllet (PDF)
        </button>
      </div>

      <!-- Footer -->
      <p class="mt-8 text-[10px] text-slate-600 uppercase tracking-widest">BCN<span class="text-primary">JET</span> • Gràcies per volar amb nosaltres</p>
    </main>
  </div>
</template>
