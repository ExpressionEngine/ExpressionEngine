Given /^I .* on (?:the )control panel .*?$/ do
  visit "http://expressionengine2/system/"
end

Given /^I am logged out$/ do
  click_link "Log-out" if page.has_link?('Log-out')
end

When /I click on (.*)/ do |link|
  click_link link
end

When /^I click the button (.*)$/ do |button|
  click_button button
end

Then /^I should see "([^\"]*)"$/ do |arg1|
  page.should have_content(arg1)
end