module CacheManager

  extend Capybara::DSL
  extend RSpec::Expectations
  extend RSpec::Matchers

  attr_accessor :button

  def self.visit
    click_link 'Developer'
    click_link 'Utilities'
    click_link 'Cache Manager'
    no_php_js_errors

    @button = page.find('div.form-btns.form-btns-top input.btn[type="submit"]');
  end

  def self.button
    @button
  end

end
