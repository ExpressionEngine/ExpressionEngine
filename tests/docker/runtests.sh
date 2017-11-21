#!/bin/bash

# Container's nameservers keep getting reset, putting this here
# until we figure out how to fix
echo "domain local" > /etc/resolv.conf
echo "nameserver 8.8.8.8" >> /etc/resolv.conf
echo "nameserver 8.8.4.4" >> /etc/resolv.conf

while [[ $# > 0 ]]
	do
	key="$1"

	PHP_VERSION="7.1.0"

	case $key in
		-p|--php)
			PHP_VERSION="$2"
			shift
		;;
		test)
			COMMAND="test"
			FILES="$2"
			shift
		;;
		*)
			COMMAND="$1"
		;;
	esac
	shift # past argument or value
done

cp /app/ee.tar /var/www/html/

pushd /var/www/html/ > /dev/null
	tar xmf ee.tar > /dev/null
	# TODO automatically replace app_version?
	cp tests/docker/config.php system/user/config/
	cp tests/docker/config.rb tests/rspec/
	cp tests/docker/EllisLabUpdate.pub system/ee/EllisLab/ExpressionEngine
	cp tests/circleci/license.key system/user/config/
	chmod 666 system/user/config/config.php
	chmod -R 777 system/user/cache
	chmod -R 777 system/user/templates
	chmod -R 777 system/user/language
	chmod 777 tests/rspec/support/tmp
	chmod -R 777 tests/rspec/support/file-sync/uploads
	chmod -R 777 images
popd > /dev/null

rm /app/ee.tar

PHP_VERSION_ASPLODE=(${PHP_VERSION//./ })
PHP_MAJOR_VERSION=${PHP_VERSION_ASPLODE[0]}

source ~/.phpbrew/bashrc
echo "Loading PHP ${PHP_VERSION} ..."
phpbrew use php-$PHP_VERSION
# Prevent other PHPs from loading
echo "" > /etc/apache2/mods-available/php5.load
echo "" > /etc/apache2/mods-available/php7.load
echo "" > /etc/apache2/mods-available/php7.0.load
echo "LoadModule php${PHP_MAJOR_VERSION}_module /usr/lib/apache2/modules/libphp${PHP_VERSION}.so" > /etc/apache2/mods-available/php5.load

if [ "${COMMAND}" == "lint" ]; then
	CORE_COUNT=`shell grep -c ^processor /proc/cpuinfo 2>/dev/null || sysctl -n hw.ncpu`
	pushd /var/www/html/
		find -L . -name '*.php' -not -path "./system/ee/EllisLab/Tests/vendor/*" -not -path "./node_modules/*" -not -name "config_tmpl.php" | parallel -j $CORE_COUNT php -l {}
	popd
	exit
fi

# https://github.com/docker/for-linux/issues/72
find /var/lib/mysql -type f -exec touch {} \;

service apache2 start > /dev/null
service mysql start > /dev/null

if [ "${COMMAND}" == "test" ]; then

	if [ "${FILES}" == "" ]; then
		FILES="tests/**/*.rb"
	fi

	# TODO: Run PHP lint and PHP Unit first, bail out if they fail

	mysql -u root -e 'CREATE DATABASE `ee-test`;' > /dev/null
	#mysql -u root -e 'SET GLOBAL sql_mode="ONLY_FULL_GROUP_BY,STRICT_ALL_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION";'

	source /usr/local/rvm/scripts/rvm

	pushd /var/www/html/tests/rspec > /dev/null
		bundle install --no-deployment --path=~/gems/ > /dev/null
		xvfb-run -a bundle exec rspec -c -fd $FILES
	popd > /dev/null

	if [ -d "/var/www/html/tests/rspec/screenshots" ]; then
		mkdir -p /app/tests/rspec/screenshots
		pushd /app/tests/rspec/screenshots > /dev/null
			#rm -rf *
			cp -r /var/www/html/tests/rspec/screenshots/* .
		popd > /dev/null
	fi
fi
