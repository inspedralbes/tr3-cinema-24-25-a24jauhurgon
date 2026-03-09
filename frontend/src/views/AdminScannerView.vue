<script>
// Vista de Scanner QR per a mòbils del personal de terra (Fase 13)
import { QrcodeStream } from 'vue-qrcode-reader'
import api from '../services/apiService.js'
import { useAuthStore } from '../stores/authStore.js'

export default {
  name: 'AdminScannerView',
  components: { QrcodeStream },
  data: function () {
    return {
      estat: 'idle', // idle, carregant, ok, error
      missatgeEstat: 'Apuntant la càmera...',
      codiManual: '',
      detallsBitllet: null,
      temporitzadorReset: null,
      cameraError: null // Per guardar els errors de permisos/càmera
    }
  },
  computed: {
    authStore() {
      return useAuthStore()
    }
  },
  methods: {
    onError: function (error) {
      if (error.name === 'NotAllowedError') {
        this.cameraError = "ERROR: L'accés a la càmera ha estat denegat."
      } else if (error.name === 'NotFoundError') {
        this.cameraError = "ERROR: No s'ha trobat cap càmera al dispositiu."
      } else if (error.name === 'NotSupportedError') {
        this.cameraError = "ERROR: Context segur (HTTPS) necessari per a la càmera."
      } else if (error.name === 'NotReadableError') {
        this.cameraError = "ERROR: La càmera ja està en ús per una altra app."
      } else if (error.name === 'OverconstrainedError') {
        this.cameraError = "ERROR: Càmera no disponible amb els requisits demanats."
      } else if (error.name === 'StreamApiNotSupportedError') {
        this.cameraError = "ERROR: El teu navegador no suporta l'API de càmera."
      } else {
        this.cameraError = "ERROR: " + error.message
      }
    },

    onDetect: function (detectedCodes) {
      if (!detectedCodes || detectedCodes.length === 0) return
      
      var codiEscanejat = detectedCodes[0].rawValue;
      if (this.estat === 'idle') {
        this.processarCodi(codiEscanejat)
      }
    },
    
    processarManual: function () {
      if (this.codiManual.trim() !== '') {
        this.processarCodi(this.codiManual.trim())
      }
    },

    processarCodi: function (codi) {
      var self = this
      self.estat = 'carregant'
      self.missatgeEstat = 'Verificant...'
      self.detallsBitllet = null
      
      if (self.temporitzadorReset) clearTimeout(self.temporitzadorReset)

      api.post('/admin/checkin', { qr_code: codi })
        .then(function (response) {
          self.estat = 'ok'
          self.missatgeEstat = 'EMBARCAMENT CORRECTE'
          self.detallsBitllet = response.data.bitllet
          self.reproduirSo('ok')
          self.programarReset()
          self.codiManual = '' // Netejar manual si ok
        })
        .catch(function (error) {
          self.estat = 'error'
          self.missatgeEstat = error.response && error.response.data.missatge 
                              ? error.response.data.missatge.toUpperCase() 
                              : 'ERROR FATAL: ' + error.message
          self.reproduirSo('error')
          self.programarReset(5000) // Error es mostra més estona per llegir
        })
    },

    reproduirSo: function (tipus) {
      try {
        var context = new (window.AudioContext || window.webkitAudioContext)();
        var oscillator = context.createOscillator();
        var gainNode = context.createGain();
        
        oscillator.connect(gainNode);
        gainNode.connect(context.destination);
        
        if (tipus === 'ok') {
          oscillator.type = 'sine';
          oscillator.frequency.value = 880; // Nota A5, aguda i agradable
          gainNode.gain.setValueAtTime(0.1, context.currentTime);
          oscillator.start(context.currentTime);
          oscillator.stop(context.currentTime + 0.15); // Bip curt (150ms)
        } else {
          oscillator.type = 'sawtooth';
          oscillator.frequency.value = 150; // Nota greu
          gainNode.gain.setValueAtTime(0.2, context.currentTime);
          oscillator.start(context.currentTime);
          oscillator.stop(context.currentTime + 0.6); // Bip llarg (600ms)
        }
      } catch (e) {
        console.warn("No s'ha pogut reproduir el so: ", e);
      }
    },

    programarReset: function (ms = 2500) {
      var self = this
      if (self.temporitzadorReset) clearTimeout(self.temporitzadorReset)
      self.temporitzadorReset = setTimeout(function () {
        self.estat = 'idle'
        self.missatgeEstat = 'Apuntant la càmera...'
        self.detallsBitllet = null
      }, ms)
    },
    
    tornar: function () {
      this.$router.push('/admin/dashboard')
    }
  },
  mounted: function () {
    // La protecció de rutes ja la fa router/index.js (requiresAdmin: true)
  },
  beforeUnmount: function() {
    if (this.temporitzadorReset) clearTimeout(this.temporitzadorReset)
  }
}
</script>

<template>
  <div class="h-screen w-full flex flex-col overflow-hidden bg-black relative">
    
    <!-- Top Bar -->
    <header class="p-4 flex items-center justify-between bg-black/80 backdrop-blur z-50 shadow-md">
      <div class="flex items-center gap-3">
        <button @click="tornar" class="w-10 h-10 rounded-full bg-slate-800 flex items-center justify-center text-white">
          <span class="material-icons">arrow_back</span>
        </button>
        <div>
          <h1 class="font-bold text-white text-lg leading-tight uppercase tracking-wider">Gate Scanner</h1>
          <p class="text-[10px] text-green-400 font-mono tracking-widest"><span class="w-2 h-2 rounded-full bg-green-500 inline-block mr-1 animate-pulse"></span> Sistema Actiu</p>
        </div>
      </div>
    </header>

    <!-- Scanner Area -->
    <div class="flex-1 relative bg-slate-900 border-y border-slate-700 flex flex-col justify-center overflow-hidden">
      
      <!-- Error Càmera Visor -->
      <div v-if="cameraError" class="absolute inset-0 flex flex-col items-center justify-center p-6 text-center z-20 bg-slate-900">
        <span class="material-icons text-red-500 text-6xl mb-4">no_photography</span>
        <h2 class="text-xl font-bold text-white mb-2">Càmera No Disponible</h2>
        <p class="text-slate-400 mb-6">{{ cameraError }}</p>
        <div class="bg-yellow-500/10 border border-yellow-500/30 text-yellow-500 p-4 rounded-lg text-sm max-w-sm">
           Pots donar permisos a la càmera i refrescar la pàgina, o fer servir la introducció manual de sota.
        </div>
      </div>

      <QrcodeStream v-if="(estat === 'idle' || estat === 'carregant') && !cameraError" @detect="onDetect" @error="onError" class="w-full h-full object-cover">
         <div class="absolute inset-0 border-2 border-primary/50 m-12 rounded-2xl shadow-[0_0_0_9999px_rgba(0,0,0,0.5)] transition-all">
           <!-- Marc apuntador -->
           <div class="absolute -top-1 -left-1 w-8 h-8 border-t-4 border-l-4 border-primary rounded-tl-xl transition-all"></div>
           <div class="absolute -top-1 -right-1 w-8 h-8 border-t-4 border-r-4 border-primary rounded-tr-xl transition-all"></div>
           <div class="absolute -bottom-1 -left-1 w-8 h-8 border-b-4 border-l-4 border-primary rounded-bl-xl transition-all"></div>
           <div class="absolute -bottom-1 -right-1 w-8 h-8 border-b-4 border-r-4 border-primary rounded-br-xl transition-all"></div>
           
           <div class="absolute -bottom-8 left-0 right-0 text-center text-white font-mono text-xs tracking-widest uppercase opacity-70">Enfoqui el bitllet</div>
         </div>
         
         <div v-if="estat === 'carregant'" class="absolute inset-0 bg-black/80 flex flex-col items-center justify-center z-10 backdrop-blur-sm">
           <span class="material-icons text-primary animate-spin text-5xl mb-4">refresh</span>
           <span class="text-white font-bold tracking-widest uppercase">Processant Dades</span>
         </div>
      </QrcodeStream>
    </div>

    <!-- Manual input (Bottom Bar) -->
    <div class="p-4 bg-slate-900 border-t border-slate-800 z-50 shadow-[0_-5px_15px_rgba(0,0,0,0.3)]" v-if="estat === 'idle' || estat === 'carregant'">
        <label class="text-[10px] uppercase tracking-widest text-slate-400 font-bold mb-2 block">Introducció Manual PNR</label>
        <div class="flex gap-2">
            <input v-model="codiManual" type="text" placeholder="last24bcn-R..."
                   class="flex-1 bg-black border border-slate-700 rounded-lg px-4 py-3 text-white font-mono placeholder-slate-600 outline-none focus:border-primary" 
                   @keyup.enter="processarManual"/>
            <button @click="processarManual" class="bg-primary hover:bg-primary/80 text-white font-bold px-6 py-3 rounded-lg flex items-center justify-center transition-colors">
                <span class="material-icons">send</span>
            </button>
        </div>
    </div>

    <!-- Fullscreen Overlays (Success / Error) -->
    <div v-if="estat === 'ok'" class="absolute inset-0 bg-green-500 z-[100] flex flex-col items-center justify-center text-white px-6 text-center shadow-[inset_0_0_100px_rgba(0,0,0,0.5)]">
        <span class="material-icons text-8xl mb-6 drop-shadow-lg">check_circle</span>
        <h2 class="text-3xl font-black mb-2 drop-shadow tracking-tight">{{ missatgeEstat }}</h2>
        <div v-if="detallsBitllet" class="bg-black/20 p-6 rounded-2xl w-full max-w-sm mt-4 border border-white/20 backdrop-blur">
             <div class="flex justify-between items-end border-b border-white/20 pb-4 mb-4">
                 <div class="text-left">
                     <p class="text-xs uppercase tracking-widest opacity-70 font-bold">Passatger</p>
                     <p class="text-xl font-bold truncate">{{ detallsBitllet.nomPassatger }}</p>
                 </div>
                 <div class="text-right">
                     <p class="text-xs uppercase tracking-widest opacity-70 font-bold">Seient</p>
                     <p class="text-3xl font-black">{{ detallsBitllet.fila }}{{ String.fromCharCode(64 + detallsBitllet.columna) }}</p>
                 </div>
             </div>
             <div class="text-[10px] uppercase font-mono tracking-widest opacity-60">Vol ID: {{ detallsBitllet.volId }} | Tarifa: {{ detallsBitllet.tipus }}</div>
        </div>
        <p class="mt-8 opacity-50 text-sm animate-pulse tracking-widest uppercase">Continuant en breu...</p>
    </div>

    <div v-if="estat === 'error'" class="absolute inset-0 bg-red-600 z-[100] flex flex-col items-center justify-center text-white px-6 text-center shadow-[inset_0_0_100px_rgba(0,0,0,0.5)]">
        <span class="material-icons text-8xl mb-6 drop-shadow-lg">cancel</span>
        <h2 class="text-3xl font-black mb-4 drop-shadow tracking-tight leading-tight">DENEGAT</h2>
        <p class="text-lg font-bold bg-black/30 p-4 rounded-xl border border-white/10 w-full max-w-sm">{{ missatgeEstat }}</p>
        <button @click="programarReset(0)" class="mt-10 px-8 py-3 bg-white text-red-600 font-bold uppercase tracking-widest rounded-full hover:bg-red-50 transition-colors">Continuar</button>
    </div>

  </div>
</template>
