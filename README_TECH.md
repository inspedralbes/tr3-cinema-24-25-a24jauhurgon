# 🛠️ Stack Tecnològic i API — Last24BCN

Detallem l'arquitectura interna de l'aplicació i les integracions que permeten el funcionament en temps real i alta concurrència.

## 🏗️ Arquitectura (Estela Stack)

L'aplicació es basa en una arquitectura de microserveis orquestrats per Docker:

-   **Backend Core (Laravel 11)**: Gestiona la lògica de negoci, base de dades (MySQL) i l'autenticació (Sanctum).
-   **Real-Time Service (Node.js + Socket.IO)**: Canal bidireccional per a actualitzacions instantànies del seatmap i la cua de compra.
-   **Frontend (Vue 3 + Pinia)**: SPA reactiva amb interfície premium i modular.
-   **Infrastructura (Nginx)**: Proxy invers amb SSL auto-signat per a comunicacions segures (HTTPS/WSS).

## 📡 API RESTful (Laravel)

### 🔓 Rutes Públiques
- `GET /api/vols`: Llistat dinàmic de vols en finestra de 24h.
- `GET /api/tarifes`: Obtenció dels preus actius.

### 🔐 Autenticació i OAuth
- `POST /api/login` / `POST /api/registre`: Auth tradicional via Sanctum.
- `GET /api/auth/google`: Flux OAuth2 delegat via Laravel Socialite.

### 🎟️ Compra i Concurrència
- `POST /api/compra/bloquejar`: Client-side locking per evitar duplicats en el seatmap.
- `POST /api/compra/confirmar`: Transaccions atòmiques en BD amb `lockForUpdate()` per evitar sobre-venda d'última hora.

## 🔄 Lògica de Temps Real (WebSockets)

El sistema utilitza un flux de comunicació triangular:
1. El **Backend (PHP)** realitza l'acció i notifica al servidor de **Sockets (Node)** via una API request interna automàtica (`SOCKET_SERVER_URL=http://socket:3001`).
2. El servidor de **Sockets** difon (`emit`) l'esdeveniment pertinent.
3. El **Frontend** reacciona de forma Optimista i redibuixa la UI sense necessitat de recarregar.

**Esdeveniments Clau:**
- `seatmap-actualitzat` / `cua-canvi` (Client)
- `monitoritzacio_actualitzada` (Admin Dashboard Global)
- `barreta_embarcament_actualitzada` (Admin Scanner QR)

## 🧾 Eines de Gestió
- **Generació de Bitllets**: DomPDF per crear el document oficial.
- **Check-in QR**: Integració de `vue-qrcode-reader` per a l'escaneig de bitllets en la Torre de Control (Panell Admin).
- **Mailing**: Enviament assíncron de bitllets un cop finalitzada la compra.

##  Privacitat i Historial Intel·ligent (Garbage Collection)
L'aplicació compta amb una rutina passiva en l'endpoint de llistats:
1. **Garbage Collection**: Tot vol la data de sortida del qual ja ha passat **sense cap bitllet venut** és completament esborrat de la Base de Dades per prevenir l'acumulació de brossa del Simulador.
2. **Historial Segur**: Els vols vençuts _amb_ vendes es passen a l'Historial Diari (`/api/vols/historial`).
3. **Ofuscació**: L'API d'Historial censura qualsevol dada privada (PII) d'usuaris. Les dades retornades al Frontend es limiten al mapa de seients en format `{fila, columna}`, eliminant la traçabilitat dels passatgers per als visitants generals.
