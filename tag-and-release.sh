#!/bin/bash

if [ -z $1 ]; then
	echo "Usage: $0 <tag_name>"
	exit 1
fi

TAG=$1
echo "Creating Git tag: $TAG"
git tag -af "$TAG"

FILENAME="webdb_$TAG.tgz"
echo "Creating release file: $FILENAME"
tar -czf $FILENAME \
  --exclude-vcs \
  --exclude-from='.gitignore' \
  --exclude='nbproject' \
  --exclude='*.sh' \
  --exclude='*.tar' \
  --exclude='*.tgz' \
  *

