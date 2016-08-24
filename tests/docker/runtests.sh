#!/bin/bash

while [[ $# > 0 ]]
	do
	key="$1"

	PHP_VERSION="7.0.4"

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

pushd /var/www/html/
	tar xf ee.tar > /dev/null
	# TODO automatically replace app_version?
	cp tests/docker/config.php system/user/config/
	cp tests/docker/config.rb tests/rspec/
	cp tests/circleci/license.key system/user/config/
	chmod 666 system/user/config/config.php
	chmod -R 777 system/user/cache
	chmod -R 777 system/user/templates
	chmod -R 777 system/user/language
	chmod 777 tests/rspec/support/tmp
	chmod -R 777 tests/rspec/support/file-sync/uploads
	chmod -R 777 images
	chmod -R 777 themes/ee/site_themes/agile_records/images/uploads
popd

rm /app/ee.tar

source ~/.phpbrew/bashrc
echo "Loading PHP ${PHP_VERSION} ..."
phpbrew use php-$PHP_VERSION
echo "LoadModule php7_module /usr/lib/apache2/modules/libphp7.0.4.so" > /etc/apache2/mods-available/php5.load

CORE_COUNT=`shell grep -c ^processor /proc/cpuinfo 2>/dev/null || sysctl -n hw.ncpu`

if [ "${COMMAND}" == "lint" ]; then
	pushd /var/www/html/
		find -L . -name '*.php' -not -path "./system/ee/EllisLab/Tests/vendor/*" -not -path "./node_modules/*" -not -name "config_tmpl.php" | parallel -j $CORE_COUNT php -l {}
	popd
	exit
fi

service apache2 start > /dev/null
service mysql start > /dev/null

if [ "${COMMAND}" == "test" ]; then

	if [ "${FILES}" == "" ]; then
		FILES="tests/**/*.rb"
	fi

	# TODO: Run PHP lint and PHP Unit first, bail out if they fail

	mysql -u root -e 'CREATE DATABASE `ee-test`;' > /dev/null
	mysql -u root -e 'SET sql_mode=STRICT_ALL_TABLES;'

	source /usr/local/rvm/scripts/rvm

	pushd /var/www/html/tests/rspec
		bundle install --no-deployment --path=~/gems/ > /dev/null
		xvfb-run -a bundle exec rspec -c -fd $FILES
	popd

	mkdir /app/tests/rspec/screenshots
	pushd /app/tests/rspec/screenshots
		#rm -rf *
		cp -r /var/www/html/tests/rspec/screenshots/* .
	popd
fi
