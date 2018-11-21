require './bootstrap.rb'

feature 'Profile - Username and Password' do
  before(:each) do
    cp_session
    @page = Profile::UsernamePassword.new
    @page.load
    no_php_js_errors
  end

  it 'should load' do
    @page.all_there?.should == true
  end

  it 'should submit with no changes' do
    @page.current_password.set 'password'
    @page.profile_form.submit

    no_php_js_errors
    @page.all_there?.should == true
  end

  it 'should submit with a password change' do
    @page.password.set 'password'
    @page.confirm_password.set 'password'
    @page.current_password.set 'password'
    @page.profile_form.submit

    no_php_js_errors
    @page.all_there?.should == true
  end

  it 'should not submit with a password that is too long' do
    # Password is 80 characters long, 72 is the max
    @page.execute_script("$('input[maxlength=72]').prop('maxlength', 80);")
    @page.password.set '12345678901234567890123456789012345678901234567890123456789012345678901234567890'
    @page.confirm_password.set '12345678901234567890123456789012345678901234567890123456789012345678901234567890'
    @page.current_password.set 'password'
    @page.profile_form.submit

    no_php_js_errors
    @page.should have_text 'Your password cannot be over 72 characters in length'
  end
end
