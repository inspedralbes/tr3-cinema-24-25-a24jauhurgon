#!/bin/sh

# Generar certificat auto-signat (self-signed) si no existeix encara
if [ ! -f /etc/nginx/ssl/cert.pem ]; then
    echo "Generant certificat SSL Auto-signat per a HTTPS..."
    mkdir -p /etc/nginx/ssl
    openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
        -keyout /etc/nginx/ssl/key.pem \
        -out /etc/nginx/ssl/cert.pem \
        -subj "/C=ES/ST=Barcelona/L=Barcelona/O=Last24BCN/CN=last24bcn.daw.inspedralbes.cat"
    echo "Certificat generat correctament."
fi

# Substituir variables d'entorn en la plantilla Nginx
envsubst '${NGINX_HOST}' < /etc/nginx/templates/default.conf.template > /etc/nginx/conf.d/default.conf

# Donar el relleu al procés principal (Nginx)
exec "$@"
