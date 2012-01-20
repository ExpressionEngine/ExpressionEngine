# Conditions

Given /^I .* on (?:the )control panel .*?$/ do
  visit "http://expressionengine2/system/"
end

Given /^I am logged out$/ do
  click_link "Log-out" if page.has_link?('Log-out')
end

# Actions

When /I click on (.*)/ do |link|
  click_link link
end

When /^I click the button (.*)$/ do |button|
  click_button button
end

When /^I login using the following:$/ do |table|
  table.rows_hash.each do |field, value|
    fill_in field, :with => value
  end
  click_button "Login"
end

# Tests

Then /^I should (not )?see "([^\"]*)"$/ do |negation, text|
  # If the negation isn't there, the string should exist
  if negation.nil?
    page.should have_content(text)
  else
    page.should have_no_content(text)
  end 
end