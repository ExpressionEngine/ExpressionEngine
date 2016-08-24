#!/bin/bash

cp /app/tests/docker/runtests.sh .
chmod 755 runtests.sh
./runtests.sh $@
