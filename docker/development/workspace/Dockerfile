FROM php:8.2-fpm

# Installation des dépendances système
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    curl \
    libpq-dev \
    libzip-dev \
    zip \
    sudo \
    && docker-php-ext-install pdo pdo_pgsql zip

# Installation de Composer (copie depuis image officielle)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Création de l'utilisateur non-root (www)
ARG UID=1000
ARG GID=1000

RUN if getent group ${GID}; then \
      useradd -m -u ${UID} -g ${GID} -s /bin/bash www; \
    else \
      groupadd -g ${GID} www && \
      useradd -m -u ${UID} -g www -s /bin/bash www; \
    fi && \
    usermod -aG sudo www && \
    echo 'www ALL=(ALL) NOPASSWD:ALL' >> /etc/sudoers

# Installation du Laravel installer sous l’utilisateur www
USER www
RUN composer global require laravel/installer && \
    echo 'export PATH="$PATH:/home/www/.composer/vendor/bin"' >> /home/www/.bashrc

# Dossier de travail
WORKDIR /var/www

# Lancer PHP-FPM par défaut
CMD ["php-fpm"]
