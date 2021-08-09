#!/bin/bash

## Description: Ensure default bucket
## Usage: create-bucket

echo ddev-${DDEV_SITENAME}-mc

# TODO: `ddev ssh -s mc` fails, so does a command. Exit code 1. needs debugging.
docker exec ddev-${DDEV_SITENAME}-mc mc config host add --quiet --api s3v4 storage http://storage:8080 minioadmin minioadmin
docker exec ddev-${DDEV_SITENAME}-mc mc mb --quiet storage/mojodata
docker exec ddev-${DDEV_SITENAME}-mc mc policy set download storage/mojodata
# TODO: On install, Drupal doesn't make the `css` or `js` directory correctly and install fails.
docker exec ddev-${DDEV_SITENAME}-mc mc mb --quiet storage/mojodata/css
docker exec ddev-${DDEV_SITENAME}-mc mc mb --quiet storage/mojodata/js

#mc config host add --quiet --api s3v4 storage http://storage:9000 minioadmin minioadmin;
#mc rb --force storage/mojodata;
#mc mb --quiet storage/mojodata;
#mc policy set download storage/mojodata;
