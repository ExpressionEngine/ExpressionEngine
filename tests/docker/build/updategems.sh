#!/bin/bash

source /usr/local/rvm/scripts/rvm

pushd /app/tests/rspec
	bundle install --no-deployment --path=~/gems/
popd
