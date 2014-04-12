module CacheManager

	extend Capybara::DSL
	extend RSpec::Expectations
	extend RSpec::Matchers

	attr_accessor :button

	def self.visit
		find('.dev-menu .has-sub').click
		click_link 'Utilities'
		click_link 'Cache Manager'
		no_php_js_errors

		@button = page.find('form.settings .form-ctrls input.btn');
	end

	def self.button
		@button
	end

end