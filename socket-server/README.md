# Socket.IO Server — BCNJET

Servidor **Socket.IO** per a actualitzacions en temps real del mapa de seients i la cua de compra.

## Execució
```bash
npm install
npm start         # Port 3001
```

## Arquitectura
- **Rooms**: Una room per vol (`flight-{volId}`) per agrupar connexions
- **Events**: Actualitzacions de seients i cua en temps real
- **API interna**: Endpoint HTTP perquè el backend Laravel emeti events

## API interna
```
POST http://localhost:3001/emit
Content-Type: application/json

{
  "room": "flight-1",
  "event": "seatmap-updated",
  "data": { "seients": { "5-3": { "estat": "ocupat" } } }
}
```

## Events
| Direcció | Event | Descripció |
|----------|-------|-----------|
| Client → Server | `join-flight` | Unir-se a room del vol |
| Client → Server | `leave-flight` | Sortir de room del vol |
| Server → Client | `seatmap-updated` | Canvi en el mapa de seients |
| Server → Client | `user-joined` | Nou usuari connectat |
| Server → Client | `user-left` | Usuari desconnectat |
| Server → Client | `queue-updated` | Actualització posició cua |

## Port
Per defecte: `3001`. Configurable via variable d'entorn `SOCKET_PORT`.
