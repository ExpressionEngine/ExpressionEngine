require 'capybara/cucumber' 
require 'capybara/webkit' 
require 'cucumber/formatter/unicode' 
require 'rspec/expectations' 

Capybara.run_server = false 
Capybara.app_host = 'http://myhost.com' 
Capybara.default_selector = :css 
Capybara.default_driver = :webkit