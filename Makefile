.PHONY: lint
lint:
	./vendor/bin/phpcs --standard=lint_ruleset.xml .
