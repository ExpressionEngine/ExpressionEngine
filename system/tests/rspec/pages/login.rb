module Login

	extend Capybara::DSL
	extend RSpec::Expectations
	extend RSpec::Matchers

	def self.visit
		go_to '/system'
	end

	def self.login(username = 'admin', password = 'password')
		self::visit

		fill_in 'username', with: username
		fill_in 'password', with: password

		click_button 'Login'
	end

end