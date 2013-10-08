require 'capybara/cucumber' 
require 'capybara/webkit'
require 'capybara/rspec'

Capybara.run_server = false
Capybara.default_selector = :css
Capybara.default_driver = :webkit
Capybara.javascript_driver = :webkit

# Make a file called env_local.rb and set this
# Capybara.app_host = 'http://expressionengine2/'