#!/bin/bash

echo "Waiting for Nextcloud servers to become available"
until curl -I --silent http://acceptance-ui-php:8080 && curl -I --silent http://acceptance-ui-php:8081
do
    sleep 2
done

./node_modules/.bin/mocha test/installSpec.js --timeout 20000
./node_modules/.bin/mocha test/loginSpec.js --timeout 20000
./node_modules/.bin/mocha test/publicSpec.js --timeout 20000

