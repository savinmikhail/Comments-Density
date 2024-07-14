.PHONY: test, check, run, base

test:
	export XDEBUG_MODE=coverage && vendor/bin/phpunit --coverage-html coverage

check:
	php ./vendor/bin/grumphp run

run:
	php ./bin/comments_density analyze:comments --storage tree

base:
	php ./bin/comments_density generate:baseline --storage tree