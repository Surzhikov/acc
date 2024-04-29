FROM php:8.2-fpm-alpine


# Добавляем репозитории 'main' и 'community'
RUN echo "http://dl-cdn.alpinelinux.org/alpine/edge/main" >> /etc/apk/repositories \
    && echo "http://dl-cdn.alpinelinux.org/alpine/edge/community" >> /etc/apk/repositories \
    && echo "http://dl-cdn.alpinelinux.org/alpine/edge/testing" >> /etc/apk/repositories

# Update the package repository
RUN apk update

# User and group
RUN addgroup -g 1000 app && adduser -u 1000 -G app -s /bin/sh -D app

# Adding PHP extensions
RUN apk add libjpeg-turbo-dev libpng-dev libwebp-dev freetype-dev libzip-dev curl-dev nss ca-certificates 
RUN docker-php-ext-configure gd --with-jpeg --with-webp --with-freetype
RUN docker-php-ext-install gd zip pcntl pdo pdo_mysql curl


# Install necessary packages and dependencies
RUN apk add --no-cache imagemagick imagemagick-dev autoconf g++ make && \
    pecl install imagick && \
    docker-php-ext-enable imagick


# Install composer
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

# NodeJs
RUN apk add nodejs yarn

# Supervisor
RUN apk add supervisor supercronic htop bash


RUN apk add --no-cache ffmpeg x264 x264-libs x264-dev


USER app
WORKDIR /var/www




#ENTRYPOINT ["tail", "-f", "/dev/null"]
#ENTRYPOINT ["supervisord"]
ENTRYPOINT "/entrypoint.sh"
