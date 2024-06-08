<p align="center">
    <a href="https://scrutinizer-ci.com/g/savinmikhail/Comments-Density/?branch=main">
        <img src="https://scrutinizer-ci.com/g/savinmikhail/Comments-Density/badges/quality-score.png?b=main" alt="Quality Score">
    </a>
</p>

# Comments Density

CommentDensityAnalyzer is a tool designed to analyze the comment density and quality in source code files in php. 
It helps maintain and improve the quality of code documentation by evaluating different types of comments and providing 
insights into their effectiveness and appropriateness.
We can say, that licences in the code files doesn't matter. 
But the commented out code does. And lack of docblock comments to classes or methods too


At this moment works only for php files.

## Features

- **Multiple Comment Types**: Supports identification and analysis of several comment types including regular, 
docblocks, TODOs, FIXMEs, and license information.
- **Detailed Reporting**: Helps quickly find code spots where changes might be neccessary.
- **Quality Check**: You can set up configuration file, and if the thresholds won't pass, the exit code will be returned 
with the report
- **Configurable Reports**: You can get results whether in console or html file.
- **Pre-commit hook**: to validate only those files, that are about to be commited.

### Output Example 
![Output Example](./example_for_readme.png)

### Installation

To install CommentDensityAnalyzer, run the following command in your terminal:

```bash
composer require --dev savinmikhail/comments-density
```

### Usage
```bash
php vendor/bin/comments_density analyze:comments
```

### Configuration

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
All the thresholds are not required, but the directory is.
