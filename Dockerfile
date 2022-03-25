FROM php:8.0-fpm

# Set working directory
WORKDIR /var/www

RUN umask 0000

# Add docker php ext repo
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

# Install php extensions
RUN chmod +x /usr/local/bin/install-php-extensions && sync && \
    install-php-extensions mbstring pdo_mysql zip exif pcntl gd memcached

RUN apt-get update -y && \
    apt-get install -y build-essential libfuse-dev libcurl4-openssl-dev libxml2-dev pkg-config libssl-dev mime-support automake libtool wget tar git unzip kmod
RUN apt-get install lsb-release -y  && apt-get install zip -y && apt-get install vim -y

# Install dependencies
RUN apt-get update && apt-get install -y \
    build-essential \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    locales \
    zip \
    jpegoptim optipng pngquant gifsicle \
    unzip \
    git \
    curl \
    lua-zlib-dev \
    libmemcached-dev \
    nginx

RUN docker-php-ext-configure gd --enable-gd --with-freetype --with-jpeg 
RUN cd /usr/src/php/ext/gd && make
RUN docker-php-ext-install -j$(nproc) gd

#install calender gregorian
RUN docker-php-ext-install calendar

# Memory Limit
RUN echo "memory_limit=2048M" > $PHP_INI_DIR/conf.d/memory-limit.ini

# Install supervisor
RUN apt-get install -y supervisor

# Install composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Add user for laravel application
RUN groupadd -g 1000 www
RUN useradd -u 1000 -ms /bin/bash -g www www

# Copy code to /var/www
COPY --chown=root:root . /var/www

# add root to www group
RUN chmod -R ug+w /var/www/storage

# Copy nginx/php/supervisor configs
RUN cp docker/supervisor.conf /etc/supervisord.conf
RUN cp docker/php.ini /usr/local/etc/php/conf.d/app.ini
RUN cp docker/nginx.conf /etc/nginx/sites-enabled/default

# PHP Error Log Files
RUN mkdir /var/log/php
RUN touch /var/log/php/errors.log && chmod 777 /var/log/php/errors.log

ARG CONSUL_TOKEN

RUN curl -s --header "X-Consul-Token:$CONSUL_TOKEN" -XGET https://consul.sudahdigital.com/v1/kv/daily_report/dailyreport.sudahdigital.com?raw=true > .env
RUN chown root:root .env

## Install AWS CLI
RUN apt-get update && \
    apt-get install -y \
        python3 \
        python3-pip \
        python3-setuptools \
        groff \
        less \
    && pip3 install --upgrade pip \
    && apt-get clean

RUN pip3 --no-cache-dir install --upgrade awscli

ARG AWS_KEY
ARG AWS_SECRET_KEY

## Install S3 Fuse
RUN rm -rf /usr/src/s3fs-fuse
RUN git clone https://github.com/s3fs-fuse/s3fs-fuse/ /usr/src/s3fs-fuse
WORKDIR /usr/src/s3fs-fuse 
RUN ./autogen.sh && ./configure && make && make install

ENV S3_MOUNT_DIRECTORY=/var/www/storage/app/public
ENV S3_BUCKET_NAME=sudahdigital

## S3fs-fuse credential config
RUN touch /root/.passwd-s3fs
RUN echo $AWS_KEY:$AWS_SECRET_KEY > /root/.passwd-s3fs && \
    chmod 600 /root/.passwd-s3fs

# Deployment steps
RUN composer install --optimize-autoloader --no-dev
RUN chmod +x /var/www/docker/run.sh

RUN chown -R root:root vendor
RUN chown -R root:root storage/logs/
RUN chmod -R 777 /var/www/storage/framework/sessions
RUN chmod -R 777 /var/www/storage/framework/views
RUN chmod -R 777 /var/www/storage/framework/cache
RUN chmod -R 777 /var/www/storage/framework/laravel-excel

EXPOSE 443
ENTRYPOINT ["/var/www/docker/run.sh"]