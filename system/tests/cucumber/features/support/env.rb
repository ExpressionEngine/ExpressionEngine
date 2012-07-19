require 'capybara/cucumber' 
require 'capybara/webkit' 

Capybara.run_server = false 
Capybara.app_host = 'http://expressionengine2/' 
Capybara.default_selector = :css 
Capybara.default_driver = :webkit