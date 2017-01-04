.PHONY: build orm_build orm_migrate deps lint
build: orm_build
orm_build:
	./vendor/bin/propel config:convert
	./vendor/bin/propel model:build
	./vendor/bin/propel sql:build

orm_migrate: orm_build
	./vendor/bin/propel diff
	./vendor/bin/propel migrate

deps:
	composer install

lint:
	./vendor/bin/phpcs --standard=lint_ruleset.xml .
