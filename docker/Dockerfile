ARG PHP_VERSION=8.4
FROM php:${PHP_VERSION}-cli-alpine

# Install system dependencies
RUN apk add --no-cache \
    git \
    unzip \
    bash \
    curl \
    libzip-dev \
    icu-dev \
    libxml2-dev \
    oniguruma-dev \
    yaml-dev \
    $PHPIZE_DEPS

# Install PHP extensions
RUN docker-php-ext-install \
    zip \
    intl \
    xml \
    mbstring \
    pdo \
    pdo_mysql \
    bcmath \
    opcache

# Install YAML extension via PECL
RUN pecl install yaml && docker-php-ext-enable yaml

# Configure OPcache for better performance
RUN echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini && \
    echo "opcache.enable_cli=1" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini && \
    echo "opcache.memory_consumption=256" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini && \
    echo "opcache.interned_strings_buffer=16" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini && \
    echo "opcache.max_accelerated_files=20000" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini && \
    echo "opcache.validate_timestamps=1" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini && \
    echo "opcache.revalidate_freq=2" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini && \
    echo "opcache.fast_shutdown=1" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install Task (go-task)
RUN sh -c "$(curl --location https://taskfile.dev/install.sh)" -- -d -b /usr/local/bin

# Set working directory
WORKDIR /app

# Keep container running
CMD ["tail", "-f", "/dev/null"]

