# Composer build step.
# The official Composer image is not used since it has PHP 8 Alpine, and no platform requirements.
# https://github.com/docker-library/docs/tree/master/composer#php-version--extensions
FROM php:7.4-cli-alpine as vendor

WORKDIR /app

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/

# Need patch for applying patches.
RUN set -eux; \
    apk upgrade --no-cache; \
    apk add --no-cache --virtual .composer-rundeps \
    patch

COPY composer.json composer.json
COPY composer.lock composer.lock
COPY web/ web/

RUN set -eux; \
    export COMPOSER_HOME="$(mktemp -d)"; \
    # @todo discouraged, can cause problem with scripts and plugins. need to setup extensions here as well.
    composer install --ignore-platform-reqs --no-interaction --no-dev; \
    rm -rf "$COMPOSER_HOME";


# Taken from https://github.com/docker-library/drupal/blob/master/9.2/php7.4/apache-buster/Dockerfile
# Adds bcmath extension, deployment identifier, and Redis.
FROM php:7.4-apache-buster as build
ARG BUILD_VERSION

# install the PHP extensions we need
RUN set -eux; \
	\
	if command -v a2enmod; then \
		a2enmod rewrite; \
	fi; \
	\
	savedAptMark="$(apt-mark showmanual)"; \
	\
	apt-get update; \
	apt-get install -y --no-install-recommends \
		libfreetype6-dev \
		libjpeg-dev \
		libpng-dev \
		libpq-dev \
		libzip-dev \
    libxml2-dev \
	; \
	\
	docker-php-ext-configure gd \
		--with-freetype \
		--with-jpeg=/usr \
	; \
	\
	docker-php-ext-install -j "$(nproc)" \
    bcmath \
		gd \
		opcache \
		pdo_mysql \
		pdo_pgsql \
    soap \
		zip \
	; \
	\
# reset apt-mark's "manual" list so that "purge --auto-remove" will remove all build dependencies
	apt-mark auto '.*' > /dev/null; \
	apt-mark manual $savedAptMark; \
	ldd "$(php -r 'echo ini_get("extension_dir");')"/*.so \
		| awk '/=>/ { print $3 }' \
		| sort -u \
		| xargs -r dpkg-query -S \
		| cut -d: -f1 \
		| sort -u \
		| xargs -rt apt-mark manual; \
	\
	apt-get purge -y --auto-remove -o APT::AutoRemove::RecommendsImportant=false; \
	rm -rf /var/lib/apt/lists/*

# set recommended PHP.ini settings
# see https://secure.php.net/manual/en/opcache.installation.php
RUN { \
		echo 'opcache.memory_consumption=128'; \
		echo 'opcache.interned_strings_buffer=8'; \
		echo 'opcache.max_accelerated_files=4000'; \
		echo 'opcache.revalidate_freq=60'; \
		echo 'opcache.fast_shutdown=1'; \
	} > /usr/local/etc/php/conf.d/opcache-recommended.ini
RUN { \
		echo 'memory_limit=256M'; \
		echo 'upload_max_filesize=10M'; \
	} > $PHP_INI_DIR/conf.d/zzz.ini

RUN set -eux; \
    sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf; \
    sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf;

RUN pecl install redis; docker-php-ext-enable redis
RUN pecl install apcu; docker-php-ext-enable apcu

# Copy precompiled codebase into the container.
COPY --from=vendor /app/ /var/www/html/
COPY config/ /var/www/html/config/
COPY load.environment.php /var/www/html/load.environment.php
RUN mkdir -p /var/www/html/web/sites/default/files
RUN chown -R www-data:www-data /var/www/html/web

# Adjust the Apache docroot.
ENV APACHE_DOCUMENT_ROOT=/var/www/html/web

ENV DEPLOYMENT_IDENTIFIER=$BUILD_VERSION

CMD ["/usr/sbin/apache2ctl", "-D", "FOREGROUND"]
