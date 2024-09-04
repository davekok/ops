CONREG=gitlab.true.nl

.PHONY: all
all: package install

.PHONY: package
package: gitops.tar.gz

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

/usr/local/bin/gitops: gitops
	sudo install -o root -g root -m 755 -T $< $@

gitops: $(wildcard src/*.php)
	phar pack -c gz -f $@.phar -a 'gitops' -s stub.php $^
	mv $@.phar $@
	chmod +x $@

gitops.tar.gz: gitops
	tar -czf $@ gitops
