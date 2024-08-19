.PHONY: test, check, run, base

test:
	export XDEBUG_MODE=coverage && vendor/bin/phpunit --coverage-html coverage

run:
	php ./bin/comments_density analyze:comments

base:
	php ./bin/comments_density generate:baseline