# 🚀 Desplegament i Configuració — Last24BCN

Aquest document detalla com posar en marxa l'aplicació tant en local com en el servidor remot de producció, incloent la configuració específica de ports per a entorns multi-tenant.

## 🏗️ Requisits previs
- **Docker** i **Docker Compose** instal·lats.
- Un client de Git.

## 🌐 Configuració de Ports (Entorn Multi-tenant)
Per defecte, l'aplicació està configurada per utilitzar ports acabats en **5** per evitar conflictes al servidor compartit `91.99.222.189`.

| Servei | Port Públic (Host) | Port Intern (Docker) | Port Proxy (Local) |
|--------|-------------------|----------------------|--------------------|
| **Web (HTTPS)** | `443` | - | `5055` |
| **API (Laravel)** | - | `8000` | `8055` |
| **Realtime** | - | `3001` | `3055` |
| **Base de Dades** | `33065` | `3306` | - |

> [!NOTE]
> En aquest nou model de producció, un Nginx extern del host gestiona l'SSL i redirigeix el tràfic als ports `5055`, `8055` i `3055`.

## 🚀 Desplegament amb Docker Compose

### 📦 Producció
Per aixecar tot l'entorn de producció amb el domini personalitzat:
```bash
docker-compose -f docker-compose.prod.yml up -d --build
```

### 💻 Desenvolupament (Branca `dev`)
Per treballar en local sense SSL:
```bash
docker-compose up -d --build
```

## 🤖 Automatització amb GitHub Actions
El projecte inclou un workflow de CI/CD basat en la branca **`main`**. 

### Secrets Necessaris
Cal configurar els següents secrets a GitHub (`Settings > Secrets > Actions`):
- `SERVER_HOST`: `91.99.222.189`
- `SERVER_USER`: `root`
- `SERVER_PASSWORD`: Les teves credencials de SSH.
- `RESEND_API_KEY`: Clau d'API de Resend per a l'enviament oficial de correus.
- `APP_KEY`, `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, etc. (veure `.env.prod.example`).

Cada vegada que es faci un `push` o un `merge` a **`main`**, el sistema:
1. Es connectarà al servidor via SSH.
2. Farà un `git pull`.
3. Reconstruirà els contenidors.
4. Executarà migracions i seeds.
5. Optimitzarà la caché de Laravel (`config:cache`, `route:cache`).

## ⚙️ Variables d'Entorn (.env)
Consulta el fitxer [`.env.prod.example`](.env.prod.example) per veure la llista completa de variables necessàries per al correcte funcionament del domini `last24bcn.daw.inspedralbes.cat`.
