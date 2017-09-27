#!/usr/bin/env bash

# We will increment this as we get bad statuses from RSpec and finally
# exit with that status at the end
STATUS=0

# Script provided by CircleCI, modified for 0.7.3.1 instead of 0.5.3.1
# curl -sSL https://s3.amazonaws.com/circle-downloads/install-mysql5.7-circleci.sh | sh
installmysql() {
	set -x
	# set -e

 	sudo rm /etc/apt/sources.list.d/mysql.list
	export DEBIAN_FRONTEND=noninteractive
	curl -LO https://dev.mysql.com/get/mysql-apt-config_0.7.3-1_all.deb
	echo mysql-apt-config mysql-apt-config/select-product          select Apply              | sudo debconf-set-selections
	echo mysql-apt-config mysql-apt-config/select-server           select mysql-5.7          | sudo debconf-set-selections
	echo mysql-apt-config mysql-apt-config/select-connector-python select none               | sudo debconf-set-selections
	echo mysql-apt-config mysql-apt-config/select-workbench        select none               | sudo debconf-set-selections
	echo mysql-apt-config mysql-apt-config/select-utilities        select none               | sudo debconf-set-selections
	echo mysql-apt-config mysql-apt-config/select-connector-odbc   select connector-odbc-x.x | sudo debconf-set-selections
	sudo -E dpkg -i mysql-apt-config_0.7.3-1_all.deb
	sudo apt-get update
	echo mysql-community-server mysql-community-server/re-root-pass password ${mysql_root_password} | sudo debconf-set-selections
	echo mysql-community-server mysql-community-server/root-pass    password ${mysql_root_password} | sudo debconf-set-selections
	sudo -E apt-get -y install mysql-server

	echo "Checking installed version....."
	mysql -D mysql -e "SELECT version()"
	echo "Done!!"

	set +x
}

setpermissions() {
	cp tests/circleci/config.php system/user/config/
	cp tests/circleci/license.key system/user/config/
	cp tests/docker/EllisLabUpdate.pub system/ee/EllisLab/ExpressionEngine
	chmod 666 system/user/config/config.php
	chmod -R 777 system/user
	chmod -R 777 system/ee/legacy/translations
	chmod 777 tests/rspec/support/tmp
	mkdir -p tests/rspec/support/file-sync/uploads
	chmod -R 777 tests/rspec/support/file-sync/uploads
	chmod -R 777 images
	chmod +x tests/circleci/runtests.sh
}

# Explode php_versions environment variable since we can't assign
# arrays in the YML
PHP_VERSIONS_ARRAY=(${php_versions// / })

printf "Starting tests. Outputting results to build artifacts directory\n\n"

i=0
for PHPVERSION in ${PHP_VERSIONS_ARRAY[@]}
do
	if [ $(($i % $CIRCLE_NODE_TOTAL)) -eq $CIRCLE_NODE_INDEX ]
	then

		# Install MySQL 5.7 when we're testing PHP 7
		PHP_VERSION_ASPLODE=(${PHPVERSION//./ })
		PHP_MAJOR_VERSION=${PHP_VERSION_ASPLODE[0]}
		if [[ $PHP_MAJOR_VERSION -eq 7 ]]
		then
			# Script provided by CircleCI
			installmysql

			# Prevent "MySQL server has gone away" error
			echo -e "[mysqld]\nmax_allowed_packet=256M\nwait_timeout=300\ninteractive_timeout=300\nsql_mode='ONLY_FULL_GROUP_BY,STRICT_ALL_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'" | sudo sh -c "cat >> /etc/mysql/my.cnf"

			# Upgrade databases
			sudo mysql_upgrade -u ubuntu --force

			sudo service mysql restart
		fi

		# Switch PHP version with phpenv and reload the Apache module
		printf "Testing under PHP ${PHPVERSION}\n\n"
		phpenv global $PHPVERSION
		echo "LoadModule php${PHP_MAJOR_VERSION}_module /home/ubuntu/.phpenv/versions/${PHPVERSION}/libexec/apache2/libphp${PHP_MAJOR_VERSION}.so" > /etc/apache2/mods-available/php5.load

		setpermissions

		if [ $CIRCLE_NODE_INDEX -eq 2 ]
		then
			APP_VERSION=`cat system/ee/legacy/libraries/Core.php | perl -ne '/'\''APP_VER'\'',\s+'\''(.*)'\''/g && print $1'`
			gulp app --archive --dirty --local-key --upload-circle-build --version=$APP_VERSION
		fi

		# Disable opcode cache
		echo -e "\n[opcache]\nopcache.enable=0" | sudo sh -c "cat >> /home/ubuntu/.phpenv/versions/${PHPVERSION}/etc/php.ini"

		# Get rid of ridiculous warning in PHP 5.6, we don't even use $HTTP_RAW_POST_DATA
		# http://stackoverflow.com/questions/26261001/warning-about-http-raw-post-data-being-deprecated
		echo -e "\n[PHP]\nalways_populate_raw_post_data=-1" | sudo sh -c "cat >> /home/ubuntu/.phpenv/versions/${PHPVERSION}/etc/php.ini"

		sudo service apache2 restart

		# We'll store our build artifacts under the name of the current PHP version
		mkdir -p $CIRCLE_ARTIFACTS/$PHPVERSION/

		# Clear cache
		rm -rf system/user/cache/*

		pushd tests/rspec
			# Run the tests, outputting the results in the artifacts directory.
			printf "Running Rspec tests\n\n"
			bundle install --without development --deployment
			bundle exec rspec -c -fd -fh -o $CIRCLE_ARTIFACTS/$PHPVERSION/rspec.html tests/**/*.rb

			# Append status code for this test
			((STATUS+=$?))

			# If screenshots were taken, move them to the build artifacts directory
			if [ -d "./screenshots" ]; then
				printf "Screenshots taken, moved to build artifacts directory\n\n"
				mv screenshots/* $CIRCLE_ARTIFACTS/$PHPVERSION/
				rmdir screenshots
			fi
		popd

		# Repo was likely clobbered by upgrade, reset
		sudo chown -R ubuntu *
		git reset HEAD --hard

		# PHPUnit tests
		pushd system/ee/EllisLab/Tests/
			printf "Running PHPUnit tests\n\n"
			composer install --prefer-source --no-interaction
			vendor/bin/phpunit ExpressionEngine/ > $CIRCLE_ARTIFACTS/$PHPVERSION/phpunit.txt

			# Save our exit status code
			((STATUS+=$?))

			# Remove CLI colors
			sed -i -r "s/\x1B\[([0-9]{1,2}(;[0-9]{1,2})*)?m//g" $CIRCLE_ARTIFACTS/$PHPVERSION/phpunit.txt
		popd

		# Updater microapp unit tests
		pushd system/ee/installer/updater/EllisLab/Tests/
			printf "Running PHPUnit tests\n\n"
			composer install --prefer-source --no-interaction
			vendor/bin/phpunit ExpressionEngine/ > $CIRCLE_ARTIFACTS/$PHPVERSION/phpunit-updater.txt

			# Save our exit status code
			((STATUS+=$?))

			# Remove CLI colors
			sed -i -r "s/\x1B\[([0-9]{1,2}(;[0-9]{1,2})*)?m//g" $CIRCLE_ARTIFACTS/$PHPVERSION/phpunit-updater.txt
		popd
	fi
	((i++))
done

exit $STATUS
