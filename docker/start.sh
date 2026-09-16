#!/bin/sh
set -eu
PORT="${PORT:-10000}"
# Change Apache's listen port to Render's assigned port.
sed -i "s/^Listen 80$/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/g" /etc/apache2/sites-enabled/000-default.conf
exec apache2-foreground
