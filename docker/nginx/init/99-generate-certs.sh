#!/bin/sh
set -e

if [ ! -f /etc/nginx/certs/cert.pem ] || [ ! -f /etc/nginx/certs/key.pem ]; then
    apk add --no-cache openssl
    openssl req -x509 -nodes -newkey rsa:2048 -days 825 \
        -keyout /etc/nginx/certs/key.pem \
        -out /etc/nginx/certs/cert.pem \
        -subj "/CN=localhost"
    echo "Self-signed certificate generated for localhost"
fi
