FROM php:7.2-fpm

RUN apt-get update && apt-get install -y \
        libfreetype6-dev \
        libjpeg-dev \
        libmcrypt-dev \
        libpng-dev \
        libicu-dev \
        libpq-dev \
        libxpm-dev \
        libwebp-dev \
        libzip-dev \
        zip \
        vim \
        git \
        libxml2-dev \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && docker-php-ext-install pdo_mysql \
    && docker-php-ext-install mysqli \
    && docker-php-ext-install dom \
    && docker-php-ext-configure zip --with-libzip \
    && docker-php-ext-install zip \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && docker-php-ext-install gettext \
#             intl \
#             zip \
#             pgsql \
#             pdo_pgsql
#\
#    && docker-php-ext-install -j$(nproc) mongodb
#\
    && docker-php-ext-install -j$(nproc) exif \
    && docker-php-ext-configure gd \
        --with-gd \
        --with-freetype-dir=/usr/include/ \
        --with-jpeg-dir=/usr/include/ \
        --with-xpm-dir=/usr/lib/x86_64-linux-gnu/ \
        --with-webp-dir \
    && docker-php-ext-install gd \
    && docker-php-ext-enable gd



#RUN apt-get update && apt-get install -y libpq-dev && docker-php-ext-install pdo pdo_pgsql


RUN pecl install mongodb
RUN docker-php-ext-install bcmath
RUN echo "extension=mongodb.so" >> /usr/local/etc/php/conf.d/mongodb.ini

#RUN echo 'xdebug.remote_host="192.168.77.1"' >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini; \
#    echo 'xdebug.remote_enable=1' >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini; \
#    echo 'xdebug.idekey="PHPSTORM"' >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini; \
#    echo 'xdebug.remote_autostart=1' >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini; \
#    echo 'xdebug.remote_connect_back=0' >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini; \
#    echo 'xdebug.remote_handler=dbgp' >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini; \
#    echo 'xdebug.profiler_enable=0' >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini; \
#    echo 'xdebug.remote_port=9000' >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
#    echo "\n" >> /usr/local/etc/php/conf.d/docker-php-ext-pdo_mysql.ini \
#    echo 'pdo_mysql.default_socket="/tmp/mysql.sock"' >> /usr/local/etc/php/conf.d/docker-php-ext-pdo_mysql.ini

RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" && \
    php composer-setup.php --version=1.10.13 && \
    php -r "unlink('composer-setup.php');" && \
    mv composer.phar /usr/local/bin/composer

#RUN apt-get update && \
#    apt-get install -y vim

RUN useradd -m --uid=1000 szczepan
RUN chown szczepan /var/www
USER szczepan

WORKDIR /var/www
