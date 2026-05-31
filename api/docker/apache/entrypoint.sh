#!/bin/bash -e

JWT_PRIVATE_KEY_FILE="/var/www/config/jwt/private.pem"
JWT_PUBLIC_KEY_FILE="/var/www/config/jwt/public.pem"

chown -R www-data:www-data /var/www/var || true
chmod -R 775 /var/www/var || true

if [ "$APP_ENV" = "prod" ]; then
    if [ -f /var/www/vendor/autoload.php ]; then
        if [ ! -f "$JWT_PRIVATE_KEY_FILE" ] || [ ! -f "$JWT_PUBLIC_KEY_FILE" ]; then
            echo "JWT keys not found, generating keypair..."
            php bin/console lexik:jwt:generate-keypair -n
            chown -R www-data:www-data /var/www/config/jwt || true
            chmod -R 775 /var/www/config/jwt || true
        else
            echo "JWT keys already exist, skipping generation."
        fi
    else
        echo "Vendor directory not found, skipping JWT keypair generation."
    fi
else
    echo "APP_ENV=$APP_ENV, skipping JWT keypair generation."
fi

exec apache2-foreground