FROM hyperf/hyperf:8.1-alpine-v3.16-swoole-v5.0

WORKDIR /usr/local/src

COPY ./composer.* /usr/local/src/
RUN composer install --prefer-dist
COPY . /usr/local/src/

ENTRYPOINT [ "/bin/sh" ]