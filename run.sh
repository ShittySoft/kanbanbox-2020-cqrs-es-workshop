#!/usr/bin/env bash

php vendor/bin/psalm --show-info=false
php -S 0.0.0.0:8080 -t public
