module Login

  extend Capybara::DSL
  extend RSpec::Expectations
  extend RSpec::Matchers

  attr_accessor :button

  def self.visit
    go_to '/admin.php'
    @button = page.find('input.btn');
  end

  def self.login(username = 'admin', password = 'password')
    self::visit

    fill_in 'username', with: username
    fill_in 'password', with: password

    @button.click
  end

  def self.button
    @button
  end

end
