FROM php:8.3-apache

# Extension PDO MySQL (compatible MariaDB) et réécriture d'URL
RUN docker-php-ext-install pdo_mysql \
    && a2enmod headers

# Le serveur web ne sert que le dossier public/ : src/ et db/ ne sont pas accessibles par URL
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf \
    && sed -ri -e 's!ServerTokens OS!ServerTokens Prod!; s!ServerSignature On!ServerSignature Off!' /etc/apache2/conf-available/security.conf

# Configuration PHP de production : pas d'erreurs affichées
RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

WORKDIR /var/www/html
COPY src ./src
COPY db ./db
COPY public ./public

RUN chown -R www-data:www-data /var/www/html
EXPOSE 80
