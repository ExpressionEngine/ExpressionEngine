require './bootstrap.rb'

feature 'Login Page' do
  it 'shows the login page content' do
    Login::visit

    page.should have_content('Username')
    page.should have_content('Password')
    page.should have_content('Forgot your password?')
  end

  it 'rejects when submitting no credentials' do
    Login::login('', '')

    page.should have_content('The username field is required.')
  end

  it 'rejects when submitting no password' do
    Login::login('admin', '')

    page.should have_content('The password field is required.')
  end

  it 'rejects when submitting invalid credentials' do
    Login::login('noone', 'nowhere')

    page.should have_content('Invalid username or password.')
  end

  it 'logs in when submitting valid credentials' do
    Login::login

    page.should have_content('CP Home')
  end
end
