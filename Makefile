.PHONY: package
package: ops

.PHONY: install
install: /usr/local/bin/ops

.PHONY: build
build:
	buildah bud -t ghcr.io/davekok/ops:1.0.0 -f ops.containerfile .

.PHONY: push
push:
	buildah push ghcr.io/davekok/ops:1.0.0

/usr/local/bin/ops: ops
	sudo install -o root -g root -m 755 -T $< $@

ops: $(wildcard src/*.php)
	phar pack -f $@.phar -a 'ops' -s stub.php $^
	mv $@.phar $@
	chmod +x $@
