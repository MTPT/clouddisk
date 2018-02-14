#!/bin/bash

export PATH=node_modules/.bin:$PATH
#trap "killall php" SIGINT
#trap "killall php" EXIT

pkill -9 php

cleaninstall() {
    rm -r data/*
    rm config/config.php
}
BASEBRANCH=master
[ ! -d $ORIGINPATH ] && git worktree add $ORIGINPATH $BASEBRANCH

(
    cd $ORIGINPATH
    git submodule update --init
    cleaninstall
    php -S 0.0.0.0:8080 & 2>&1 > base.log
)

(
    cd $REPOPATH
    git submodule update --init
    cleaninstall
    php -S 0.0.0.0:8081 & 2>&1 > change.log
)


#./node_modules/.bin/mocha test/installSpec.js --timeout 10000
#./node_modules/.bin/mocha test/loginSpec.js --timeout 10000

