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
1. El **Backend (PHP)** realitza l'acció i notifica al servidor de **Sockets (Node)** via una API interna oculta (Port 3002).
2. El servidor de **Sockets** difon (`emit`) l'esdeveniment a tots els **Clients** connectats.
3. El **Frontend** reacciona i redibuixa la UI sense necessitat de recarregar.

## 🧾 Eines de Gestió
- **Generació de Bitllets**: DomPDF per crear el document oficial.
- **Check-in QR**: Integració de `vue-qrcode-reader` per a l'escaneig de bitllets en la Torre de Control (Panell Admin).
- **Mailing**: Enviament assíncron de bitllets un cop finalitzada la compra.

##  Privacidade e RGPD
Les dades de vols passats s'ofusquen automàticament en l'historial del client per protegir la privacitat, mantenint només els mapes de seients amb dades genèriques (sense noms).
