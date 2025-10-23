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
    $PHPIZE_DEPS

# Install PHP extensions
RUN docker-php-ext-install \
    zip \
    intl \
    xml \
    mbstring \
    pdo \
    pdo_mysql \
    bcmath

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install Task (go-task)
RUN sh -c "$(curl --location https://taskfile.dev/install.sh)" -- -d -b /usr/local/bin

# Set working directory
WORKDIR /app

# Keep container running
CMD ["tail", "-f", "/dev/null"]

