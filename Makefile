.PHONY: test, check, debug

test:
	export XDEBUG_MODE=coverage && vendor/bin/phpunit --coverage-html coverage

check:
	php ./vendor/bin/grumphp run
