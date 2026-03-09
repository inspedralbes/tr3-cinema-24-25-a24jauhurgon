FROM nginx:alpine

# Instal·lem openssl per generar els certificats auto-signats al vol
RUN apk add --no-cache openssl

COPY entrypoint.sh /entrypoint.sh
COPY default.conf.template /etc/nginx/templates/default.conf.template

RUN chmod +x /entrypoint.sh

# L'entrypoint generarà el certificat la primera vegada i arrancarà nginx
ENTRYPOINT ["/entrypoint.sh"]
CMD ["nginx", "-g", "daemon off;"]
