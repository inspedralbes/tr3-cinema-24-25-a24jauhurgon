# ✈️ Last24BCN — Vols d'Última Hora

**La teva passarel·la definitiva per aconseguir els bitllets d'avió més barats des de Barcelona just abans d'enlairar-se.**

Last24BCN és una plataforma web innovadora diseñada per a la venda de vols "Last-Minute" des de l'aeroport del Prat. L'aplicació simula l'experiència real d'un aeroport amb cua de compra, mapes de seients interactius i actualitzacions en temps real.

## ✨ Funcionalitats Clau

- 🛫 **Cartellera Dinàmica**: Només els vols més pròxims (properes 24h) per garantir l'exclusivitat last-minute.
- 🎟️ **Waiting Room Virtual**: Gestió concurrent de l'accés a la compra per evitar saturació del sistema.
- 💺 **Live Seatmap**: Selecció de seients interactiva amb actualització instantània a tots els usuaris via WebSockets.
- 🔒 **Clients Premium & Socis**: Accés exclusiu a First Class i descomptes aplicats automàticament.
- 🎫 **Bitllet Digital Seguro**: Generació instantània de PDF amb codi QR xifrat enviat via **Resend**.
- 🛂 **Torre de Control (Admin)**: Panell de monitorització en temps real, overrides d'estat i check-in per escàner QR.
- 🧹 **Smart History & GC**: Neteja automàtica (Garbage Collection) de vols passats sense vendes per mantenir la DB optimitzada.

## 🛠️ Tecnologies Principals
- **Frontend**: Vue 3, Pinia, Vite, TailwindCSS.
- **Backend**: Laravel 11, Sanctum, Socialite (Google Login), Resend PHP.
- **Temps Real**: Node.js + Socket.IO.
- **Infraestructura**: Docker & Nginx Reverse Proxy (SSL/HTTPS).

## 📚 Documentació Detallada
Per a més informació, consulta els nostres guies específiques:
- 📖 [**Guia de Desplegament i Ports**](README_DEPLOY.md): Com aixecar el projecte i detalls del servidor.
- ⚙️ [**Detalls Tècnics i API**](README_TECH.md): Arquitectura, endpoints i flux de dades en temps real.

## 👤 Autor
Desenvolupat per **Jaume Hurtado González** com a projecte TR3 Cinema 24-25.

---
🔗 **Accés a l'App**: [last24bcn.daw.inspedralbes.cat:8445](https://last24bcn.daw.inspedralbes.cat)
    - Admin: [admin@ultimahorabcn.cat] / password
    - Usuari Premium: [premium@example.com] / password
    - Usuari General: [general@example.com] / password
    - Usuari Propi: amb autenticació de Google o com a convidat
