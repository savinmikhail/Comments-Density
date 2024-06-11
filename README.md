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
</p>

<h1 align="center">Comments Density Analyzer</h1>

<p align="center">A tool to analyze the comment density and quality in PHP source code files, helping to maintain and improve code documentation quality.</p>

## Features

- **Multiple Comment Types**: Supports identification and analysis of several comment types including regular, 
docblocks, TODOs, FIXMEs, and license information.
- **Detailed Reporting**: Quickly find code spots where changes might be necessary.
- **Quality Check**: Set up a configuration file, and if thresholds aren't met, the exit code will be returned with the report.
- **Configurable Reports**:  Get results in either console or HTML file.
- **Pre-commit hook**: Validate only the files that are about to be committed.

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
php vendor/bin/comments_density analyze:comments
```

### Configuration

On installation, you can allow plugin to create its configuration file.
Customize your analysis by editing a comments_density.yaml configuration file:

```yaml
directories:
  - "Comments-Density/src"
  - "Comments-Density/vendor"
exclude:
  - "Comments-Density/src/Comments"
thresholds:
  docBlock: 90
  regular: 5
  todo: 5
  fixme: 5
  missingDocBlock: 10
  Com/LoC: 0.1
  CDS: 0.1
output:
  type: "console" #  "console" or 'html'
  file: "output.html" # file path for HTML output
```
## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This library is released under the [MIT license](LICENSE).

___
    