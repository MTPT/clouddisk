#!/bin/bash

cleaninstall() {
    rm -fr data/*
    rm -f config/config.php
}
BASEBRANCH=master
BASEPATH=/tmp/base
REPOPATH=`realpath ../../`
[ ! -d $BASEPATH ] && mkdir -p $BASEPATH

(
    git clone --recursive https://github.com/nextcloud/server.git $BASEPATH
    cd $BASEPATH
    cleaninstall
    php -S 0.0.0.0:8080 & 
)

(
    cd $REPOPATH
    git submodule update --init
    cleaninstall
    php -S 0.0.0.0:8081
)


