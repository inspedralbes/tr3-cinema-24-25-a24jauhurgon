// Servei Socket.IO — Connexió temps real amb el servidor
// Gestiona la connexió al servidor Socket.IO per actualitzacions del seatmap i la cua
import { io } from 'socket.io-client'

var SOCKET_URL = import.meta.env.VITE_SOCKET_URL || 'http://localhost:3001'

var socket = null
var connectat = false

// ID de l'usuari loguejat, per re-identificar en cas de reconnexió
var usuariIdActual = null

// Inicialitzar connexió
function connectar() {
    if (socket) {
        return socket
    }

    socket = io(SOCKET_URL, {
        transports: ['websocket', 'polling'],
        reconnectionAttempts: 10,
        reconnectionDelay: 2000,
        autoConnect: true
    })

    socket.on('connect', function () {
        connectat = true
        // Re-identificar l'usuari cada vegada que (re)ens connectem al servidor
        // Això resol la race condition: sempre identifiquem quan la connexió és real
        if (usuariIdActual) {
            socket.emit('identificar_usuari', usuariIdActual)
        }
    })

    socket.on('disconnect', function () {
        connectat = false
    })

    socket.on('connect_error', function (err) {
        // Silently handle error
    })

    return socket
}

// Unir-se a la sala d'un vol (per rebre updates del seatmap)
function unirVol(volId, clientId) {
    var s = connectar()
    s.emit('unir-vol', { volId: volId, clientId: clientId })
}

// Sortir de la sala d'un vol
function sortirVol(volId) {
    if (socket) {
        socket.emit('sortir-vol', volId)
    }
}

// Unir-se a la cua d'un vol
function unirCua(volId) {
    var s = connectar()
    s.emit('unir-cua', volId)
}

// Emetre actualitzacions de la cua (encara necessari si gestionem posició des del socket)
function emitreCuaActualitzada(volId, actius, capacitat) {
    if (socket) {
        socket.emit('cua-actualitzada', {
            volId: volId,
            actius: actius,
            capacitat: capacitat
        })
    }
}

// Escoltar actualitzacions del seatmap
function onSeatmapActualitzat(callback) {
    var s = connectar()
    s.on('seatmap-actualitzat', callback)
}

// Escoltar quan un nou usuari es connecta
function onUsuariConnectat(callback) {
    var s = connectar()
    s.on('usuari-connectat', callback)
}

// Escoltar canvis de cua
function onCuaCanvi(callback) {
    var s = connectar()
    s.on('cua-canvi', callback)
}

// Demanar la llista actual d'usuaris online al servidor (per quan obrim el panell d'admin)
function demanarUsuarisOnline() {
    var s = connectar()
    if (s.connected) {
        s.emit('demanar_usuaris_online')
    } else {
        // Esperar la connexió i demanar-la
        s.once('connect', function () {
            s.emit('demanar_usuaris_online')
        })
    }
}

// Escoltar usuaris online (llista global)
function onUsuarisOnline(callback) {
    var s = connectar()
    s.on('usuaris_online', callback)
}

// Escoltar nous registres
function onNouUsuariRegistrat(callback) {
    var s = connectar()
    s.on('nou_usuari_registrat', callback)
}

// Escoltar canvis de rol generals (General <-> Premium)
function onRolActualitzat(callback) {
    var s = connectar()
    s.on('rol_actualitzat', callback)
}

// Fase 13: QR Check-in Boarding Bar
function onBarretaEmbarcamentActualitzada(callback) {
    var s = connectar()
    s.on('barreta_embarcament_actualitzada', callback)
}

// Escoltar actualitzacions per al Dashboard Admin
function onMonitoritzacioActualitzada(callback) {
    var s = connectar()
    s.on('monitoritzacio_actualitzada', callback)
}

// Escoltar quan un usuari és autoritzat per comprar (cua -> reserva)
function onUsuariAutoritzat(callback) {
    var s = connectar()
    s.on('usuari_autoritzat', callback)
}

// Informar al servidor de l'ID d'usuari loguejat actual
// La connexió pot no estar establerta encara, per tant guardem l'ID
// i el socket l'envia automàticament quan l'event 'connect' es dispara
function identificarUsuari(usuariId) {
    usuariIdActual = usuariId
    var s = connectar()
    // Si ja estem connectats, enviar ara mateix
    if (s.connected) {
        s.emit('identificar_usuari', usuariId)
    }
    // Si NO estem connectats, el handler 'connect' l'enviarà quan estableixi la connexió
}

// Escoltar canvis en l'estat d'un vol (obert, tancat, finalitzat)
function onVolEstatActualitzat(callback) {
    var s = connectar()
    s.on('vol_estat_actualitzat', callback)
}

// Netejar listeners
function netejarListeners() {
    if (socket) {
        socket.off('seatmap-actualitzat')
        socket.off('usuari-connectat')
        socket.off('cua-canvi')
        socket.off('usuaris_online')
        socket.off('nou_usuari_registrat')
        socket.off('rol_actualitzat')
        socket.off('barreta_embarcament_actualitzada')
        socket.off('monitoritzacio_actualitzada')
        socket.off('vol_estat_actualitzat')
        socket.off('usuari_autoritzat')
    }
}

// Desconnectar completament
function desconnectar() {
    if (socket) {
        netejarListeners()
        socket.disconnect()
        socket = null
        connectat = false
    }
}

// Exportar funcions
export default {
    connectar: connectar,
    unirVol: unirVol,
    sortirVol: sortirVol,
    unirCua: unirCua,
    emitreCuaActualitzada: emitreCuaActualitzada,
    onSeatmapActualitzat: onSeatmapActualitzat,
    onUsuariConnectat: onUsuariConnectat,
    onCuaCanvi: onCuaCanvi,
    onUsuarisOnline: onUsuarisOnline,
    demanarUsuarisOnline: demanarUsuarisOnline,
    onNouUsuariRegistrat: onNouUsuariRegistrat,
    onRolActualitzat: onRolActualitzat,
    onBarretaEmbarcamentActualitzada: onBarretaEmbarcamentActualitzada,
    onMonitoritzacioActualitzada: onMonitoritzacioActualitzada,
    onVolEstatActualitzat: onVolEstatActualitzat,
    identificarUsuari: identificarUsuari,
    onUsuariAutoritzat: onUsuariAutoritzat,
    netejarListeners: netejarListeners,
    desconnectar: desconnectar
}
