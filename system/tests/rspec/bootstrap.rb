require 'capybara/rspec'
require 'capybara/webkit'
require 'mysql2'
require 'site_prism'
require './config.rb'

if File.exists?('./config.local.rb') then
	require './config.local.rb'
end

$db = Mysql2::Client.new(
	:host     => $test_config[:db_host],
	:database => $test_config[:db_name],
	:username => $test_config[:db_username],
	:password => $test_config[:db_password],
	:flags    => Mysql2::Client::MULTI_STATEMENTS
)

# Include our page objects/helpers
Dir.glob(File.dirname(__FILE__) + '/helpers/*', &method(:require))
Dir.glob(File.dirname(__FILE__) + '/pages/*', &method(:require))

Capybara.default_driver = :webkit
Capybara.javascript_driver = :webkit
Capybara.app_host = $test_config[:app_host]
Capybara.run_server = false

# Wait 20 second at most for AJAX to finish; it's a lot, but
# there were some intermittent failings maybe due to requests
# getting backed up due to the speed of the tests
Capybara.default_wait_time = 20

# Configure hook to run after each example
RSpec.configure do |config|

	# Before each example...
	config.before(:each) do
		reset_db
	end

	# After each example...
	config.after(:each) do
		# If the example failed, take a screenshot to help us spot the problem
		if example.exception != nil
			page.save_screenshot('screenshots/'+example.description+'.png');
		end

		# Check for PHP or Javascript errors on the page
		begin
			no_php_js_errors
		rescue => error
			# Raise another exception so that RSpec sees the example as a failure
			raise StandardError, error.message
			page.save_screenshot('screenshots/'+example.description+'.png');
			puts 'Screenshot taken: ' + example.description+'.png'
		end
	end
end