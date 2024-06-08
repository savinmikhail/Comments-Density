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
- **Detailed Reporting**: Provides reports on comment density and quality, which can be used to guide code reviews and 
maintainability assessments.
- **Quality Check**: You can set up configuration file, and if the thresholds won't pass, the exit code will be returned 
with the report

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
directory: "src"
thresholds:
  docBlock: 10
  regular: 20
  todo: 5
  fixme: 5
  Com/LoC: 0.76
```
All the thresholds are not required, but the directory is.

### Output Example 
![Output Example](./example_for_readme.png)
