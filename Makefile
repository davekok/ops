CONREG=ghcr.io

.PHONY: all
all: package install

.PHONY: package
package: ops.tar.gz

.PHONY: install
install: /usr/local/bin/ops

.PHONY: login
login:
	buildah login ${CONREG}

.PHONY: build
build:
	buildah bud -t ${CONREG}/davekok/ops:1.0.0 -f ops.containerfile .

.PHONY: push
push:
	buildah push ${CONREG}/davekok/ops:1.0.0

/usr/local/bin/ops: ops
	sudo install -o root -g root -m 755 -T $< $@

ops: $(wildcard src/*.php)
	phar pack -c gz -f $@.phar -a 'ops' -s stub.php $^
	mv $@.phar $@
	chmod +x $@

ops.tar.gz: ops
	tar -czf $@ ops
