#!/bin/bash

if [ -z $1 ]; then
	echo "Usage: $0 <tag_name>"
	exit 1
fi

TAG=$1
echo "Creating Git tag: $TAG"
git tag -af "$TAG"

BASENAME="$(basename $(pwd))"
FILENAME=$BASENAME/$BASENAME"_"$TAG".tgz"
echo "Creating release file: $FILENAME"
cd ..
tar -czf $FILENAME \
  --exclude-vcs \
  --exclude-from="$BASENAME/.gitignore" \
  --exclude='nbproject' \
  --exclude='.gitmodules' \
  --exclude='*.sh' \
  --exclude='*.tar' \
  --exclude='*.tgz' \
  $BASENAME

