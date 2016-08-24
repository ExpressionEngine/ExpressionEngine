#!/bin/bash

source /usr/local/rvm/scripts/rvm

pushd /var/www/html/tests/rspec
	bundle install --no-deployment --path=~/gems/
popd
