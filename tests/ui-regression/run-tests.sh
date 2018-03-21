#!/bin/bash

echo "Waiting for Nextcloud servers to become available"
until curl --silent http://acceptance-ui-php-master > /dev/null && curl --silent http://acceptance-ui-php > /dev/null
do
    sleep 2
done

node runTests.js
