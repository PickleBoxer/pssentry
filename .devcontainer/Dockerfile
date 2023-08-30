# Image: https://github.com/devcontainers/images/tree/main/src/php
# Template: https://github.com/devcontainers/templates/tree/main/src/php-mariadb
# [Options] Available image variants: https://mcr.microsoft.com/v2/devcontainers/php/tags/list
ARG imageVariant=8.2-bullseye
FROM mcr.microsoft.com/devcontainers/php:1-${imageVariant}

# Install MariaDB client
RUN apt-get update && export DEBIAN_FRONTEND=noninteractive \
    && apt-get install -y mariadb-client \ 
    && apt-get clean -y && rm -rf /var/lib/apt/lists/*

# Install php-mysql driver
RUN docker-php-ext-install mysqli pdo pdo_mysql

# [Optional] Uncomment this section to install additional OS packages.
# RUN apt-get update && export DEBIAN_FRONTEND=noninteractive \
#     && apt-get -y install --no-install-recommends <your-package-list-here>

# [Optional] Uncomment this line to install global node packages.
# RUN su vscode -c "source /usr/local/share/nvm/nvm.sh && npm install -g <your-package-here>" 2>&1

# PrestasShop Docker base image:
ENV PS_DOMAIN="<to be defined>" \
DB_SERVER="<to be defined>" \
DB_PORT=3306 \
DB_NAME=prestashop \
DB_USER=root \
DB_PASSWD=admin \
DB_PREFIX=ps_ \
ADMIN_MAIL=demo@prestashop.com \
ADMIN_PASSWD=prestashop_demo \
PS_LANGUAGE=en \
PS_COUNTRY=GB \
PS_ALL_LANGUAGES=0 \
PS_INSTALL_AUTO=0 \
PS_ERASE_DB=0 \
PS_INSTALL_DB=0 \
PS_DEV_MODE=0 \
PS_HOST_MODE=0 \
PS_DEMO_MODE=0 \
PS_ENABLE_SSL=0 \
PS_HANDLE_DYNAMIC_DOMAIN=0 \
PS_FOLDER_ADMIN=admin \
PS_FOLDER_INSTALL=install

RUN apt-get update && export DEBIAN_FRONTEND=noninteractive \
    && apt-get -y install --no-install-recommends libmcrypt-dev \
        gnupg2 \
        libjpeg62-turbo-dev \
        libpcre3-dev \
        libpng-dev \
        libwebp-dev \
        libfreetype6-dev \
        libxml2-dev \
        libicu-dev \
        libzip-dev \
        default-mysql-client \
        wget \
        unzip \
        libonig-dev \
        && apt-get clean -y && rm -rf /var/lib/apt/lists/*

# The docker-php-ext-configure command is used in a Dockerfile to configure a PHP extension before it is installed.
RUN docker-php-ext-configure gd --with-freetype=/usr/include/ --with-jpeg=/usr/include/ --with-webp=/usr/include
RUN docker-php-ext-install iconv intl mbstring soap gd zip bcmath

RUN docker-php-source extract \
    && if [ -d "/usr/src/php/ext/mysql" ]; then docker-php-ext-install mysql; fi \
    && if [ -d "/usr/src/php/ext/mcrypt" ]; then docker-php-ext-install mcrypt; fi \
    && if [ -d "/usr/src/php/ext/opcache" ]; then docker-php-ext-install opcache; fi \
    && docker-php-source delete

# Prepare install and CMD script
COPY config_files/ps-extractor.sh config_files/docker_run.sh config_files/docker_nightly_run.sh /tmp/

# If handle dynamic domain
COPY config_files/docker_updt_ps_domains.php /tmp/

# PHP env for dev / demo modes
COPY config_files/defines_custom.inc.php /tmp/
RUN chown www-data:www-data /tmp/defines_custom.inc.php

# Apache configuration
RUN if [ -x "$(command -v apache2-foreground)" ]; then a2enmod rewrite; fi

# PHP configuration
COPY config_files/php.ini /usr/local/etc/php/

# Run
CMD ["/tmp/docker_run.sh"]

# Use ARG to set a default value for PS_VERSION
ARG PS_VERSION=8.1.0

# Set ENV to use the value from ARG (if provided) or use the default value (8.1.0)
ENV PS_VERSION ${PS_VERSION}

# Get PrestaShop
ADD https://github.com/PrestaShop/PrestaShop/releases/download/${PS_VERSION}/prestashop_${PS_VERSION}.zip /tmp/prestashop.zip

# Extract
RUN mkdir -p /tmp/data-ps \
	&& unzip -q /tmp/prestashop.zip -d /tmp/data-ps/ \
	&& bash /tmp/ps-extractor.sh /tmp/data-ps \
	&& rm /tmp/prestashop.zip