// Socket.IO Server — Última Hora BCN
// Gestiona actualitzacions en temps real: seatmap, cua, i events de compra
var http = require('http')
var socketIo = require('socket.io')

var PORT = process.env.SOCKET_PORT || 3001

var server = http.createServer()
var io = socketIo(server, {
    cors: {
        origin: ['http://localhost:5173', 'http://localhost:3000', 'http://127.0.0.1:5173'],
        methods: ['GET', 'POST']
    }
})

// Mapa de socket.id -> clientId per fer cleanup en desconnexió
var socketToClient = {}
// Mapa de socket.id -> usuariId per saber qui està online realment (usuaris loguejats)
var socketToUser = {}
var usuarisOnline = new Set()
var connexionsActives = 0

function emetreUsuarisOnline() {
    io.emit('usuaris_online', Array.from(usuarisOnline))
}

io.on('connection', function (socket) {
    connexionsActives = connexionsActives + 1
    console.log('[CONN] Nou client: ' + socket.id + ' (actius: ' + connexionsActives + ')')

    // Client s'identifica amb la seva sessió d'usuari (per Vue AuthStore)
    socket.on('identificar_usuari', function (usuariId) {
        if (usuariId) {
            socketToUser[socket.id] = usuariId
            usuarisOnline.add(usuariId)
            console.log('[USER] Usuari Online: ID ' + usuariId)
            emetreUsuarisOnline()
        }
    })

    // El client (panell d'Admin) demana la llista actual d'usuaris online
    socket.on('demanar_usuaris_online', function () {
        socket.emit('usuaris_online', Array.from(usuarisOnline))
    })

    // Client s'uneix a una sala de vol per rebre actualitzacions del seatmap
    socket.on('unir-vol', function (data) {
        // Suport per volId (antic) o { volId, clientId } (nou)
        var volId = typeof data === 'object' ? data.volId : data
        var clientId = typeof data === 'object' ? data.clientId : null

        socket.join('vol-' + volId)

        if (clientId) {
            socketToClient[socket.id] = clientId
            console.log('[SALA] ' + socket.id + ' (' + clientId + ') unit a vol-' + volId)
        } else {
            console.log('[SALA] ' + socket.id + ' unit a vol-' + volId)
        }

        // Notificar als altres clients de la sala
        var room = io.sockets.adapter.rooms.get('vol-' + volId)
        socket.to('vol-' + volId).emit('usuari-connectat', {
            missatge: 'Un nou usuari està mirant els seients',
            total: room ? room.size : 1
        })
    })

    // Client deixa la sala d'un vol
    socket.on('sortir-vol', function (volId) {
        socket.leave('vol-' + volId)
        console.log('[SALA] ' + socket.id + ' ha sortit de vol-' + volId)
    })

    // Client s'uneix a la sala de cua d'un vol
    socket.on('unir-cua', function (volId) {
        socket.join('cua-' + volId)
        console.log('[CUA] ' + socket.id + ' unit a cua-' + volId)
    })

    // ---- Events emesos des del backend (via API interna) ----

    // Seient bloquejat per un client
    socket.on('seient-bloquejat', function (data) {
        // data = { volId, fila, columna, clientId }
        socket.to('vol-' + data.volId).emit('seatmap-actualitzat', {
            tipus: 'bloquejat',
            fila: data.fila,
            columna: data.columna,
            clientId: data.clientId
        })
        console.log('[SEAT] Bloquejat ' + data.fila + '-' + data.columna + ' al vol ' + data.volId)
    })

    // Seient alliberat
    socket.on('seient-alliberat', function (data) {
        socket.to('vol-' + data.volId).emit('seatmap-actualitzat', {
            tipus: 'alliberat',
            fila: data.fila,
            columna: data.columna
        })
        console.log('[SEAT] Alliberat ' + data.fila + '-' + data.columna + ' al vol ' + data.volId)
    })

    // Seient comprat (definitiu)
    socket.on('seient-comprat', function (data) {
        socket.to('vol-' + data.volId).emit('seatmap-actualitzat', {
            tipus: 'comprat',
            fila: data.fila,
            columna: data.columna
        })
        console.log('[SEAT] Comprat ' + data.fila + '-' + data.columna + ' al vol ' + data.volId)
    })

    // Actualització de la cua (posició canviada)
    socket.on('cua-actualitzada', function (data) {
        io.to('cua-' + data.volId).emit('cua-canvi', {
            actius: data.actius,
            capacitat: data.capacitat
        })
    })

    // Desconnexió
    socket.on('disconnect', function () {
        connexionsActives = connexionsActives - 1

        var clientId = socketToClient[socket.id]
        if (clientId) {
            console.log('[DISC] Client ' + clientId + ' desconnectat. Alliberant seients...')
            alliberarSeientsBackend(clientId)
            delete socketToClient[socket.id]
        }

        var usuariId = socketToUser[socket.id]
        if (usuariId) {
            delete socketToUser[socket.id]
            // Comprovar si l'usuari té altres pestanyes/sockets oberts abans d'esborrar-lo del Set global
            var encaraOnline = Object.values(socketToUser).includes(usuariId)
            if (!encaraOnline) {
                usuarisOnline.delete(usuariId)
            }
            emetreUsuarisOnline()
        }

        console.log('[DISC] Client desconnectat: ' + socket.id + ' (actius: ' + connexionsActives + ')')
    })
})

// Funció per avisar al backend que un usuari s'ha desconnectat
function alliberarSeientsBackend(clientId) {
    var postData = JSON.stringify({ clientId: clientId })

    var options = {
        hostname: 'last24_backend', // Nom del servei a docker-compose
        port: 8000,
        path: '/api/compra/cleanup-holds',
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Content-Length': Buffer.byteLength(postData)
        }
    }

    var req = http.request(options, function (res) {
        // OK
    })

    req.on('error', function (e) {
        console.error('[CLEANUP] Error contactant Laravel: ' + e.message)
    })

    req.write(postData)
    req.end()
}

// ---- API interna per al backend Laravel ----
// El backend pot enviar events via POST a aquest endpoint
var apiServer = http.createServer(function (req, res) {
    if (req.method === 'POST' && req.url === '/emit') {
        var body = ''
        req.on('data', function (chunk) {
            body = body + chunk
        })
        req.on('end', function () {
            try {
                var data = JSON.parse(body)
                // data = { event: 'seatmap-actualitzat', room: 'vol-1', payload: {...} } OR glogal event { event: 'x', payload: {...} }
                if (data.event && data.payload) {
                    if (data.room) {
                        io.to(data.room).emit(data.event, data.payload)
                        console.log('[API] Emès ' + data.event + ' a la sala ' + data.room)
                    } else {
                        // Global broadcast
                        io.emit(data.event, data.payload)
                        console.log('[API] Broadcast global: ' + data.event)
                    }
                }
                res.writeHead(200, { 'Content-Type': 'application/json' })
                res.end(JSON.stringify({ ok: true }))
            } catch (e) {
                res.writeHead(400, { 'Content-Type': 'application/json' })
                res.end(JSON.stringify({ error: 'JSON invàlid' }))
            }
        })
    } else {
        res.writeHead(404)
        res.end('Not found')
    }
})

// Iniciar servidors
server.listen(PORT, function () {
    console.log('=== Socket.IO Server Última Hora BCN ===')
    console.log('WebSocket: http://localhost:' + PORT)
})

var API_PORT = parseInt(PORT) + 1
apiServer.listen(API_PORT, function () {
    console.log('API interna: http://localhost:' + API_PORT + '/emit')
    console.log('=========================================')
})
