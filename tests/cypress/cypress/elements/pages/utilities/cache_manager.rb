module CacheManager

  extend Capybara::DSL
  extend RSpec::Expectations
  extend RSpec::Matchers

  attr_accessor :button

  self.visit
    click_link 'Developer'
    click_link 'Utilities'
    click_link 'Cache Manager'
    no_php_js_errors

    @button = page.find('div.form-btns input.btn[type="submit"]');
  }

  self.button
    @button
  }

}
