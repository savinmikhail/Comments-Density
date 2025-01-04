<p align="center">
    <a href="https://scrutinizer-ci.com/g/savinmikhail/Comments-Density/?branch=main">
        <img src="https://scrutinizer-ci.com/g/savinmikhail/Comments-Density/badges/quality-score.png?b=main" alt="Quality Score">
    </a>
    <a href="https://scrutinizer-ci.com/g/savinmikhail/Comments-Density/?branch=main">
        <img src="https://scrutinizer-ci.com/g/savinmikhail/Comments-Density/badges/coverage.png?b=main" alt="Code Coverage">
    </a>
    <a href="https://scrutinizer-ci.com/g/savinmikhail/Comments-Density/?branch=main">
        <img src="https://scrutinizer-ci.com/g/savinmikhail/Comments-Density/badges/build.png?b=main" alt="Build status">
    </a>
    <a href="https://dashboard.stryker-mutator.io/reports/github.com/savinmikhail/Comments-Density/main">
        <img src="https://img.shields.io/endpoint?style=flat&amp;url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fsavinmikhail%2FComments-Density%2Fmain" alt="Mutation testing badge">
    </a>
</p>

# Comments Density Analyzer

A tool to analyze the comment density and quality in PHP source code files

You might want to use it to control in CI/CD spreading of todos and fixmes in the codebase.

Or you might want to spot simple (regular) comments, which might be there to explain some shitty code or be the commented out code

Or you might want to enforce some docblocks (I worked in companies, where each class and method were required to have docblock explaining purpose et al.)

## Features

- **Multiple Comment Types**: Supports identification and analysis of several comment types including regular, 
docblocks, TODOs, FIXMEs, and license information.
- **Detailed Reporting**: Quickly find code spots where changes might be necessary.
- **Quality Check**: Set up a configuration file, and if thresholds aren't met, the exit code will be returned with the report.
- **Configurable Reports**:  Get results in either console or HTML file.
- **Baseline**:  Filter collected comments against a baseline to ignore old technical debt and focus on new issues.

### Output Example 
![Output Example](./example_for_readme.png)

### Installation

To install Comment Density Analyzer, run the following command in your terminal:

```bash
composer require --dev savinmikhail/comments-density
```

### Usage

Analyze the comment density in your PHP files with:

```bash
php vendor/bin/comments_density analyze
```

Generate baseline with:
```bash
php vendor/bin/comments_density baseline
```

### Configuration

On installation, you can allow plugin to create its configuration file.
Customize your analysis by editing a comments_density.php configuration file:

```php
<?php

declare(strict_types=1);

use SavinMikhail\CommentsDensity\AnalyzeComments\Config\DTO\Config;
use SavinMikhail\CommentsDensity\AnalyzeComments\Config\DTO\ConsoleOutputDTO;
use SavinMikhail\CommentsDensity\AnalyzeComments\Config\DTO\MissingDocblockConfigDTO;

return new Config(
    output: ConsoleOutputDTO::create(),
    directories: [
        'src',
    ],
    thresholds: [
        'regular' => 0,
        'todo' => 0,
        'fixme' => 0,
    ],
    exclude: [],
);

```

## Acknowledgments

This project was inspired by Yegor Bugayenko. See [opensource ideas](https://gist.github.com/yegor256/5bddb12ce88a6cba44d578c567031508).

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This library is released under the [MIT license](LICENSE).

___
    