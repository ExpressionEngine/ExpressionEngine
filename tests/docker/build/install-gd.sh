#!/bin/bash

source ~/.phpbrew/bashrc

# TODO: Loop
phpbrew switch php-5.4.45
phpbrew ext install gd

phpbrew switch php-5.5.33
phpbrew ext install gd

phpbrew switch php-5.6.19
phpbrew ext install gd

phpbrew switch php-7.0.4
phpbrew ext install gd
