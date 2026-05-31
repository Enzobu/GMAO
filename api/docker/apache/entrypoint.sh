#!/bin/bash -e

JWT_PRIVATE_KEY="${JWT_SECRET_KEY:-/var/www/config/jwt/private.pem}"
JWT_PUBLIC_KEY="${JWT_PUBLIC_KEY:-/var/www/config/jwt/public.pem}"

mkdir -p "$(dirname "$JWT_PRIVATE_KEY")"

if [ ! -f "$JWT_PRIVATE_KEY" ] || [ ! -f "$JWT_PUBLIC_KEY" ]; then
    echo "JWT keys not found, generating keypair..."
    php bin/console lexik:jwt:generate-keypair -n
else
    echo "JWT keys already exist, skipping generation."
fi

chown -R www-data:www-data /var/www/var || true
chmod -R 775 /var/www/var || true

exec apache2-foreground
