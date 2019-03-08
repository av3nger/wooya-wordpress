FROM wordpress:php7.3

RUN apt-get update && apt-get install -y \
    libfreetype6-dev libjpeg62-turbo-dev libpng-dev nano ssmtp \
    && rm -rf /var/lib/apt/lists/*

RUN rm /etc/ssmtp/ssmtp.conf
ADD ./ssmtp.conf /etc/ssmtp/
ADD ./php.ini /usr/local/etc/php/

RUN pecl install xdebug-beta \
    && docker-php-ext-enable xdebug

RUN curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar \
    && chmod +x wp-cli.phar \
    && mv wp-cli.phar /usr/local/bin/wp
