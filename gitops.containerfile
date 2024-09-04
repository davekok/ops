FROM php:8.3-cli-bookworm AS phar
COPY stub.php /srv/stub.php
COPY src /srv/src
WORKDIR /srv
RUN sed 's/;phar.readonly = On/phar.readonly = Off/' /usr/local/etc/php/php.ini-production > /usr/local/etc/php/php.ini \
 && phar pack -f gitops.phar -a 'gitops' -s stub.php $(ls -1 src/*.php)

FROM scratch
COPY --from=phar --chown=0:0 --chmod=755 /srv/gitops.phar /usr/local/bin/gitops
