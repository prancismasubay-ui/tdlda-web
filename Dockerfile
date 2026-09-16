FROM php:8.2-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends ca-certificates \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-install pdo_mysql \
    && a2enmod rewrite headers

WORKDIR /var/www/html
COPY . /var/www/html/

# Render provides PORT at runtime. Apache must listen on that port.
COPY docker/start.sh /usr/local/bin/render-start.sh
RUN chmod +x /usr/local/bin/render-start.sh \
    && chown -R www-data:www-data /var/www/html

ENV PORT=10000
EXPOSE 10000

CMD ["/usr/local/bin/render-start.sh"]
