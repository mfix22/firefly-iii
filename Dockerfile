FROM php:7.2-apache
ENV FIREFLY_PATH=/var/www/firefly-iii COMPOSER_ALLOW_SUPERUSER=1
LABEL version="1.4" maintainer="thegrumpydictator@gmail.com"

# Create volumes
VOLUME $FIREFLY_PATH/storage/export $FIREFLY_PATH/storage/upload

# Install stuff Firefly III runs with & depends on: php extensions, locales, dev headers and composer
RUN apt-get update && apt-get install -y libpng-dev \
                                            libicu-dev \
                                            unzip \
                                            gettext-base \
                                            libldap2-dev \
                                            libpq-dev \
                                            locales \
                                            libmemcached-dev && \
                                            apt-get clean && \
                                            rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-configure ldap --with-libdir=lib/$(gcc -dumpmachine)/ && \
    docker-php-ext-install -j$(nproc) zip bcmath ldap gd pdo_pgsql pdo_mysql intl opcache && \
    pecl install memcached-3.1.3 && \
    docker-php-ext-enable memcached && \
    a2enmod rewrite && a2enmod ssl && \
    echo "hu_HU.UTF-8 UTF-8\nro_RO.UTF-8 UTF-8\nnb_NO.UTF-8 UTF-8\nde_DE.UTF-8 UTF-8\ncs_CZ.UTF-8 UTF-8\nen_US.UTF-8 UTF-8\nes_ES.UTF-8 UTF-8\nfr_FR.UTF-8 UTF-8\nid_ID.UTF-8 UTF-8\nit_IT.UTF-8 UTF-8\nnl_NL.UTF-8 UTF-8\npl_PL.UTF-8 UTF-8\npt_BR.UTF-8 UTF-8\nru_RU.UTF-8 UTF-8\ntr_TR.UTF-8 UTF-8\nzh_TW.UTF-8 UTF-8\nzh_CN.UTF-8 UTF-8\n\n" > /etc/locale.gen && \
    locale-gen && \
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# configure PHP
RUN cp /usr/local/etc/php/php.ini-production /usr/local/etc/php/php.ini && \
    sed -i 's/max_execution_time = 30/max_execution_time = 600/' /usr/local/etc/php/php.ini && \
    sed -i 's/memory_limit = 128M/memory_limit = 512M/' /usr/local/etc/php/php.ini

# Copy in Firefly III source
WORKDIR $FIREFLY_PATH
ADD . $FIREFLY_PATH

# Ensure correct app directory permission, then `composer install`
RUN chown -R www-data:www-data /var/www && \
    chmod -R 775 $FIREFLY_PATH/storage && \
    composer install --prefer-dist --no-dev --no-scripts --no-suggest

# copy ca certs to correct location
COPY ./.deploy/docker/cacert.pem /usr/local/ssl/cert.pem

# copy Apache config to correct spot.
COPY ./.deploy/docker/apache2.conf /etc/apache2/apache2.conf

# Enable default site (Firefly III)
COPY ./.deploy/docker/apache-firefly.conf /etc/apache2/sites-available/000-default.conf

# Expose port 80
EXPOSE 80

# Run entrypoint thing
ENTRYPOINT [".deploy/docker/entrypoint.sh"]
