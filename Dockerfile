FROM php:apache

# Install modules
RUN apt-get update && apt-get install -y \
	libfreetype6-dev \
	libjpeg62-turbo-dev \
	libpng-dev \
	libxml2-dev \
	libzip-dev \
	zip \
	unzip \
	&& docker-php-ext-install -j$(nproc) iconv \
	&& docker-php-ext-configure gd --with-freetype --with-jpeg \
	&& docker-php-ext-install -j$(nproc) gd \
	&& docker-php-ext-install -j$(nproc) pdo_mysql \
	&& docker-php-ext-install -j$(nproc) soap \
	&& docker-php-ext-install -j$(nproc) zip \
	&& docker-php-ext-install -j$(nproc) opcache

# Install php-redis
RUN pecl install -o -f redis \
	&&  rm -rf /tmp/pear \
	&&  docker-php-ext-enable redis

# Enable mod_rewrite
RUN a2enmod rewrite

# Copy opcache configration
COPY _cfg/opcache/opcache.ini /usr/local/etc/php/conf.d/opcache.ini
# Copy existing application directory contents
COPY . '/var/www/html'
VOLUME [ "/var/www/html" ]