.PHONY: build
build: docker/build deps

.PHONY: deps
deps: composer/install

.PHONY: test
test: composer/test

.PHONY: clean
clean: docker/clean

########################################################################################################################

.PHONY: docker/build
docker/build:
	@docker build -t ticdenis/php-liccheck --target development .

.PHONY: docker/clean
docker/clean:
	@docker rmi ticdenis/php-liccheck

.PHONY: docker/run/sh
docker/run/sh:
	@docker run --rm -it -v $(PWD):/app --user $(id -u):$(id -g) --entrypoint sh \
    		ticdenis/php-liccheck $(command) $(arg)

.PHONY: docker/run/composer
docker/run/composer composer:
	@docker run --rm -it -v $(PWD):/app --user $(id -u):$(id -g) --entrypoint composer \
    		ticdenis/php-liccheck $(options) $(arguments)

########################################################################################################################

.PHONY: composer/install
composer/install: options=install

.PHONY: composer/update
composer/update: options=update

.PHONY: composer/require
composer/require: options=require $(package)

.PHONY: composer/test
composer/test: options=test

.PHONY: composer
composer composer/install composer/update composer/require composer/test: docker/run/composer
