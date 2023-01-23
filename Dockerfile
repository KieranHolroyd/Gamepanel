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
	&& docker-php-ext-install -j$(nproc) zip

# Enable mod_rewrite
RUN a2enmod rewrite

# Copy existing application directory contents
COPY . '/var/www/html'
VOLUME [ "/var/www/html" ]

# Install composer and add to PATH
RUN curl -sS https://getcomposer.org/installer | php
RUN mv /var/www/html/composer.phar /usr/local/bin/composer

# Install dependencies
RUN composer install