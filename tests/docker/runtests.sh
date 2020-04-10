#!/bin/bash

# Increment this to indicate exit status
STATUS=0

# Container's nameservers keep getting reset, putting this here
# until we figure out how to fix
echo "domain local" > /etc/resolv.conf
echo "nameserver 8.8.8.8" >> /etc/resolv.conf
echo "nameserver 8.8.4.4" >> /etc/resolv.conf

function load_php_version {
	PHP_VERSION_ASPLODE=(${PHP_VERSION//./ })
	PHP_MAJOR_VERSION=${PHP_VERSION_ASPLODE[0]}

	source ~/.phpbrew/bashrc
	echo "Loading PHP ${PHP_VERSION} ..."
	phpbrew use $PHP_VERSION
	# Prevent other PHPs from loading
	echo "" > /etc/apache2/mods-available/php5.load
	echo "" > /etc/apache2/mods-available/php7.load
	echo "" > /etc/apache2/mods-available/php7.0.load
	echo "LoadModule php${PHP_MAJOR_VERSION}_module /usr/lib/apache2/modules/libphp${PHP_VERSION}.so" > /etc/apache2/mods-available/php5.load
}

while [[ $# > 0 ]]
	do
	key="$1"

	# Default PHP version
	# PHP_VERSION="5.6.0"
	#PHP_VERSION="7.2.11"
	PHP_VERSION="7.4.1"

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

load_php_version

function setup_permissions {
	pushd /var/www/html/ > /dev/null
		cp tests/docker/config.php system/user/config/
		cp tests/docker/config.rb tests/rspec/
		cp tests/docker/EllisLabUpdate.pub system/ee/EllisLab/ExpressionEngine
		chmod 666 system/user/config/config.php
		chmod -R 777 system/user/cache
		chmod -R 777 system/user/templates
		chmod -R 777 system/user/language
		chmod 777 tests/rspec/support/tmp
		chmod -R 777 tests/rspec/support/file-sync/uploads
		chmod -R 777 images
		chmod -R 777 themes/user
		# JS Shim for ES5/ES6 with Capybara
		cp themes/ee/asset/javascript/src/react/react.min.js themes/ee/asset/javascript/src/react/react.min~orig.js
		cat tests/rspec/shim.min.js themes/ee/asset/javascript/src/react/react.min.js > themes/ee/asset/javascript/src/react/react.min-shimmed.js
		mv themes/ee/asset/javascript/src/react/react.min-shimmed.js themes/ee/asset/javascript/src/react/react.min.js
	popd > /dev/null
}

ARTIFACTS_DIR="/app/artifacts/${PHP_VERSION}"
if [ ! -d $ARTIFACTS_DIR ]; then
	mkdir -p $ARTIFACTS_DIR;
fi

function start_apache_mysql {
	# https://github.com/docker/for-linux/issues/72
	find /var/lib/mysql -type f -exec touch {} \;

	service apache2 start > /dev/null
	service mysql start > /dev/null
}

function lint_php_files {
	pushd /var/www/html/ > /dev/null
		for file in `find -L . -type f -name "*.php" -not -path "./system/ee/EllisLab/Tests/vendor/*" -not -path "./node_modules/*" -not -name "config_tmpl.php"`; do
			RESULTS=`php -l $file`

			if [ "$RESULTS" != "No syntax errors detected in $file" ] ; then
				echo $RESULTS | tee -a $ARTIFACTS_DIR/phplint.txt
				((STATUS+=${PIPESTATUS[0]}))
			fi
		done
	popd > /dev/null

	# Bail early if PHP Linting failed
	if [ "${STATUS}" -gt "0" ]; then
		exit $STATUS
	fi
}

function run_unit_tests {
	pushd /var/www/html/ > /dev/null
		cp tests/docker/config.php system/user/config/
	popd

	# PHPUnit tests
	pushd /var/www/html/system/ee/EllisLab/Tests/
		printf "Running PHPUnit tests\n\n"
		composer install --prefer-source --no-interaction
		vendor/bin/phpunit ExpressionEngine/ | tee $ARTIFACTS_DIR/phpunit.txt

		# Save our exit status code
		((STATUS+=${PIPESTATUS[0]}))

		# Remove CLI colors
		sed -i -r "s/\x1B\[([0-9]{1,2}(;[0-9]{1,2})*)?m//g" $ARTIFACTS_DIR/phpunit.txt
	popd

	# Updater microapp unit tests
	pushd /var/www/html/system/ee/installer/updater/EllisLab/Tests/
		printf "Running PHPUnit tests\n\n"
		composer install --prefer-source --no-interaction
		vendor/bin/phpunit ExpressionEngine/ | tee $ARTIFACTS_DIR/phpunit-updater.txt

		# Save our exit status code
		((STATUS+=${PIPESTATUS[0]}))

		# Remove CLI colors
		sed -i -r "s/\x1B\[([0-9]{1,2}(;[0-9]{1,2})*)?m//g" $ARTIFACTS_DIR/phpunit-updater.txt
	popd

	# Bail early if PHP Unit tests failed
	if [ "${STATUS}" -gt "0" ]; then
		exit $STATUS
	fi
}

function run_rspec_tests {
	if [ "${FILES}" == "" ]; then
		FILES="tests/**/*.rb"
	fi

	mysql -u root -e 'CREATE DATABASE `ee-test`;' > /dev/null
	mysql -u root -e 'SET GLOBAL sql_mode="ONLY_FULL_GROUP_BY,STRICT_ALL_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION";'

	source /usr/local/rvm/scripts/rvm

	pushd /var/www/html/tests/rspec > /dev/null
		bundle install --no-deployment --path=~/gems/ > /dev/null
		xvfb-run -a bundle exec rspec -c -fd -fh -o screenshots/rspec.html $FILES

		# Append status code for this test
		((STATUS+=$?))
	popd > /dev/null

	if [ -d "/var/www/html/tests/rspec/screenshots" ]; then
		pushd $ARTIFACTS_DIR > /dev/null
			#rm -rf *
			cp -r /var/www/html/tests/rspec/screenshots/* .
		popd > /dev/null
	fi
}

if [ "${COMMAND}" == "circleci" ]; then
	lint_php_files
	run_unit_tests
	setup_permissions
	start_apache_mysql
	run_rspec_tests
elif [ "${COMMAND}" == "test" ]; then
	setup_permissions
	start_apache_mysql
	run_rspec_tests
elif [ "${COMMAND}" == "unittest" ]; then
	run_unit_tests
fi

exit $STATUS
