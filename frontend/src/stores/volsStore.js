// Store de vols — Gestiona el llistat de vols i tarifes
import { defineStore } from 'pinia'
import api from '../services/apiService.js'

export var useVolsStore = defineStore('vols', {
    state: function () {
        return {
            vols: [],
            volActual: null,
            tarifes: [],
            historial: [],
            carregant: false,
            error: null,
            finestraMinuts: 180
        }
    },

    getters: {
        volsOrdenats: function (state) {
            return state.vols
        },
        obtenirTarifa: function (state) {
            return function (nom) {
                for (var i = 0; i < state.tarifes.length; i++) {
                    if (state.tarifes[i].nom === nom) {
                        return state.tarifes[i]
                    }
                }
                return null
            }
        }
    },

    actions: {
        carregarVols: function (finestraMinuts) {
            var self = this
            self.carregant = true
            self.error = null
            var params = {}
            if (finestraMinuts) {
                params.finestraMinuts = finestraMinuts
                self.finestraMinuts = finestraMinuts
            }

            return api.get('/vols', { params: params }).then(function (response) {
                self.vols = response.data.vols
                self.carregant = false
                return self.vols
            }).catch(function (error) {
                self.carregant = false
                self.error = 'Error carregant vols'
                throw error
            })
        },

        carregarDetall: function (volId) {
            var self = this
            self.carregant = true

            return api.get('/vols/' + volId).then(function (response) {
                self.volActual = response.data.vol
                self.carregant = false
                return self.volActual
            }).catch(function (error) {
                self.carregant = false
                self.error = 'Error carregant detall del vol'
                throw error
            })
        },

        carregarTarifes: function () {
            var self = this
            return api.get('/tarifes').then(function (response) {
                self.tarifes = response.data.tarifes
                return self.tarifes
            }).catch(function (error) {
                self.error = 'Error carregant tarifes'
                throw error
            })
        },

        carregarHistorial: function () {
            var self = this
            self.carregant = true
            self.error = null

            return api.get('/vols/historial').then(function (response) {
                self.historial = response.data.vols
                self.carregant = false
                return self.historial
            }).catch(function (error) {
                self.carregant = false
                self.error = 'Error carregant historial'
                throw error
            })
        }
    }
})
