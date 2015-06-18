ENV['area'] = 'installer'
require './bootstrap.rb'

feature 'Installer' do
  def swap(file, before, after)
    file = File.expand_path(file)
    temp = File.read(file).gsub(before, after)
    File.open(file, 'w') { |f| f.puts temp }
  end

  before :all do
    # Make sure boot.php does not have the FALSE &&
    @boot = File.expand_path('../../system/ee/EllisLab/ExpressionEngine/Boot/boot.php')
    swap(
      @boot,
      "if (FALSE && defined('REQ') && REQ == 'CP' && is_dir(SYSPATH.'ee/installer/'))",
      "if (defined('REQ') && REQ == 'CP' && is_dir(SYSPATH.'ee/installer/'))"
    )

    # Rename config.php temporarily
    @config = File.expand_path('../../system/user/config/config.php')
    @config_temp = File.expand_path('../../system/user/config/config.php.tmp')
    File.rename(@config, @config_temp)
    File.open(@config, 'w')

    # Disable directory renaming
    @wizard = File.expand_path('../../system/ee/installer/controllers/wizard.php')
    swap(
      @wizard,
      'return rename(APPPATH, $new_path);',
      '// return rename(APPPATH, $new_path);'
    )

    @page = Installer::Base.new
  end

  before :each do
    @page.load
    no_php_js_errors
  end

  after :all do
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
end
