FROM php:8.3-fpm

# Definir el usuario como argumento
ARG user=www

# Copiar los archivos de configuración de composer
COPY ./composer.lock ./composer.json /var/www/

# Cambiar el directorio de trabajo
WORKDIR /var/www

# Cambiar la propiedad del directorio de trabajo
RUN chown -R $user:$user /var/www

# Instalar dependencias
RUN apt-get update && apt-get install -y \
    build-essential \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    locales \
    zip \
    jpegoptim optipng pngquant gifsicle \
    vim \
    unzip \
    git \
    curl \
    libonig-dev \
    libzip-dev \
    libgd-dev \
    libicu-dev 

# Limpiar la cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Instalar extensiones de PHP
RUN docker-php-ext-install pdo_mysql mbstring zip exif pcntl
RUN docker-php-ext-configure gd --with-external-gd
RUN docker-php-ext-install gd
RUN docker-php-ext-configure intl \
    && docker-php-ext-install intl \
    && docker-php-ext-install mbstring

RUN docker-php-ext-enable intl mbstring

# Instalar composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Agregar el usuario www
RUN groupadd -g 1000 www
RUN useradd -u 1000 -ms /bin/bash -g www www

# Copiar los archivos de la aplicación y cambiar el propietario
COPY --chown=$user:$user . /var/www

# Establecer permisos en el directorio de almacenamiento
RUN mkdir -p /var/www/storage && \
    chown -R $user:$user /var/www/storage && \
    chmod -R 775 /var/www/storage

# Configurar opcache para mejorar el rendimiento
RUN echo "opcache.memory_consumption=128" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini \
    && echo "opcache.interned_strings_buffer=8" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini \
    && echo "opcache.max_accelerated_files=4000" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini \
    && echo "opcache.revalidate_freq=0" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini \
    && echo "opcache.fast_shutdown=1" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini \
    && echo "opcache.enable_cli=0" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini

# Cambiar el usuario
USER $user

# Exponer el puerto 9000 y ejecutar php-fpm
EXPOSE 9000
CMD ["php-fpm"]
