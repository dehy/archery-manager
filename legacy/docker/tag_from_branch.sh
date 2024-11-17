#!/bin/bash

BRANCH=${GITHUB_REF##refs/heads/}
TAG=

if [ "$BRANCH" == "main" ]
then
    TAG="latest"
else
    TAG=$(echo $BRANCH | sed -e "s#/#-#g")
fi

echo $TAG

if [ -z "$TAG" ]
then
    exit 1
fi

exit 0