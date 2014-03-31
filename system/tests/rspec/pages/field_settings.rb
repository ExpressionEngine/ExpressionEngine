module FieldSettings

	extend Capybara::DSL
	extend RSpec::Expectations
	extend RSpec::Matchers

	def self.visit
		within('#navigationTabs') do
			click_link 'Admin'
			click_link 'Channel Administration'
			click_link 'Channel Fields'
		end
		no_php_js_errors
	end

	def self.add_field_page
		click_link 'Create a New Channel Field Group'
	end

	def self.select_fieldtype(fieldtype)
		select(fieldtype, :from => 'field_type')
	end

end