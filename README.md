
<p align="center">
    <a href="https://scrutinizer-ci.com/g/savinmikhail/Comments-Density/?branch=main">
        <img src="https://scrutinizer-ci.com/g/savinmikhail/Comments-Density/badges/quality-score.png?b=main" alt="Quality Score">
    </a>
    <a href="https://scrutinizer-ci.com/g/savinmikhail/Comments-Density/?branch=main">
        <img src="https://scrutinizer-ci.com/g/savinmikhail/Comments-Density/badges/coverage.png?b=main" alt="Code Coverage">
    </a>
    <a href="https://scrutinizer-ci.com/g/savinmikhail/Comments-Density/?branch=main">
        <img src="https://scrutinizer-ci.com/g/savinmikhail/Comments-Density/badges/build.png?b=main" alt="Build Status">
    </a>
    <a href="https://dashboard.stryker-mutator.io/reports/github.com/savinmikhail/Comments-Density/main">
        <img src="https://img.shields.io/endpoint?style=flat&amp;url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fsavinmikhail%2FComments-Density%2Fmain" alt="Mutation Testing Badge">
    </a>
</p>

# Comments Density Analyzer

The **Comments Density Analyzer** is a tool to help you monitor and manage comments in your codebase.

### Why Use It?

- **Control TODOs and FIXMEs in CI/CD**: Ensure these comments are not proliferating unchecked in your codebase.
- **Spot Problematic Comments**: Identify regular comments explaining "shitty code" or remnants of commented-out code.
- **Enforce Documentation Standards**: Require docblocks for classes and methods to maintain clear, consistent documentation.

All of this is made possible with a powerful **plugin system** (see the documentation for examples).

## Features

- **Multiple Comment Types**: Detect and analyze regular comments, docblocks, TODOs, FIXMEs, and license headers.
- **Plugin Support**: Extend functionality by creating custom plugins via a simple interface.
- **Detailed Reporting**: Quickly identify areas of your code that need attention.
- **Thresholds and Exit Codes**: Set thresholds for comment types and return an exit code when they are exceeded.
- **Configurable Reports**: Output results to the console or as an HTML report.
- **Baseline Support**: Filter out known technical debt using a baseline file and focus on new issues.

### Output Example
![Output Example](./example_for_readme.png)

---

## Installation

Install **Comments Density Analyzer** as a development dependency via Composer:

```bash
composer require --dev savinmikhail/comments-density
```

---

## Usage

Analyze the comments in your PHP files:

```bash
php vendor/bin/comments_density analyze
```

Generate a baseline to ignore existing technical debt:

```bash
php vendor/bin/comments_density baseline
```

---

## Configuration

During installation, the tool can generate a default configuration file. Customize your analysis by editing the `comments_density.php` file:

```php
<?php

declare(strict_types=1);

use SavinMikhail\CommentsDensity\AnalyzeComments\Comments\FixMeComment;
use SavinMikhail\CommentsDensity\AnalyzeComments\Comments\RegularComment;
use SavinMikhail\CommentsDensity\AnalyzeComments\Comments\TodoComment;
use SavinMikhail\CommentsDensity\AnalyzeComments\Config\DTO\Config;

return new Config(
    directories: [
        'src',
    ],
    thresholds: [
        RegularComment::NAME => 0,
        TodoComment::NAME => 0,
        FixMeComment::NAME => 0,
    ],
);
```

---

## Acknowledgments

This project was inspired by Yegor Bugayenko. See [Open Source Ideas](https://gist.github.com/yegor256/5bddb12ce88a6cba44d578c567031508).

---

## Contributing

Contributions are always welcome! Feel free to submit a pull request with improvements or new features.

---

## License

This library is licensed under the [MIT License](LICENSE).  
