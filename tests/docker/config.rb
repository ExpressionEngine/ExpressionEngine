#
# Don't edit this, these settings are for the testing server.
# Create a new file called config.local.rb and set your
# environment settings there.
#
$test_config = {
  # URL Capybara will use to access your EE install
  app_host:    'http://localhost/',

  # DB settings for resetting your database
  db_host:     'localhost',
  db_name:     'ee-test',
  db_username: 'root',
  db_password: ''
}
