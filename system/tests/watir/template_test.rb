# Test to run through the Template Manager and update a template 20 times
# 
# Author::  Wes Baker

require 'rubygems'
require 'safariwatir'

browser = Watir::Safari.new
browser.goto('http://expressionengine2/system/')
browser.link(:text, "Template Manager").click
browser.link(:text, "404").click
20.times do
  browser.button(:name, 'update').click
end

puts browser.title.include? 'Edit Template'