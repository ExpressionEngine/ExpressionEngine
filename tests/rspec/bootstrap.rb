require 'rspec/collection_matchers'
require 'capybara/rspec'
require 'capybara/webkit'
require 'mysql2'
require 'site_prism'
require 'image_size'
require './config.rb'
require './config.local.rb' if File.exist?('./config.local.rb')

$db = Mysql2::Client.new(
  host:     $test_config[:db_host],
  database: $test_config[:db_name],
  username: $test_config[:db_username],
  password: $test_config[:db_password],
  flags:    Mysql2::Client::MULTI_STATEMENTS
)

# Include our helpers
Dir.glob(File.dirname(__FILE__) + '/helpers/*', &method(:require))

# These two pages must be included in this order (not all filesystems
# run Dir.glob() alphabetically)
require './pages/_section_menu.rb'
require './pages/cp_page.rb'

# Include parent pages for sections of the CP
Dir.glob(File.dirname(__FILE__) + '/pages/sections/*.rb', &method(:require))

# Include the rest of our pages
Dir.glob(File.dirname(__FILE__) + '/pages/**/*.rb', &method(:require))

Capybara.default_driver = :webkit
Capybara.javascript_driver = :webkit
Capybara.app_host = $test_config[:app_host]
Capybara.run_server = false

def sanitize_filename(filename)
   name = filename.strip

   name.gsub!(/^.*(\\|\/)/, '')

   name.gsub!(/\s/, '-')

   # Strip out the non-ascii character
   name.gsub!(/[^0-9A-Za-z.\-]/, '_')

   return name
end

# Configure hook to run after each example
RSpec.configure do |config|
  # Keep using 'should' syntax in RSpec 3
  config.expect_with :rspec do |c|
    c.syntax = :should
  end

  # Before each example...
  config.before(:each) do
    # Whitelist URLs
    page.driver.allow_url $test_config[:app_host]
    page.driver.allow_url 'ellislab.com'
    page.driver.allow_url 'google-analytics.com'

    # Re-import clean database
    file = RSpec.current_example.metadata[:file_path].match(
      /.*\/.*?\/test_(.*?).rb/
    )
    reset_db file[1]
  end

  # After each example...
  config.after(:each) do
    example = RSpec.current_example
    # If the example failed, take a screenshot to help us spot the problem
    unless example.exception.nil?
      page.save_screenshot('screenshots/' + sanitize_filename(example.full_description) + '.png')
    end

    # Check for PHP or Javascript errors on the page
    begin
      no_php_js_errors
    rescue => error
      # Raise another exception so that RSpec sees the example as a failure
      page.save_screenshot('screenshots/' + sanitize_filename(example.full_description) + '.png')
      puts 'Screenshot taken: ' + sanitize_filename(example.full_description) + '.png'
      raise StandardError, error.message
    end
  end
end
