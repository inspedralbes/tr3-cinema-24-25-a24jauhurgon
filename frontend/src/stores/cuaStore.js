// Store de cua — Gestiona entrar, consultar posició i sortir de la cua
import { defineStore } from 'pinia'
import api from '../services/apiService.js'
import socketService from '../services/socketService.js'

export var useCuaStore = defineStore('cua', {
    state: function () {
        return {
            estat: null, // 'esperant', 'autoritzat', null
            posicio: 0,
            ticket: null,
            ticketExpiraAt: null,
            volId: null,
            carregant: false,
            error: null,
            intervalId: null
        }
    },

    getters: {
        estaAutoritzat: function (state) {
            return state.estat === 'autoritzat'
        },
        estaEsperant: function (state) {
            return state.estat === 'esperant'
        }
    },

    actions: {
        // Entrar a la cua d'un vol
        entrarCua: function (volId, clientId) {
            var self = this
            self.carregant = true
            self.error = null
            self.volId = volId

            return api.post('/cua/' + volId + '/entrar', {
                clientId: clientId
            }).then(function (response) {
                var dades = response.data
                self.estat = dades.cua.estat
                self.ticket = dades.cua.ticket
                self.ticketExpiraAt = dades.cua.ticketExpiraAt
                self.carregant = false
                return dades
            }).catch(function (error) {
                self.carregant = false
                if (error.response && error.response.status === 409) {
                    // Ja està a la cua
                    self.error = 'Ja estàs en una cua activa'
                } else {
                    self.error = 'Error entrant a la cua'
                }
                throw error
            })
        },

        // Consultar posició a la cua
        consultarPosicio: function (volId, clientId) {
            var self = this

            return api.get('/cua/' + volId + '/posicio', {
                params: { clientId: clientId }
            }).then(function (response) {
                var dades = response.data
                self.estat = dades.estat
                self.posicio = dades.posicio
                self.ticket = dades.ticket
                self.ticketExpiraAt = dades.ticketExpiraAt
                return dades
            }).catch(function (error) {
                if (error.response && error.response.status === 404) {
                    self.estat = null
                    self.posicio = 0
                }
                throw error
            })
        },

        // Iniciar polling de la posició i escolta de Sockets per a immediatesa
        iniciarPolling: function (volId, clientId) {
            var self = this
            self.aturarPolling()

            // 1. WebSocket per a immediatesa
            socketService.onUsuariAutoritzat(function (data) {
                // data = { volId, clientId }
                if (data.volId == volId && data.clientId == clientId) {
                    self.consultarPosicio(volId, clientId) // Forçar refresh final
                }
            })

            socketService.onCuaCanvi(function () {
                self.consultarPosicio(volId, clientId)
            })

            // 2. Polling de fallback (cada 5 segons, menys agressiu ja que tenim sockets)
            self.intervalId = setInterval(function () {
                self.consultarPosicio(volId, clientId).catch(function () {
                    // Silenciar errors de polling
                })
            }, 5000)
        },

        // Aturar polling
        aturarPolling: function () {
            if (this.intervalId) {
                clearInterval(this.intervalId)
                this.intervalId = null
            }
        },

        // Sortir de la cua
        sortirCua: function (volId, clientId) {
            var self = this
            self.aturarPolling()

            return api.post('/cua/' + volId + '/sortir', {
                clientId: clientId
            }).then(function () {
                self.estat = null
                self.posicio = 0
                self.ticket = null
                self.ticketExpiraAt = null
                self.volId = null
            }).catch(function (error) {
                throw error
            })
        },

        // Netejar estat de la cua
        netejar: function () {
            this.aturarPolling()
            this.estat = null
            this.posicio = 0
            this.ticket = null
            this.ticketExpiraAt = null
            this.volId = null
            this.error = null
        }
    }
})
