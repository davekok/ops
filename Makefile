CONREG=ghcr.io

.PHONY: all
all: install

.PHONY: install
install: /usr/local/bin/gitops

.PHONY: login
login:
	buildah login ${CONREG}

.PHONY: build
build:
	buildah bud -t ${CONREG}/davekok/gitops:1.0.0 -f gitops.containerfile .

.PHONY: push
push:
	buildah push ${CONREG}/davekok/gitops:1.0.0

/usr/local/bin/gitops: bin/gitops.phar
	sudo install -o root -g root -m 755 -T $< $@

bin/gitops.phar: $(wildcard src/*.php src/*/*.php)
	phar pack -c gz -f $@ -a 'gitops' -s stub.php $^
	chmod +x $@

.PHONY: test
test: bin/phpunit.phar var/cache/phpunit var/www/phpunit
	 XDEBUG_MODE=coverage php bin/phpunit.phar

bin/phpunit.phar:
	wget -O bin/phpunit.phar https://phar.phpunit.de/phpunit-11.phar
	chmod +x bin/phpunit.phar

var/cache/phpunit:
	mkdir -p var/cache/phpunit

var/www/phpunit:
	mkdir -p var/www/phpunit
