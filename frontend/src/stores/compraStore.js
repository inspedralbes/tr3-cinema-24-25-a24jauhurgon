// Store de compra — Gestiona seatmap, holds de seients i confirmació
// Integrat amb Socket.IO per actualitzacions en temps real
import { defineStore } from 'pinia'
import api from '../services/apiService.js'
import socketService from '../services/socketService.js'
import { useAuthStore } from './authStore.js'

export var useCompraStore = defineStore('compra', {
    state: function () {
        return {
            seatmap: [],
            modelAvio: null,
            files: 0,
            columnes: 0,
            seientsSeleccionats: [],
            email: '',
            total: 0,
            compraConfirmada: null,
            carregant: false,
            error: null,
            volIdActual: null,
            usuarisConnectats: 0
        }
    },

    getters: {
        nombreSeientsSeleccionats: function (state) {
            return state.seientsSeleccionats.length
        },
        totalCalculat: function (state) {
            var sum = 0
            for (var i = 0; i < state.seientsSeleccionats.length; i++) {
                sum = sum + parseFloat(state.seientsSeleccionats[i].preu || 0)
            }
            return sum
        }
    },

    actions: {
        // Carregar seatmap d'un vol i connectar al socket
        carregarSeatmap: function (volId) {
            var self = this
            self.carregant = true
            self.error = null
            self.volIdActual = volId

            return api.get('/compra/' + volId + '/seatmap').then(function (response) {
                var dades = response.data
                self.seatmap = dades.seatmap
                self.modelAvio = dades.modelAvio
                self.files = dades.files
                self.columnes = dades.columnes
                self.carregant = false

                // Connectar al socket i unir-se a la sala del vol
                self.iniciarSocket(volId)

                return dades
            }).catch(function (error) {
                self.carregant = false
                self.error = 'Error carregant seatmap'
                throw error
            })
        },

        // Iniciar connexió Socket.IO i escoltar events
        iniciarSocket: function (volId) {
            var self = this
            var authStore = useAuthStore()
            var clientId = authStore.obtenirClientId

            socketService.unirVol(volId, clientId)

            // Escoltar actualitzacions del seatmap d'altres usuaris
            socketService.onSeatmapActualitzat(function (data) {
                self.aplicarActualitzacioSocket(data)
            })

            socketService.onUsuariConnectat(function (data) {
                self.usuarisConnectats = data.total || 0
            })
        },

        // Aplicar una actualització rebuda per socket al seatmap local
        aplicarActualitzacioSocket: function (data) {
            // data = { tipus: 'bloquejat'|'alliberat'|'comprat', fila, columna, clientId? }
            if (!this.seatmap || this.seatmap.length === 0) return

            var filaIdx = data.fila - 1
            if (filaIdx < 0 || filaIdx >= this.seatmap.length) return

            var fila = this.seatmap[filaIdx]
            for (var i = 0; i < fila.length; i++) {
                if (fila[i].fila === data.fila && fila[i].columna === data.columna) {
                    if (data.tipus === 'bloquejat') {
                        fila[i].estat = 'bloquejat'
                    } else if (data.tipus === 'alliberat') {
                        fila[i].estat = 'lliure'
                    } else if (data.tipus === 'comprat') {
                        fila[i].estat = 'comprat'
                    }
                    break
                }
            }
        },

        // Aturar connexió socket
        aturarSocket: function () {
            if (this.volIdActual) {
                socketService.sortirVol(this.volIdActual)
            }
            socketService.netejarListeners()
        },

        // Bloquejar un seient (hold) amb Optimistic UI
        bloquejarSeient: function (volId, clientId, fila, columna, tipusTarifa, preuBase) {
            var self = this

            // 1. Optimistic UI: Marquem el seient com a bloquejat localment
            self.aplicarActualitzacioSocket({
                tipus: 'bloquejat',
                fila: fila,
                columna: columna,
                clientId: clientId
            })

            // 2. Optimistic UI: Afegim a la llista de seleccionats perquè el botó surti JA
            self.afegirSeientSeleccionat(fila, columna, tipusTarifa, preuBase)

            return api.post('/compra/' + volId + '/bloquejar', {
                clientId: clientId,
                fila: fila,
                columna: columna
            }).then(function (response) {
                // El backend ja ha validat i registrat el hold
                return response.data
            }).catch(function (error) {
                // 3. Revertim l'Optimistic UI si hi ha un error (ex: seient ocupat per un altre fa mseg)
                self.aplicarActualitzacioSocket({
                    tipus: 'alliberat',
                    fila: fila,
                    columna: columna
                })
                self.treureSeientSeleccionat(fila, columna)

                if (error.response && error.response.data) {
                    self.error = error.response.data.missatge
                }
                throw error
            })
        },

        // Alliberar un seient amb Optimistic UI
        alliberarSeient: function (volId, clientId, fila, columna) {
            var self = this

            // 1. Optimistic UI: Alliberem localment l'estat visual
            self.aplicarActualitzacioSocket({
                tipus: 'alliberat',
                fila: fila,
                columna: columna
            })

            // 2. Optimistic UI: Treiem de la llista de seleccionats immediatament
            self.treureSeientSeleccionat(fila, columna)

            return api.post('/compra/' + volId + '/alliberar', {
                clientId: clientId,
                fila: fila,
                columna: columna
            }).then(function (response) {
                return response.data
            }).catch(function (error) {
                // 3. Revertim si falla (tornem a marcar com bloquejat pel mateix usuari)
                self.aplicarActualitzacioSocket({
                    tipus: 'bloquejat',
                    fila: fila,
                    columna: columna,
                    clientId: clientId
                })
                // Hauríem de tornar-lo a afegir a seleccionats? 
                // Donat que treure de seleccionats és una acció deliberada de l'usuari, 
                // si falla el servidor el deixem 'fora' però el seient quedarà 'bloquejat' visualment.
                // El més segur és carregar el seatmap de nou o avisar.
                throw error
            })
        },

        // Afegir seient a la selecció local
        afegirSeientSeleccionat: function (fila, columna, tipus, preu) {
            this.seientsSeleccionats.push({
                fila: fila,
                columna: columna,
                tipus: tipus,
                preu: preu
            })
        },

        // Treure seient de la selecció local
        treureSeientSeleccionat: function (fila, columna) {
            var novaLlista = []
            for (var i = 0; i < this.seientsSeleccionats.length; i++) {
                var s = this.seientsSeleccionats[i]
                if (s.fila !== fila || s.columna !== columna) {
                    novaLlista.push(s)
                }
            }
            this.seientsSeleccionats = novaLlista
        },

        // Confirmar compra + emetre per socket
        confirmarCompra: function (volId, clientId, email, bitllets) {
            var self = this
            self.carregant = true
            self.error = null

            return api.post('/compra/' + volId + '/confirmar', {
                clientId: clientId,
                email: email,
                bitllets: bitllets
            }).then(function (response) {
                self.compraConfirmada = response.data.compra
                self.carregant = false

                // Ja no cal emitreComprat des d'aquí, el backend ho fa al mètode confirmar()

                // Netejar selecció i socket després de compra confirmada
                self.aturarSocket()
                self.seatmap = []
                self.seientsSeleccionats = []
                self.email = ''
                self.total = 0
                self.volIdActual = null
                self.usuarisConnectats = 0

                return response.data
            }).catch(function (error) {
                self.carregant = false
                if (error.response && error.response.data) {
                    self.error = error.response.data.missatge || 'Error confirmant compra'
                }
                throw error
            })
        },

        // Netejar estat de compra i desconnectar socket
        netejar: function () {
            this.aturarSocket()
            this.seatmap = []
            this.seientsSeleccionats = []
            this.email = ''
            this.total = 0
            this.compraConfirmada = null
            this.error = null
            this.volIdActual = null
            this.usuarisConnectats = 0
        }
    }
})
