require './bootstrap.rb'

feature 'Installer' do
  def swap(file, before, after)
    file = File.expand_path(file)
    temp = File.read(file).gsub(before, after)
    File.open(file, 'w') { |f| f.puts temp }
  end

  before :all do
    # Set the environment variable so reset_db does not import the database
    ENV['installer'] = 'true'

    # Make sure boot.php does not have the FALSE &&
    @boot = File.expand_path('../../system/ee/EllisLab/ExpressionEngine/Boot/boot.php')
    swap(
      @boot,
      "if (FALSE && defined('REQ') && REQ == 'CP' && is_dir(SYSPATH.'ee/installer/'))",
      "if (defined('REQ') && REQ == 'CP' && is_dir(SYSPATH.'ee/installer/'))"
    )

    # Backup config.php
    @config = File.expand_path('../../system/user/config/config.php')
    @config_temp = @config + '.tmp'
    File.rename(@config, @config_temp)

    # Disable directory renaming
    @wizard = File.expand_path('../../system/ee/installer/controllers/wizard.php')
    swap(
      @wizard,
      'return rename(APPPATH, $new_path);',
      '// return rename(APPPATH, $new_path);'
    )

  end

  before :each do
    # Delete existing config and create a new one
    File.delete(@config) if File.exist?(@config)
    File.new(@config, 'w')
    FileUtils.chmod(0666, @config)

    @page = Installer::Base.new
    @page.load
    no_php_js_errors
  end

  after :all do
    # Delete the environment variable that overrode reset_db
    ENV.delete('installer')

    # Add the FALSE && back into boot.php
    swap(
      @boot,
      "if (defined('REQ') && REQ == 'CP' && is_dir(SYSPATH.'ee/installer/'))",
      "if (FALSE && defined('REQ') && REQ == 'CP' && is_dir(SYSPATH.'ee/installer/'))"
    )

    swap(
      @wizard,
      '// return rename(APPPATH, $new_path);',
      'return rename(APPPATH, $new_path);'
    )

    # Put config.php back
    File.delete(@config)
    File.rename(@config_temp, @config)
  end

  it 'should load installer' do
    @page.should have(0).inline_errors
    @page.install_form.all_there?.should == true
  end

  it 'should install successfully' do
    @page.install_form.db_hostname.set $test_config[:db_host]
    @page.install_form.db_name.set $test_config[:db_name]
    @page.install_form.db_username.set $test_config[:db_username]
    @page.install_form.db_password.set $test_config[:db_password]
    @page.install_form.username.set 'admin'
    @page.install_form.email_address.set 'hello@ellislab.com'
    @page.install_form.password.set 'password'
    @page.install_form.install_submit.click

    no_php_js_errors
    @page.req_title.text.should eq 'Completed'
    @page.install_success.success_header.text.should match /ExpressionEngine (\d+\.\d+\.\d+) is now installed/
    @page.install_success.all_there?.should == true
  end

  it 'should show errors with missing database credentials' do
    @page.install_form.install_submit.click

    no_php_js_errors
    @page.install_form.all_there?.should == true
    @page.should have(5).inline_errors
  end

  it 'should show errors with invalid database credentials' do
    @page.install_form.db_hostname.set 'nonsense'
    @page.install_form.db_name.set 'nonsense'
    @page.install_form.db_username.set 'nonsense'
    @page.install_form.username.set 'admin'
    @page.install_form.email_address.set 'hello@ellislab.com'
    @page.install_form.password.set 'password'
    @page.install_form.install_submit.click

    no_php_js_errors
    @page.install_form.all_there?.should == true
    @page.should have_error
    @page.error.text.should include 'Unable to connect to your database using the configuration settings you submitted.'
  end

  it 'should show errors with invalid database prefix' do
    @page.execute_script("$('input[maxlength=30]').prop('maxlength', 80);")
    @page.install_form.db_prefix.set '1234567890123456789012345678901234567890'
    @page.install_form.install_submit.click

    @page.should have(6).inline_errors
    @page.inline_errors[2].text.should include 'This field cannot exceed 30 characters in length.'

    @page.install_form.db_prefix.set '<nonsense>'
    @page.install_form.install_submit.click

    @page.should have(6).inline_errors
    @page.inline_errors[2].text.should include 'There are invalid characters in the database prefix.'

    @page.install_form.db_prefix.set 'exp_'
    @page.install_form.install_submit.click

    @page.should have(6).inline_errors
    @page.inline_errors[2].text.should include 'The database prefix cannot contain the string "exp_".'
  end

  it 'should show errors with invalid username' do
    @page.install_form.username.set 'non<>sense'
    @page.install_form.install_submit.click

    @page.should have(5).inline_errors
    @page.inline_errors[3].text.should include 'Your username cannot use the following characters:'

    @page.install_form.username.set '123'
    @page.install_form.install_submit.click

    @page.should have(5).inline_errors
    @page.inline_errors[3].text.should include 'Your username must be at least 4 characters long'

    @page.execute_script("$('input[maxlength=50]').prop('maxlength', 80);")
    @page.install_form.username.set '12345678901234567890123456789012345678901234567890123456789012345678901234567890'
    @page.install_form.install_submit.click

    @page.should have(5).inline_errors
    @page.inline_errors[3].text.should include 'Your username cannot be over 50 characters in length'
  end

  it 'should show errors with invalid email address' do
    @page.install_form.email_address.set 'nonsense'
    @page.install_form.install_submit.click

    @page.should have(5).inline_errors
    @page.inline_errors[2].text.should include 'This field must contain a valid email address'

    @page.install_form.email_address.set 'nonsense@example'
    @page.install_form.install_submit.click

    @page.should have(5).inline_errors
    @page.inline_errors[2].text.should include 'This field must contain a valid email address'

    @page.install_form.email_address.set 'example.com'
    @page.install_form.install_submit.click

    @page.should have(5).inline_errors
    @page.inline_errors[2].text.should include 'This field must contain a valid email address'
  end

  it 'should show errors with invalid password' do
    @page.install_form.password.set '123'
    @page.install_form.install_submit.click

    @page.should have(5).inline_errors
    @page.inline_errors[4].text.should include 'Your password must be at least 5 characters long'

    @page.execute_script("$('input[maxlength=72]').prop('maxlength', 80);")
    @page.install_form.password.set '12345678901234567890123456789012345678901234567890123456789012345678901234567890'
    @page.install_form.install_submit.click

    @page.should have(5).inline_errors
    @page.inline_errors.last.text.should include 'Your password cannot be over 72 characters in length'

    @page.install_form.username.set 'nonsense'
    @page.install_form.password.set 'nonsense'
    @page.install_form.install_submit.click

    @page.should have(4).inline_errors
    @page.inline_errors.last.text.should include 'The password cannot be based on the username'
  end
end
