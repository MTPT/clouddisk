#!/bin/bash

./node_modules/.bin/mocha test/installSpec.js --timeout 10000
./node_modules/.bin/mocha test/loginSpec.js --timeout 10000
./node_modules/.bin/mocha test/publicSpec.js --timeout 5000

