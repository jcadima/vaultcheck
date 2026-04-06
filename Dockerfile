FROM php:8.2-cli

ARG user=admin
ARG uid=1000

# git: required for G001-G008 git history checks (symfony/process shells out to git)
# curl/zip/unzip: required by Composer
# libonig-dev: required by mbstring extension
RUN apt-get update && apt-get install -y \
    git \
    curl \
    zip \
    unzip \
    libonig-dev \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# mbstring: required by Composer
RUN docker-php-ext-install mbstring

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Create non-root user
RUN useradd -G www-data -u $uid -d /home/$user $user
RUN mkdir -p /home/$user/.composer && \
    chown -R $user:$user /home/$user

WORKDIR /var/www

USER $user