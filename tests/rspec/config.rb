#
# Don't edit this, these settings are for the testing server.
# Create a new file called config.local.rb and set your
# environment settings there.
#
$test_config = {
	:app_host    => 'http://ee2.test:8080/', # URL Capybara will use to access your EE install
	:db_host     => 'localhost', # DB settings for resetting your database
	:db_name     => 'circle_test',
	:db_username => 'ubuntu',
	:db_password => ''
}