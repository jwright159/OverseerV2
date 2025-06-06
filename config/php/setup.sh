#!/bin/bash

composer install
/usr/sbin/apache2ctl -D FOREGROUND
