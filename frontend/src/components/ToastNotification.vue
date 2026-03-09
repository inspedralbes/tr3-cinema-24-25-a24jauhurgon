<script>
// Component ToastNotification — Sistema de notificacions global
export default {
  name: 'ToastNotification',
  data: function () {
    return {
      missatges: [],
      comptador: 0
    }
  },
  methods: {
    afegir: function (text, tipus) {
      var self = this
      var id = self.comptador++
      self.missatges.push({ id: id, text: text, tipus: tipus || 'error' })
      // Eliminar automàticament després de 5 segons
      setTimeout(function () {
        self.eliminar(id)
      }, 5000)
    },
    eliminar: function (id) {
      for (var i = 0; i < this.missatges.length; i++) {
        if (this.missatges[i].id === id) {
          this.missatges.splice(i, 1)
          return
        }
      }
    },
    classeToast: function (tipus) {
      if (tipus === 'exit') return 'bg-green-500/90 border-green-400/50'
      if (tipus === 'avís') return 'bg-amber-500/90 border-amber-400/50'
      return 'bg-red-500/90 border-red-400/50'
    },
    iconaToast: function (tipus) {
      if (tipus === 'exit') return 'check_circle'
      if (tipus === 'avís') return 'warning'
      return 'error'
    }
  }
}
</script>

<template>
  <div class="fixed top-20 right-4 z-[999] flex flex-col gap-3 max-w-sm">
    <transition-group name="toast">
      <div v-for="msg in missatges" :key="msg.id"
           class="flex items-center gap-3 px-4 py-3 rounded-lg border backdrop-blur-md shadow-xl text-white text-sm font-medium animate-slide-toast"
           :class="classeToast(msg.tipus)">
        <span class="material-icons text-lg">{{ iconaToast(msg.tipus) }}</span>
        <span class="flex-1">{{ msg.text }}</span>
        <button class="opacity-60 hover:opacity-100 transition-opacity" @click="eliminar(msg.id)">
          <span class="material-icons text-sm">close</span>
        </button>
      </div>
    </transition-group>
  </div>
</template>
