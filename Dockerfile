FROM ubuntu:20.04

# ENV TZ=Asia/Seoul
# export TZ='Asia/Seoul'
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

RUN apt update \
    && apt -y install software-properties-common

RUN add-apt-repository ppa:ondrej/php

RUN apt -y install php8.0-fpm \
        php8.0-zip \
        php8.0-mbstring \
        php8.0-xml \
        php8.0-curl \
        php8.0-ftp \
        php8.0-fileinfo \
        php8.0-gd \
        php8.0-gettext \
        php8.0-intl \
        php8.0-exif \
        php8.0-mysqli \
        php8.0-mysql \
        nginx \
        vim \
        curl \
        npm

RUN apt remove composer \
    && curl -s https://getcomposer.org/installer | php \
    && mv composer.phar /usr/local/bin/composer \
    && composer global require laravel/installer \
    && echo export PATH="$PATH:$HOME/.config/composer/vendor/bin" >> ~/.bashrc \
    && /bin/bash -c 'source ~/.bashrc'

WORKDIR /home

RUN composer create-project --prefer-dist laravel/laravel laravel8 \
    && chown -R www-data laravel8

ENTRYPOINT service php8.0-fpm start \
    && service nginx start && bash

# laravel new laravel8
# chown -R www-data laravel8
# service php8.0-fpm start
# service nginx start
