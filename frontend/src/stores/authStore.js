// Store d'autenticació — Gestiona login, registre, logout i clientId per a convidats
import { defineStore } from 'pinia'
import api from '../services/apiService.js'
import socketService from '../services/socketService.js'

export var useAuthStore = defineStore('auth', {
    state: function () {
        return {
            usuari: (function () {
                try { return JSON.parse(localStorage.getItem('usuari')) } catch (e) { return null }
            })(),
            token: localStorage.getItem('auth_token') || null,
            clientId: localStorage.getItem('clientId') || null,
            carregant: false,
            error: null
        }
    },

    getters: {
        estaAutenticat: function (state) {
            return state.token !== null
        },
        esAdmin: function (state) {
            return state.usuari && state.usuari.rol === 'admin'
        },
        esSoci: function (state) {
            return state.usuari && state.usuari.esSoci
        },
        obtenirClientId: function (state) {
            return state.clientId
        },
        esPremium: function (state) {
            return state.usuari ? state.usuari.rol === 'premium' : false
        }
    },

    actions: {
        // L'usuari ja no ho pot canviar manualment, ho gestiona l'Administrador a la DB
        // Inicialitzar clientId per a convidats (guardat a localStorage)
        inicialitzarClient: function () {
            var clientIdGuardat = localStorage.getItem('clientId')
            if (clientIdGuardat) {
                this.clientId = clientIdGuardat
            } else {
                // Generar un clientId únic
                this.clientId = 'client_' + Date.now() + '_' + Math.floor(Math.random() * 10000)
                localStorage.setItem('clientId', this.clientId)
            }

            // Restaurar sessió si hi ha token
            var tokenGuardat = localStorage.getItem('auth_token')
            var usuariGuardat = localStorage.getItem('usuari')
            if (tokenGuardat && usuariGuardat) {
                this.token = tokenGuardat
                try {
                    this.usuari = JSON.parse(usuariGuardat)
                } catch (e) {
                    this.token = null
                    localStorage.removeItem('auth_token')
                    localStorage.removeItem('usuari')
                }
                if (this.usuari) {
                    socketService.identificarUsuari(this.usuari.id)
                }
            }
        },

        // Login amb email i password
        login: function (email, password) {
            var self = this
            self.carregant = true
            self.error = null

            return api.post('/auth/login', {
                email: email,
                password: password
            }).then(function (response) {
                var dades = response.data
                self.usuari = dades.usuari
                self.token = dades.token
                localStorage.setItem('auth_token', dades.token)
                localStorage.setItem('usuari', JSON.stringify(dades.usuari))
                self.carregant = false
                return dades
            }).catch(function (error) {
                self.carregant = false
                if (error.response && error.response.data) {
                    self.error = error.response.data.message || 'Error de login'
                } else {
                    self.error = 'Error de connexió'
                }
                throw error
            })
        },

        // Registre d'un nou usuari
        registre: function (nom, email, password, passwordConfirmation) {
            var self = this
            self.carregant = true
            self.error = null

            return api.post('/auth/registre', {
                name: nom,
                email: email,
                password: password,
                password_confirmation: passwordConfirmation
            }).then(function (response) {
                var dades = response.data
                self.usuari = dades.usuari
                self.token = dades.token
                localStorage.setItem('auth_token', dades.token)
                localStorage.setItem('usuari', JSON.stringify(dades.usuari))
                self.carregant = false
                socketService.identificarUsuari(dades.usuari.id)
                return dades
            }).catch(function (error) {
                self.carregant = false
                if (error.response && error.response.data) {
                    self.error = error.response.data.message || 'Error de registre'
                } else {
                    self.error = 'Error de connexió'
                }
                throw error
            })
        },

        // Tancar sessió de forma optimista
        logout: function () {
            var self = this

            // 1. Netejar localment al moment per a un feedback instantani
            self.usuari = null
            self.token = null
            localStorage.removeItem('auth_token')
            localStorage.removeItem('usuari')

            // 2. Desconnectar socket immediatament
            socketService.desconnectar()

            // 3. Informar al servidor en segon pla (sense 'return' per no bloquejar)
            api.post('/auth/logout').catch(function () {
                // Silenciós, ja hem netejat localment
            })

            return Promise.resolve() // Permetre redirecció immediata
        },

        // Redirigir a Google per login
        loginGoogle: function () {
            // Simplement redirigim a l'endpoint de Laravel que fa el redirect a Google
            window.location.href = 'http://localhost:8000/api/auth/google/redirect'
        },

        // Guardar sessió des de paràmetres de URL (Callback Social)
        guardarSessioSocial: function (token, usuariJson) {
            if (!token || !usuariJson) return;
            try {
                var usuari = JSON.parse(decodeURIComponent(usuariJson))
                this.token = token
                this.usuari = usuari
                localStorage.setItem('auth_token', token)
                localStorage.setItem('usuari', JSON.stringify(usuari))
                socketService.identificarUsuari(usuari.id)
            } catch (e) {
                console.error('Error processant callback social', e)
            }
        }
    }
})
