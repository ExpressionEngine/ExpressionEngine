require './bootstrap.rb'

feature 'Login Page' do
  it 'shows the login page content' do
    Login::visit

    page.should have_content('Username')
    page.should have_content('Password')
    page.should have_content('I forgot my password')
    Login::button[:disabled].should eq nil
    no_php_js_errors
  end

  it 'rejects when submitting no credentials' do
    Login::login('', '')

    page.should have_content('The username field is required')
    Login::button[:disabled].should eq nil
    no_php_js_errors
  end

  it 'rejects when submitting no password' do
    Login::login('admin', '')

    page.should have_content('The password field is required')
    Login::button[:disabled].should eq nil
    no_php_js_errors
  end

  it 'logs in when submitting valid credentials' do
    Login::login

    page.should have_content('Overview')
    Login::button[:disabled].should eq nil
    no_php_js_errors
  end

  it 'rejects when submitting invalid credentials' do
    Login::login('noone', 'nowhere')

    page.should have_content('That is the wrong username or password')
    Login::button[:disabled].should eq nil
    no_php_js_errors
  end

  it 'locks the user out after four login attempts' do
    Login::login('noone', 'nowhere')
    Login::login('noone', 'nowhere')
    Login::login('noone', 'nowhere')
    Login::login('noone', 'nowhere')

    page.should have_content('You are only permitted to make four login attempts every 1 minute(s)')
    Login::button.value.should eq 'Locked'
    Login::button[:disabled].should eq 'true'
    no_php_js_errors
  end

  it 'shows the reset password form when link is clicked' do
    Login::visit

    click_link 'I forgot my password'

    page.should have_content('Reset Password')
    no_php_js_errors
  end
end
