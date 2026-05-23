#!/bin/bash -e

chown -R www-data:www-data /var/www/var || true
chmod -R 775 /var/www/var || true

exec apache2-foreground
