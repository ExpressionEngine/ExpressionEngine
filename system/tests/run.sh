#!/bin/bash

ln -sf "/home/vagrant/jenkins/workspace/${JOB_NAME}" "/home/vagrant/jenkins/workspace/webroot"

# Copy config.php and database.php from shared folder and set permissions
cp /vagrant/files/{config.php,database.php} system/expressionengine/config/
chmod 666 system/expressionengine/config/{config.php,database.php}
chmod -R 777 system/expressionengine/cache
chmod -R 777 system/expressionengine/templates
chmod -R 777 images

cd system/tests/rspec
rm screenshots/*

source /home/vagrant/.bashrc
source /home/vagrant/.rvm/scripts/rvm
rvm use 2.1.1
bundle install
DISPLAY=localhost:1.0

# Use PHPbrew to switch PHP versions (have to manually do it for Apache)
for phpversion in "5.5.10" "5.4.26" "5.3.10"
do
	printf "\n\nNow using PHP ${phpversion}\n\n"
	phpbrew use $php_version
	echo "LoadModule php5_module /usr/lib/apache2/modules/libphp${phpversion}.so" > /etc/apache2/mods-available/php5.load
	sudo service apache2 restart
	xvfb-run rspec -fd -c
done
