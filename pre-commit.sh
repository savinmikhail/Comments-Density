#!/bin/sh

# Get the list of PHP files staged for commit
FILES=$(git diff --cached --name-only --diff-filter=ACM | grep '\.php$')

if [ -z "$FILES" ]; then
  echo "No PHP files to analyze."
  exit 0
fi

php vendor/bin/comments_density analyze:files $FILES
if [ $? -ne 0 ]; then
  exit 1
fi

exit 0
