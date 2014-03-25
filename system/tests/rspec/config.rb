#
# Don't edit this, these settings are for the testing server.
# Create a new file called config.local.rb and set your
# environment settings there.
#
$test_config = {
	:app_host    => 'http://localhost/', # URL Capybara will use to access your EE install
	:db_host     => 'localhost', # DB settings for resetting your database
	:db_name     => 'ee2',
	:db_username => 'root',
	:db_password => 'root'
}