require './bootstrap.rb'

feature 'Member Registration' do

  before(:each) do
    cp_session
    @page = Member::Create.new
    @page.load
    no_php_js_errors
  end

  it 'loads' do
    @page.all_there?.should == true
  end

  it 'prevents duplicate gmail email addresses' do
    @page.username.set 'test'
    @page.email.set 'test@gmail.com'
    @page.password.set 'password'
    @page.confirm_password.set 'password'
    @page.submit

    no_php_js_errors
    # Save and New is the only action
    @page.all_there?.should == true

    @page.email.set 't.e.s.t@gmail.com'
    @page.email.trigger 'blur'
    @page.wait_for_error_message_count(1)
    should_have_form_errors(@page)
    should_have_error_text(
      @page.email,
      'This field must contain a unique email address.'
    )
  end
end
