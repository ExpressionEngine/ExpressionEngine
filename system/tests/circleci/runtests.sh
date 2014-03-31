#!/usr/bin/env bash

cd system/tests/rspec

# We will increment this as we get bad statuses from RSpec and finally
# exit with that status at the end
STATUS=0

# Get the PHP version to test based on the node index; if we want more
# PHP versions, be sure to add another container
PHPVERSION=php$CIRCLE_NODE_INDEX
PHPVERSION=${!PHPVERSION}

# Switch PHP version with phpenv and reload the Apache module
printf "Testing under PHP ${PHPVERSION}\n\n"
phpenv global $PHPVERSION
echo "LoadModule php5_module /home/ubuntu/.phpenv/versions/${PHPVERSION}/libexec/apache2/libphp5.so" > /etc/apache2/mods-available/php5.load
sudo service apache2 restart

# We'll store our build artifacts under the name of the current PHP version
mkdir -p $CIRCLE_ARTIFACTS/$PHPVERSION/

# Finally, run the tests, outputting resultss to build artifacts directory
printf "Running tests, outputting results to build artifacts directory\n\n"
bundle exec rspec -fh -c -o $CIRCLE_ARTIFACTS/$PHPVERSION/results.html

# Capture status for to exit with later
STATUS=$(($STATUS+$?))

# If screenshots were taken, move them to the build artifacts directory
if [ -d "./screenshots" ]; then
	printf "Screenshots taken, moved to build artifacts directory\n\n"
	mv screenshots/* $CIRCLE_ARTIFACTS/$PHPVERSION/
	rmdir screenshots
fi

exit $STATUS