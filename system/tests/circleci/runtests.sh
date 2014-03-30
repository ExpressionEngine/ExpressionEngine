#!/usr/bin/env bash

cd system/tests/rspec

# We will increment this as we get bad statuses from RSpec and finally
# exit with that status at the end
STATUS=0

# Use PHPbrew to switch PHP versions (have to manually do it for Apache)
for phpversion in "5.3.10" "5.4.21" "5.5.8"
do
	printf "\n\nNow using PHP ${phpversion}\n\n"
	phpenv global $phpversion
	echo "LoadModule php5_module /home/ubuntu/.phpenv/versions/${phpversion}/libexec/apache2/libphp5.so" > /etc/apache2/mods-available/php5.load
	sudo service apache2 restart
	mkdir -p $CIRCLE_ARTIFACTS/$phpversion/

	printf "Running tests, outputting results to build artifacts directory\n\n"
	bundle exec rspec -fh -c -o $CIRCLE_ARTIFACTS/$phpversion/results.html

	# Track the status code from the previous test
	STATUS=$(($STATUS+$?))

	# Move screenshots to the build artifacts directory
	if [ -d "./screenshots" ]; then
		printf "Screenshots taken, moved to build artifacts directory\n\n"
		mv screenshots/* $CIRCLE_ARTIFACTS/$phpversion/
		rmdir screenshots
	fi
done

# Exit with the status codes from the results of the rspec command
exit $STATUS