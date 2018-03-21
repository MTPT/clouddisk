#!/bin/bash

echo "Waiting for Nextcloud servers to become available"
until curl --silent http://ui-regression-php-master > /dev/null && curl --silent http://ui-regression-php > /dev/null
do
    sleep 2
done

node runTests.js
