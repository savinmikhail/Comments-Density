.PHONY: test, check, run

test:
	export XDEBUG_MODE=coverage && vendor/bin/phpunit --coverage-html coverage

check:
	php ./vendor/bin/grumphp run

run:
	php src/AnalyzeCommentCommand.php analyze:comments tests/sample.php
