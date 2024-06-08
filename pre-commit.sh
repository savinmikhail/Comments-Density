#!/bin/sh

# Get the list of PHP files staged for commit
FILES=$(git diff --cached --name-only --diff-filter=ACM | grep '\.php$')

if [ -z "$FILES" ]; then
  echo "No PHP files to analyze."
  exit 0
fi

for FILE in $FILES; do
  echo "Analyzing $FILE..."
  php ../../vendor/bin/comments_density analyze:file $FILE
  if [ $? -ne 0 ]; then
    echo "Comment density check failed for $FILE"
    exit 1
  fi
done

echo "All files passed comment density check."
exit 0
